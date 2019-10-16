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

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Treo\Core\Utils\Condition\Condition;
use Treo\Services\AbstractService;

/**
 * Completeness service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Completeness extends AbstractService
{
    const LIMIT = 10000;
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
     * @throws Error
     */
    public function runUpdateCompleteness(Entity $entity): array
    {
        $result = [];
        foreach ($this->getMethodsCompleteness() as $method) {
            if (is_array($method['entities']) && in_array($entity->getEntityType(), $method['entities'])) {
                $completeness = new $method['service']();
                if ($completeness instanceof CompletenessInterface) {
                    $completeness->setContainer($this->getContainer());
                    $completeness->setEntity($entity);
                    $completeness->setLanguages($this->getLanguages());
                    $result = $completeness->run();
                }
                break;
            }
        }
        if (empty($result)) {
            $this->setEntity($entity);
            $this->setLanguages($this->getLanguages());
            $result = $this->runUpdateCommonCompleteness();
        }
        $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
        return $result;
    }

    /**
     * Recalc all completeness for entity instances
     *
     * @param string $entityName
     * @param array $where
     *
     * @return void
     * @throws Error
     */
    public function recalcEntities(string $entityName, array $where = []): void
    {
        $count = $this->getEntityManager()
            ->getRepository($entityName)
            ->where($where)
            ->count();

        if ($count > 0) {
            $max = (int)$this->getConfig()->get('webMassUpdateMax', 200);
            if ($max < 1) {
                throw new Error('Invalid config option webMassUpdateMax');
            }
            for ($j = 0; $j <= $count; $j += self::LIMIT) {
                $entities = $this->selectLimitById($entityName, self::LIMIT, $j, $where);
                if (count($entities) > 0) {
                    $chunks = array_chunk($entities, $max);
                    foreach ($chunks as $chunk) {
                        $name = 'Updated completeness for ' . $entityName;
                        $this->qmPush(
                            $name,
                            'QueueManagerMassUpdateComplete',
                            ['entitiesIds' => array_column($chunk, 'id'), 'entityName' => $entityName]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    public function hasCompleteness(string $entityName): bool
    {
        $entityName = $this
            ->getContainer()
            ->get('metadata')
            ->get('completeness.Completeness.' . $entityName, $entityName);

        return !empty($this->getContainer()->get('metadata')->get("scopes.$entityName.hasCompleteness"));
    }

    /**
     * Update completeness for any entity
     *
     * @return array
     * @throws Error
     */
    protected function runUpdateCommonCompleteness(): array
    {
        $this->prepareRequiredFields();

        $completeness['complete'] = $this->calculationLocalComplete();
        $completeness = array_merge($completeness, $this->calculationCompleteMultiLang());
        $completeness['completeTotal'] = $this->calculationTotalComplete();

        $this->updateActive($completeness['complete']);
        $this->setFieldsCompletenessInEntity($completeness);
        $completeness['isActive'] = $this->entity->get('isActive');

        return $completeness;
    }

    /**
     * Prepare required fields and check on empty
     * @throws Error
     */
    protected function prepareRequiredFields(): void
    {
        $entityDefs = $this
            ->getContainer()
            ->get('metadata')
            ->get('entityDefs.' . $this->entity->getEntityType() . '.fields');

        foreach ($entityDefs as $name => $row) {
            $isRequiredField = $this->isRequiredField($name, $row);
            if ($isRequiredField && !empty($row['isMultilang'])) {
                $isEmpty = $this->isEmpty($name);
                $item = ['name' => $name, 'isEmpty' => $isEmpty];

                $this->fieldsAndAttrs['fields'][] = $item;
                $this->fieldsAndAttrs['localComplete'][] = $item;
                $this->fieldsForTotalComplete[] = $isEmpty;

                foreach ($this->languages as $local => $language) {
                    $isEmpty = $this->isEmpty($name, $language);
                    $item = ['name' => $name . $language, 'isEmpty' => $isEmpty, 'isMultiLang' => true];

                    $this->fieldsForTotalComplete[] = $isEmpty;
                    $this->fieldsAndAttrs['fields'][] = $item;
                    $this->fieldsAndAttrs['multiLang'][$local][] = $item;
                }
            } elseif ($isRequiredField) {
                $isEmpty = $this->isEmpty($name);
                $item = ['name' => $name, 'isEmpty' => $isEmpty];

                $this->fieldsForTotalComplete[] = $isEmpty;
                $this->fieldsAndAttrs['localComplete'][] = $item;
                $this->fieldsAndAttrs['fields'][] = $item;
            }
        }
    }

    /**
     * @return float
     */
    protected function calculationLocalComplete(): float
    {
        return $this->commonCalculationComplete($this->fieldsAndAttrs['localComplete']);
    }

    /**
     * @return array
     */
    protected function calculationCompleteMultiLang(): array
    {
        $completenessLang = [];
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->languages as $locale => $language) {
                $completenessLang['complete' . $language] =
                    $this->commonCalculationComplete($this->fieldsAndAttrs['multiLang'][$locale]);
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
     * @param $complete
     */
    protected function updateActive($complete): void
    {
        $isActive = $this->entity->get('isActive');
        if (!empty($isActive) && round($complete) < 100) {
            $this->entity->set('isActive', 0);
        }
    }

    /**
     * @param array $completeness
     */
    protected function setFieldsCompletenessInEntity(array $completeness): void
    {
        foreach ($completeness as $field => $complete) {
            $this->entity->set($field, $complete);
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
     * @param int $limit
     * @param int $offset
     * @param array $where
     * @return array
     */
    protected function selectLimitById(string $entityName, $limit = 2000, $offset = 0, array $where = []): array
    {
        return $entities = $this->getEntityManager()
            ->getRepository($entityName)
            ->select(['id'])
            ->where($where)
            ->limit($offset, $limit)
            ->find()
            ->toArray();
    }

    /**
     * @param array $items
     *
     * @return float
     */
    protected function commonCalculationComplete(?array $items): float
    {
        $complete = 100;
        if (!empty($items)) {
            $complete = 0;
            $coefficient = 100 / count($items);
            foreach ($items as $item) {
                if (empty($item['isEmpty'])) {
                    $complete += $coefficient;
                }
            }
        }
        return (float)round($complete, 2);
    }

    /**
     * @param $languages
     */
    public function setLanguages(array $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @param Entity $entity
     */
    public function setEntity(Entity $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return array
     */
    protected function getMethodsCompleteness(): array
    {
        return $this
            ->getContainer()
            ->get('metadata')
            ->get('completeness.Completeness.methodsCompleteness', []);
    }

    /**
     * @param string $field
     * @param array $row
     * @return bool
     * @throws Error
     */
    protected function isRequiredField(string $field, array $row): bool
    {
        $condition = $this
            ->getContainer()
            ->get('metadata')
            ->get("clientDefs.{$this->entity->getEntityName()}.dynamicLogic.fields.$field.required.conditionGroup", []);
        return !empty($row['required'])
                || (!empty($condition) && Condition::isCheck(Condition::prepare($this->entity, $condition)));
    }

    /**
     * @param string $name
     * @param string $serviceName
     * @param array $data
     *
     * @return bool
     */
    private function qmPush(string $name, string $serviceName, array $data): bool
    {
        return $this
            ->getContainer()
            ->get('queueManager')
            ->push($name, $serviceName, $data);
    }
}
