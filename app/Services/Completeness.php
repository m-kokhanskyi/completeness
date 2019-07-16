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

use Espo\Core\Utils\Json;
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
     * @var array
     */
    protected $multiLangFields = [
        'varcharMultiLang',
        'textMultiLang',
        'enumMultiLang',
        'multiEnumMultiLang',
        'arrayMultiLang',
        'wysiwygMultiLang'
    ];

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
        if (empty($requireds = $this->getRequireds('Product'))) {
            return $result;
        }

        foreach ($channels as $channel) {
            $channelRequired = array_merge(
                $requireds,
                $this->getRequiredsScopeChannelAttributes($productId, $channel->get('id'))
            );

            // prepare coefficient
            $coefficient = 100 / count($channelRequired);

            // prepare complete
            $complete = 0;
            foreach ($channelRequired as $field) {
                if (!$this->isEmpty($product, $field)) {
                    $complete += $coefficient;
                }
            }

            $result['list'][] = [
                'id' => $channel->get('id'),
                'name' => $channel->get('name'),
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

        // prepare entity id
        $entityId = $entity->get('id');

        // prepare table name
        $table = Util::camelCaseToUnderscore($entity->getEntityName());

        $completeness['complete'] = 100;

        foreach ($this->getLanguages() as $locale => $language) {
            $completeness['complete_' . strtolower($locale)] = 100;
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
            $completeness['complete'] = $complete;

            /**
             * For multilang fields
             */
            if ($this->getConfig()->get('isMultilangActive')
                && !empty($multilangRequireds = $this->getRequireds($entityName, true))) {
                // prepare coefficient
                $multilangCoefficient = 100 / count($multilangRequireds);

                foreach ($this->getLanguages() as $locale => $language) {
                    $multilangComplete = 0;
                    foreach ($multilangRequireds as $field) {
                        if (!empty($entity->get("{$field}{$language}"))) {
                            $multilangComplete += $multilangCoefficient;
                        }
                    }
                    $completeness['complete_' . strtolower($locale)] = $multilangComplete;
                }
            }

        }

        // prepare sql
        $sql = '';

        // update activation
        if (!empty($entity->get('isActive')) && $completeness['complete'] < 100) {
            $sql .= "UPDATE $table SET is_active=0 WHERE id='{$entityId}';";
        }

        // update db
        foreach ($completeness as $field => $complete) {
            $sql .= "UPDATE $table SET {$field}='" . round($complete, 2) . "' WHERE id='{$entityId}';";
        }

        if (!empty($sql)) {
            $this->execute($sql);
        }
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

        // prepare productId
        $productId = (string)$product->get('id');

        // prepare complete
        $complete = 0;

        // set complete
        $completeness['complete'] = 100;
        foreach ($this->getLanguages() as $locale => $language) {
            $completeness['complete_' . strtolower($locale)] = 100;
        }

        // get requireds
        $requireds = array_merge($this->getRequireds('Product'), $this->getRequiredsAttributes($productId));

        if (!empty($requireds)) {
            // prepare coefficient
            $coefficient = 100 / count($requireds);

            foreach ($requireds as $field) {
                if (!$this->isEmpty($product, $field)) {
                    $complete += $coefficient;
                }
            }
            $completeness['complete'] = $complete;

            /**
             * For multilang fields
             */
            if ($this->getConfig()->get('isMultilangActive')) {
                // get requireds
                $multilangRequireds = array_merge(
                    $this->getRequireds('Product', true),
                    $this->getRequiredsAttributes($productId, true)
                );

                // prepare coefficient
                $multilangCoefficient = 100 / count($multilangRequireds);

                foreach ($this->getLanguages() as $locale => $language) {
                    $multilangComplete = 0;
                    foreach ($multilangRequireds as $field) {
                        if (!$this->isEmpty($product, $field, $language)) {
                            $multilangComplete += $multilangCoefficient;
                        }
                    }
                    $completeness['complete_' . strtolower($locale)] = $multilangComplete;
                }
            }
        }

        // prepare sql
        $sql = '';

        // update activation
        if (!empty($product->get('isActive')) && $completeness['complete'] < 100) {
            $sql .= "UPDATE product SET is_active=0 WHERE id='{$productId}';";
        }

        // update db
        foreach ($completeness as $field => $complete) {
            $sql .= "UPDATE product SET {$field}='" . round($complete, 2) . "' WHERE id='{$productId}';";
        }

        if (!empty($sql)) {
            $this->execute($sql);
        }
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
     * @param string $productId
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredsAttributes(string $productId, bool $isMultilang = false): array
    {
        return array_merge(
            $this->getRequiredsScopeGlobalAttributes($productId, $isMultilang),
            $this->getRequiredsScopeChannelAttributes($productId, null, $isMultilang)
        );
    }

    /**
     * Get required attributes
     *
     * @param string $productId
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredsScopeGlobalAttributes(string $productId, bool $isMultilang = false): array
    {
        // prepare data
        $where = [
            'productId' => $productId,
            'productFamilyAttribute.isRequired' => true,
            'productFamilyAttribute.scope' => 'Global'
        ];

        if ($isMultilang) {
            $where['attribute.type'] = $this->multiLangFields;
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
                $result[] = $attribute;
            }
        }

        return $result;
    }

    /**
     * Get required attributes with scope Channel
     *
     * @param string $productId
     * @param string|null $channelId
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredsScopeChannelAttributes(
        string $productId,
        string $channelId = null,
        bool $isMultilang = false
    ) {
        $where = [
            'productId' => $productId,
            'productFamilyAttribute.isRequired' => true,
            'productFamilyAttribute.scope' => 'Channel'
        ];

        if (!empty($channelId)) {
            $where['channels.id'] = $channelId;
        }

        if ($isMultilang) {
            $where['attribute.type'] = $this->multiLangFields;
        }

        // get required scope Channel attributes
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->distinct()
            ->join(['productFamilyAttribute', 'attribute', 'channels'])
            ->where($where)
            ->find();

        // prepare result
        $result = [];

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                $result[] = $attribute;
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

    /**
     * @param string $sql
     */
    private function execute(string $sql): void
    {
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
    }

    /**
     * @param Entity $entity
     * @param mixed $value
     * @param string $language
     *
     * @return bool
     */
    private function isEmpty(Entity $entity, $value, string $language = ''): bool
    {
        $result = true;

        if ((is_string($value) && !empty($entity->get($value . $language)))) {
            $result = false;
        } elseif ($value instanceof Entity) {
            $type = $value->get('attribute')->get('type');

            if (in_array($type, ['array', 'arrayMultiLang', 'multiEnum', 'multiEnumMultiLang'])) {
                $attributeValue = Json::decode($value->get('value' . $language), true);
            } else {
                $attributeValue = $value->get('value' . $language);
            }

            if (!empty($attributeValue)) {
                $result = false;
            }
        }

        return $result;
    }
}
