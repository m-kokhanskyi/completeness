<?php
declare(strict_types = 1);

namespace Espo\Modules\Completeness\Services;

use Espo\Core\Services\Base;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Core\Exceptions;
use Espo\Modules\TreoCore\Core\Utils\Metadata;

/**
 * Completeness service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Completeness extends Base
{

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        // call parent
        parent::__construct(...$args);

        /**
         * Dependencies
         */
        $this->addDependency('metadata');
        $this->addDependency('language');
    }

    /**
     * Update completeness
     *
     * @param Entity $entity
     * @param bool $showException
     *
     * @return Entity
     */
    public function updateCompleteness(Entity $entity, bool $showException = true): Entity
    {
        // get entity name
        $entityName = $this->getEntityName($entity);

        if ($this->hasCompleteness($entityName) && !empty($requireds = $this->getRequireds($entityName))) {
            // prepare coefficient
            $coefficient = 100 / count($requireds);

            // prepare comlete
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
                            if (!empty($entity->get(Util::toCamelCase($field.'_'.strtolower($language))))) {
                                $multilangComplete += $multilangCoefficient;
                            }
                        }
                        $entity->set(Util::toCamelCase('complete_'.strtolower($language)), $multilangComplete);
                    }
                } else {
                    foreach ($this->getLanguages() as $language) {
                        $entity->set(Util::toCamelCase('complete_'.strtolower($language)), 100);
                    }
                }
            }

            // checking activation
            if (!empty($entity->get('isActive')) && $complete < 100) {
                if ($showException) {
                    throw new Exceptions\Error($this->translate('activationFailed'));
                } else {
                    $entity->set('isActive', 0);
                }
            }
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
        // get entities
        $entities = $this->getEntityManager()->getRepository($entityName)->find();
        if (count($entities) > 0) {
            foreach ($entities as $entity) {
                // update completeness
                $entity = $this->updateCompleteness($entity, false);

                // save entity
                $this->getEntityManager()->saveEntity($entity);
            }
        }
    }

    /**
     * Get entity name
     *
     * @param Entity $entity
     *
     * @return string
     */
    protected function getEntityName(Entity $entity): string
    {
        return array_pop(explode("\\", get_class($entity)));
    }

    /**
     * Is entity has completeness?
     *
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasCompleteness(string $entityName): bool
    {
        return !empty($this->getMetadata()->get('scopes.'.$entityName.'.hasCompleteness'));
    }

    /**
     * Get requireds
     *
     * @param string $entityName
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequireds(string $entityName, bool $isMultilang = false): array
    {
        // prepare result
        $result = [];

        // get entity defs
        $entityDefs = $this->getMetadata()->get('entityDefs.'.$entityName.'.fields');

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
     * Get metadata
     *
     * @return Metadata
     */
    protected function getMetadata()
    {
        return $this->getInjection('metadata');
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
            $languages = $this->getConfig()->get('inputLanguageList');
        }

        return $languages;
    }

    /**
     * Translate field
     *
     * @param string $key
     *
     * @return string
     */
    protected function translate(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'Completeness');
    }
}
