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
use Espo\Core\Exceptions\Error;

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

     * @return array
     * @throws Error
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
        if (empty($requireds = array_merge($this->getRequireds($product), $this->getRequiredsAttributes($product)))) {
            return $result;
        }

        foreach ($channels as $channel) {
            // get requireds for channel
            $channelRequireds = array_merge(
                $requireds, $this->getRequiredsScopeChannelAttributes($product, $channel->get('id'))
            );

            // prepare coefficient
            $coefficient = 100 / count($channelRequireds);
            // prepare complete
            $complete = 0;
            foreach ($channelRequireds as $required) {
                if (!empty($required)) {
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
        // set complete
        $entity->set('complete', 100);
        foreach ($this->getLanguages() as $language) {
            $entity->set("complete{$language}", 100);
        }

        if (!empty($requireds = $this->getRequireds($entity))) {
            // prepare coefficient
            $coefficient = 100 / count($requireds);

            // prepare complete
            $complete = 0;
            foreach ($requireds as $value) {
                if ($value) {
                    $complete += $coefficient;
                }
            }

            /**
             * For multilang fields
             */
            if ($this->getConfig()->get('isMultilangActive')
                && !empty($multilangRequireds = $this->getRequireds($entity, true))) {
                foreach ($this->getLanguages() as $language) {
                    $multilangComplete = 0;

                    if (isset($multilangRequireds[$language])) {
                        // prepare coefficient
                        $multilangCoefficient = 100 / count($multilangRequireds[$language]);

                        foreach ($multilangRequireds[$language] as $value) {
                            if (!empty($value)) {
                                $multilangComplete += $multilangCoefficient;
                            }
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
        $requireds = array_merge($this->getRequireds($product), $this->getRequiredsAttributes($product));

        if (!empty($requireds)) {
            // prepare coefficient
            $coefficient = 100 / count($requireds);

            // prepare complete
            $complete = 0;
            foreach ($requireds as $value) {
                if (!empty($value)) {
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
                    $this->getRequireds($product, true),
                    $this->getRequiredsAttributes($product, true)
                );

                foreach ($this->getLanguages() as $language) {
                    $multilangComplete = 0;

                    if (isset($multilangRequireds[$language])) {
                        // prepare coefficient
                        $multilangCoefficient = 100 / count($multilangRequireds[$language]);

                        foreach ($multilangRequireds[$language] as $value) {
                            if (!empty($value)) {
                                $multilangComplete += $multilangCoefficient;
                            }
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
     * @param Entity $entity
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequireds(Entity $entity, bool $isMultilang = false): array
    {
        // prepare result
        $result = [];

        // get entity defs
        $entityDefs = $this->getContainer()->get('metadata')->get('entityDefs.' . $entity->getEntityName() . '.fields');

        foreach ($entityDefs as $name => $row) {
            if ($isMultilang) {
                if (!empty($row['required']) && !empty($row['isMultilang'])) {
                    foreach ($this->getLanguages() as $language) {
                        $result[$language][] = $entity->get($name . $language);
                    }
                }
            } elseif (!empty($row['required'])) {
                $result[] = $entity->get($name);
            }
        }

        return $result;
    }

    /**
     * Get requireds attributes
     *
     * @param Entity $product
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredsAttributes(Entity $product, bool $isMultilang = false): array
    {
        // prepare data
        $where = [
            'productId' => $product->get('id'),
            'productFamilyAttribute.isRequired' => true,
            'productFamilyAttribute.scope' => 'Global'
        ];

        if ($isMultilang) {
            $where['attribute.type'] = array_keys($this->getConfig()->get('modules.multilangFields'));
        }

        // get required scope Global attributes
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->distinct()
            ->join(['productFamilyAttribute', 'attribute'])
            ->where($where)
            ->find();

        // prepare result
        $result = [];

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                if ($isMultilang) {
                    foreach ($this->getLanguages() as $language) {
                        $result[$language][] = $attribute->get('value' . $language);
                    }
                } else {
                    $result[] = $attribute->get('value');
                }
            }
        }

        return $result;
    }

    /**
     * Get required attributes with scope Channel
     *
     * @param Entity $product
     * @param string|null $channelId
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredsScopeChannelAttributes(Entity $product, string $channelId = null, bool $isMultilang = false)
    {
        // prepare data
        $joins = ['productFamilyAttribute', 'attribute'];

        $where = [
            'productId' => $product->get('id'),
            'productFamilyAttribute.isRequired' => true,
            'productFamilyAttribute.scope' => 'Channel'
        ];

        if (!empty($channelId)) {
            $joins[] = 'channels';
            $where['channels.id'] = $channelId;
        }

        if ($isMultilang) {
            $where['attribute.type'] = array_keys($this->getConfig()->get('modules.multilangFields'));
        }

        // get required scope Channel attributes
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->distinct()
            ->join($joins)
            ->where($where)
            ->find();

        // prepare result
        $result = [];

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                if ($isMultilang) {
                    foreach ($this->getLanguages() as $language) {
                        $result[$language][] = $attribute->get('value' . $language);
                    }
                } else {
                    $result[] = $attribute->get('value');
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
     * @throws Error
     */
    protected function getProduct(string $productId): ?Entity
    {
        return $this->getEntityManager()->getEntity('Product', $productId);
    }
}
