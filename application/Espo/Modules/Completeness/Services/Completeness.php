<?php
/**
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see http://treopim.com/eula.
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

namespace Espo\Modules\Completeness\Services;

use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

/**
 * Completeness service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Completeness extends \Treo\Services\AbstractService
{
    /**
     * Update completeness
     *
     * @param Entity $entity
     *
     * @return Entity
     */
    public function runUpdateCompleteness(Entity $entity): Entity
    {
        switch ($entity->getEntityType()) {
            case 'Product':
                $entity = $this->runUpdateProductCompleteness($entity);
                break;
            default:
                $entity = $this->runUpdateCommonCompleteness($entity);
                break;
        }

        return $entity;
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
                $entity = $this->runUpdateCompleteness($entity);

                // force save entity
                $this->saveEntity($entity);
            }
        }
    }

    /**
     * Update completeness for any entity
     *
     * @param Entity $entity
     *
     * @return Entity
     */
    protected function runUpdateCommonCompleteness(Entity $entity): Entity
    {
        // get entity name
        $entityName = $entity->getEntityType();

        if (!empty($requireds = $this->getRequireds($entityName))) {
            // prepare coefficient
            $coefficient = 100 / count($requireds);

            // prepare complete
            $complete = 0;
            foreach ($requireds as $field) {
                if (!empty($entity->get($field))) {
                    $complete += $coefficient;
                }
            }
            $entity->set('complete', $complete);

            /**
             * For multilang fields
             */
            if ($this->getConfig()->get('isMultilangActive')) {
                if (!empty($multilangRequireds = $this->getRequireds($entityName, true))) {
                    // prepare coefficient
                    $multilangCoefficient = 100 / count($multilangRequireds);

                    foreach ($this->getLanguages() as $language) {
                        $multilangComplete = 0;
                        foreach ($multilangRequireds as $field) {
                            if (!empty($entity->get("{$field}{$language}"))) {
                                $multilangComplete += $multilangCoefficient;
                            }
                        }
                        $entity->set("complete{$language}", $multilangComplete);
                    }
                } else {
                    foreach ($this->getLanguages() as $language) {
                        $entity->set("complete{$language}", 100);
                    }
                }
            }

            // checking activation
            if (!empty($entity->get('isActive')) && $complete < 100) {
                $entity->set('isActive', 0);
            }
        }

        return $entity;
    }

    /**
     * Update completeness for Product entity
     *
     * @param Entity $entity
     *
     * @return Entity
     */
    protected function runUpdateProductCompleteness(Entity $entity): Entity
    {
        echo '<pre>';
        print_r('123');
        die();

        return $entity;
    }

    /**
     * Get requireds
     *
     * @param string $entityName
     * @param bool   $isMultilang
     *
     * @return array
     */
    protected function getRequireds(string $entityName, bool $isMultilang = false): array
    {
        // prepare result
        $result = [];

        // get entity defs
        $entityDefs = $this->getContainer()->get('metadata')->get('entityDefs.' . $entityName . '.fields');

        foreach ($entityDefs as $name => $row) {
            if ($isMultilang) {
                if (!empty($row['required']) && !empty($row['isMultilang'])) {
                    $result[] = $name;
                }
            } else {
                if (!empty($row['required'])) {
                    $result[] = $name;
                }
            }
        }

        return $result;
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
                $languages[] = Util::toCamelCase(strtolower($locale), '_', true);
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

    /**
     * @param Entity $entity
     */
    protected function saveEntity(Entity $entity): void
    {
        $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
    }
}
