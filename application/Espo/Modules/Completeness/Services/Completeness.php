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
     */
    public function runUpdateCompleteness(Entity $entity): void
    {
        switch ($entity->getEntityType()) {
            case 'Product':
            case 'ProductAttributeValue':
                $this->runUpdateProductCompleteness($entity);
                break;
            default:
                $this->runUpdateCommonCompleteness($entity);
                break;
        }
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
     * @param string $productId
     *
     * @return array
     */
    public function getChannelCompleteness(string $productId): array
    {
        // prepare result
        $result = ['total' => 0, 'list' => []];

        // get product
        if (empty($product = $this->getProduct($productId))) {
            return $result;
        }

        // get channels
        if (empty($channels = $product->get('channels')) || count($channels) < 1) {
            return $result;
        };

        // get requireds
        if (empty($requireds = array_merge($this->getRequireds('Product'), $this->getRequiredsAttributes($product)))) {
            return $result;
        }

        // prepare coefficient
        $coefficient = 100 / count($requireds);

        foreach ($channels as $channel) {
            // prepare complete
            $complete = 0;
            foreach ($requireds as $field) {
                if (!empty($product->get($field))) {
                    $complete += $coefficient;
                }
            }

            $result['list'][] = [
                'id'       => $channel->get('id'),
                'name'     => $channel->get('name'),
                'complete' => round($complete, 2)
            ];
        }
        $result['total'] = count($result['list']);

        return $result;
    }

    /**
     * Update completeness for any entity
     *
     * @param Entity $entity
     */
    protected function runUpdateCommonCompleteness(Entity $entity): void
    {
        // get entity name
        $entityName = $entity->getEntityType();

        // set complete
        $entity->set('complete', 100);
        foreach ($this->getLanguages() as $language) {
            $entity->set("complete{$language}", 100);
        }

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

            /**
             * For multilang fields
             */
            if ($this->getConfig()->get('isMultilangActive')
                && !empty($multilangRequireds = $this->getRequireds($entityName, true))) {
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
            }

            // checking activation
            if (!empty($entity->get('isActive')) && $complete < 100) {
                $entity->set('isActive', 0);
            }
        }

        // force save entity
        $this->saveEntity($entity);
    }

    /**
     * Update completeness for Product entity
     *
     * @param Entity $entity
     */
    protected function runUpdateProductCompleteness(Entity $entity): void
    {
        // prepare product
        $product = ($entity->getEntityType() == 'Product') ? $entity : $entity->get('product');

        // set complete
        $product->set('complete', 100);
        foreach ($this->getLanguages() as $language) {
            $product->set("complete{$language}", 100);
        }

        // get requireds
        $requireds = array_merge($this->getRequireds('Product'), $this->getRequiredsAttributes($product));

        if (!empty($requireds)) {
            // prepare coefficient
            $coefficient = 100 / count($requireds);

            // prepare complete
            $complete = 0;
            foreach ($requireds as $field) {
                if (!empty($product->get($field))) {
                    $complete += $coefficient;
                }
            }
            $product->set('complete', $complete);

            /**
             * For multilang fields
             */
            if ($this->getConfig()->get('isMultilangActive')) {
                // get requireds
                $multilangRequireds = array_merge(
                    $this->getRequireds('Product', true),
                    $this->getRequiredsAttributes($product, true)
                );

                // prepare coefficient
                $multilangCoefficient = 100 / count($multilangRequireds);

                foreach ($this->getLanguages() as $locale => $language) {
                    $multilangComplete = 0;
                    foreach ($multilangRequireds as $field) {
                        // get value
                        if (strpos($field, 'attr_') !== false) {
                            $value = $product->get("{$field}", ['locale' => $locale]);
                        } else {
                            $value = $product->get("{$field}{$language}");
                        }

                        if (!empty($value)) {
                            $multilangComplete += $multilangCoefficient;
                        }
                    }
                    $product->set("complete{$language}", $multilangComplete);
                }
            }
        }

        // set complete
        $product->set('complete', $complete);

        // checking activation
        if (!empty($product->get('isActive')) && $complete < 100) {
            $product->set('isActive', 0);
        }

        // force save product
        $this->saveEntity($product);
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
     * Get requireds attributes
     *
     * @param Entity $product
     * @param bool   $isMultilang
     *
     * @return array
     */
    protected function getRequiredsAttributes(Entity $product, bool $isMultilang = false): array
    {
        // prepare sql
        $sql = "SELECT DISTINCT pfa.attribute_id as attributeId
                FROM product_family_attribute_linker as pfa
                JOIN product_attribute_value as pav 
                  ON pav.product_family_attribute_id= pfa.id AND pav.deleted=0
                JOIN attribute as a 
                  ON a.id=pfa.attribute_id AND a.deleted=0
                WHERE
                    pfa.deleted=0
                AND pfa.is_required=1
                AND pav.product_id='" . $product->get('id') . "'";
        if ($isMultilang) {
            $sql .= " AND a.type IN ('varcharMultiLang','textMultiLang','enumMultiLang','multiEnumMultiLang','arrayMultiLang')";
        }
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        $attributes = $sth->fetchAll(\PDO::FETCH_ASSOC);

        // prepare result
        $result = [];
        if (count($attributes) > 0) {
            foreach ($attributes as $row) {
                $result[] = "attr_" . $row['attributeId'];
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

    /**
     * @param Entity $entity
     */
    protected function saveEntity(Entity $entity): void
    {
        $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
    }

    /**
     * @param string $productId
     *
     * @return Entity|null
     */
    protected function getProduct(string $productId): ?Entity
    {
        return $this->getEntityManager()->getEntity('Product', $productId);
    }
}
