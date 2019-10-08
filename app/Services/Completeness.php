<?php
/**
 * Completeness
 * Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <http://treopim.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace Completeness\Services;

use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Treo\Core\Container;
use Treo\Services\AbstractService;

/**
 * Completeness service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Completeness extends AbstractService
{
    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var array
     */
    protected $fieldsForTotalComplete = [];

    /**
     * @var array
     */
    protected $languages = [];

    /**
     * @var array
     */
    protected $fieldsAndAttrs = [];


    /**
     * Update completeness
     *
     * @param Entity $entity
     *
     * @return array
     */
    public function runUpdateCompleteness(Entity $entity): array
    {
        $methodsCompleteness = $this
            ->getContainer()
            ->get('metadata')
            ->get('completeness.Completeness.methodsCompleteness');

        foreach ($methodsCompleteness as $method) {
            if (is_array($method['entities']) && in_array($entity->getEntityType(), $method['entities'])) {
                $completeness =  new $method['service']();

                if ($completeness instanceof CompletenessInterface) {
                    $completeness->setContainer($this->getContainer());
                    $completeness->setEntity($entity);
                    $completeness->setLanguages($this->getLanguages());

                    return $completeness->run($entity);
                }
            }
        }

        $result = $this->runUpdateCommonCompleteness($entity);

        return $result;
    }

    /**
     * Update completeness for any entity
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function runUpdateCommonCompleteness(Entity $entity): array
    {
        $this->entity = $entity;
        $this->languages = $this->getLanguages();

        $this->prepareRequiredFields();

        $completeness['complete'] = $this->calculationLocalComplete();
        $completeness['multiLang'] =  $this->calculationCompleteMultiLang();
        $completeness['completeTotal'] = $this->calculationTotalComplete();

        $isActive = $this->updateActive($completeness['completeTotal']);
        $this->setFieldsCompletenessInEntity($completeness);

        $completeness['isActive'] = $isActive;

        $this->getEntityManager()->saveEntity($this->entity, ['skipAll' => true]);

        return $completeness;
    }

    /**
     * Recalc all completeness for entity instances
     *
     * @param string $entityName
     *
     * @return void
     */
    public function recalcEntity(string $entityName): void
    {
        if (!empty($entities = $this->find($entityName)) && count($entities) > 0) {
            foreach ($entities as $entity) {
                // update completeness
                $this->runUpdateCompleteness($entity);
            }
        }
    }


    /**
     * Prepare required fields and check on empty
     */
    protected function prepareRequiredFields(): void
    {
        $entityDefs = $this
            ->getContainer()
            ->get('metadata')
            ->get('entityDefs.' . $this->entity->getEntityType() . '.fields');

        foreach ($entityDefs as $name => $row) {
            if (!empty($row['required']) && !empty($row['isMultilang'])) {
                $isEmpty = $this->isEmpty($name);

                $this->fieldsAndAttrs['fields'][] = ['name' => $name, 'isEmpty' => $isEmpty];
                $this->fieldsForTotalComplete[] = $isEmpty;

                foreach ($this->languages as $local => $language) {
                    $isEmpty = $this->isEmpty($name, $language);

                    $this->fieldsForTotalComplete[] = $isEmpty;
                    $this->fieldsAndAttrs['multiLangFields'][$local][] = [
                        'name' => $name . $language,
                        'isEmpty' => $isEmpty
                    ];
                }
            } elseif (!empty($row['required']) ) {
                $isEmpty = $this->isEmpty($name);
                $this->fieldsForTotalComplete[] = $isEmpty;
                $this->fieldsAndAttrs['fields'][] = ['name' => $name, 'isEmpty' => $isEmpty];
            }
        }
    }

    /**
     * @return float
     */
    protected function calculationLocalComplete(): float
    {
        $complete = 100;

        if (!empty($this->fieldsAndAttrs['fields'])) {
            $complete = 0;
            $coefficient = 100 / count($this->fieldsAndAttrs['fields']);

            foreach ($this->fieldsAndAttrs['fields'] as $field) {
                if (empty($field['isEmpty'])) {
                    $complete += $coefficient;
                }
            }
        }
        return (float)round($complete, 2);
    }

    /**
     * @return array
     */
    protected function calculationCompleteMultiLang(): array
    {
        $completenessLang = [];
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->languages as $locale => $language) {
                $multiLangComplete = 100;
                if (!empty($this->fieldsAndAttrs['multiLangFields'][$locale])) {
                    $coefficient = 100 / count($this->fieldsAndAttrs['multiLangFields'][$locale]);
                    $multiLangComplete = 0;
                    foreach ($this->fieldsAndAttrs['multiLangFields'][$locale] as $field) {
                        if (empty($field['isEmpty'])) {
                            $multiLangComplete += $coefficient;
                        }
                    }
                }
                $completenessLang['complete' . $language] = $multiLangComplete;
            }
        }
        return $completenessLang;
    }
    /**
     * @return float
     */
    protected function calculationTotalComplete(): float
    {
        $totalComplete = 100;

        if (!empty($this->fieldsForTotalComplete)) {
            $coefficient = 100 / count($this->fieldsForTotalComplete);
            $totalComplete = 0;
            foreach ($this->fieldsForTotalComplete as $isEmpty) {
                if (empty($isEmpty)) {
                    $totalComplete += $coefficient;
                }
            }
        }
        return (float)round($totalComplete, 2);
    }

    /**
     * @param mixed $value
     * @param string $language
     *
     * @return bool
     */
    protected function isEmpty($value, string $language = ''): bool
    {
        $result = true;
        if (is_string($value) && !empty($this->entity->get($value . $language))) {
            $result = false;
        }

        return $result;
    }

    /**
     * @param $totalComplete
     * @return bool
     */
    protected function updateActive($totalComplete): bool
    {
        $result = $this->entity->get('isActive');
        if (!empty($result) && round($totalComplete) < 100) {
            $this->entity->set('isActive', 0);
            $result = 0;
        }
        return !empty($result);
    }

    /**
     * @param array $completeness
     */
    protected function setFieldsCompletenessInEntity(array $completeness): void
    {
        // update db
        foreach ($completeness as $field => $complete) {
            $this->entity->set($field, (string)round($complete, 2));
        }
    }

    /**
     * Get languages
     *
     * @return array
     */
    protected function getLanguages(): array
    {
        $languages = [];
        if (!empty($this->getConfig()->get('isMultilangActive'))) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $languages[$locale] = Util::toCamelCase(strtolower($locale), '_', true);
            }
        }
        return $languages;
    }

    /**
     * @param string $entityName
     *
     * @return EntityCollection
     */
    protected function find(string $entityName): EntityCollection
    {
        return $this->getEntityManager()->getRepository($entityName)->find();
    }
}
