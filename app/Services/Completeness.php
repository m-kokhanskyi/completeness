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
use Treo\Services\AbstractService;

/**
 * Completeness service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Completeness extends AbstractService
{
    protected $allFieldsComplete = [];
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
     *
     * @return array
     */
    public function runUpdateCompleteness(Entity $entity): array
    {
        switch ($entity->getEntityType()) {
            case 'Product':
            case 'ProductAttributeValue':
            case 'ProductFamilyAttribute':
                $result = $this->runUpdateProductCompleteness($entity);
                break;
            default:
                $result = $this->runUpdateCommonCompleteness($entity);
                break;
        }
        return $result;
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
     * Update completeness for any entity
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function runUpdateCommonCompleteness(Entity $entity): array
    {
        // get entity name
        $entityName = $entity->getEntityType();

        $requiredFields = $this->getRequiredFields($entityName);

        $completeness['complete'] = $this->calculationComplete($entity, $requiredFields);

        $completeness =  array_merge($completeness, $this->calculationCompleteMultiLang($entity));

        $completeness['completeTotal'] = $this->calculationTotalComplete($entity);

        $isActive = $this->updateActive($entity, $completeness['completeTotal']);

        $this->setFieldCompleteInEntity($entity, $completeness);

        $completeness['isActive'] = $isActive;

        return $completeness;
    }

    /**
     * Update completeness for Product entity
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function runUpdateProductCompleteness(Entity $entity): array
    {
        $product = ($entity->getEntityType() == 'Product') ? $entity : $entity->get('product');

        $globalFieldsRequired = $this->getRequiredAttrGlobal($product);
        $requiredFields = $this->getRequiredFields($entity->getEntityType());

        $channelCompleteness = $this->setChannelCompleteness($entity, $requiredFields);

        $completeness['complete'] = $this
            ->calculationComplete($product, array_merge($requiredFields, $globalFieldsRequired));

        $completeness['completeGlobal'] = $this->calculationCompleteGlobal($globalFieldsRequired, $product);

        $completeness = array_merge($completeness, $this->calculationCompleteMultiLang($product));

        $completeness['completeTotal'] = $this->calculationTotalComplete($product);

        $isActive = $this->updateActive($entity, $completeness['completeTotal']);

        $this->setFieldCompleteInEntity($entity, $completeness);

        $completeness['isActive'] = $isActive;
        $completeness['channelCompleteness'] = $channelCompleteness;

        return $completeness;
    }

    /**
     * @param Entity $entity
     * @param array $fields
     *
     * @return float
     */
    protected function calculationComplete(Entity $entity, array $fields): float
    {
        $complete = 100;

        if (!empty($fields)) {
            $this->allFieldsComplete = array_merge($this->allFieldsComplete, $fields);
            $complete = 0;
            $coefficient = 100 / count($fields);
            foreach ($fields as $field) {
                if (!$this->isEmpty($entity, $field)) {
                    $complete += $coefficient;
                }
            }
        }
        return (float)$complete;
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    protected function calculationCompleteMultiLang(Entity $entity): array
    {
        $completenessLang = [];

        if ($this->getConfig()->get('isMultilangActive')) {
            if ($entity->getEntityType() == 'Product') {
                $multiLangRequiredField = array_merge(
                    $this->getRequiredFields($entity->getEntityName(), true),
                    $this->getRequiredAttrGlobal($entity, true)
                );
            } else {
                $multiLangRequiredField =  $this->getRequiredFields($entity->getEntityName(), true);
            }

            // prepare coefficient
            $multilangCoefficient = 100 / count($multiLangRequiredField);

            foreach ($this->getLanguages() as $locale => $language) {
                $multilangComplete = 100;
                if (!empty($multiLangRequiredField)) {
                    $multilangComplete = 0;
                    foreach ($multiLangRequiredField as $field) {
                        if (!$this->isEmpty($entity, $field, $language)) {
                            $this->allFieldsComplete[] = $field . $language;
                            $multilangComplete += $multilangCoefficient;
                        }
                    }
                }
                $completenessLang['complete' . $language] = $multilangComplete;
            }
        }
        return $completenessLang;
    }

    /**
     * @param array $globalFieldsRequired
     * @param Entity $product
     *
     * @return float
     */
    protected function calculationCompleteGlobal(array $globalFieldsRequired, Entity $product): float
    {
        $completeGlobal = 100;
        if (!empty($globalFieldsRequired)) {
            $coefficientGlobalFields = 100 / count($globalFieldsRequired);
            $completeGlobal = 0;
            foreach ($globalFieldsRequired as $field) {
                if (!$this->isEmpty($product, $field)) {
                    $completeGlobal += $coefficientGlobalFields;
                }
            }
        }
        return (float)$completeGlobal;
    }

    /**
     * @param Entity $entity
     *
     * @return float
     */
    protected function calculationTotalComplete(Entity $entity): float
    {
        $totalComplete = 100;
        $fields = $this->getAllFieldComplete();

        if (!empty($fields)) {
            $coefficient = 100 / count($fields);
            $totalComplete = 0;
            foreach ($fields as $field) {
                if (!$this->isEmpty($entity, $field)) {
                    $totalComplete += $coefficient;
                }
            }
        }
        return (float)round($totalComplete, 2);
    }

    /**
     * Get required fields
     *
     * @param string $entityName
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredFields(string $entityName, bool $isMultilang = false): array
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
     * @param Entity $product
     * @param array $requiredFields
     *
     * @return array
     */
    protected function setChannelCompleteness(Entity $product, array $requiredFields): array
    {
        $result = [];
        // get channels
        $channels = $this->getChannels($product);
        if (empty($channels) || count($channels) < 1) {
            $product->set('channelCompleteness', $result);
        } else {
            $channelCompleteness = [];

            foreach ($channels as $channel) {
                $requiredAttrChannels = $this->getRequiredAttrChannels($product, $channel->get('id'));

                $this->allFieldsComplete = array_merge($this->allFieldsComplete, $requiredAttrChannels);

                $channelRequired = array_merge(
                    $requiredFields,
                    $requiredAttrChannels
                );

                $coefficient = 100 / count($channelRequired);
                $complete = !empty($channelRequired) ? 0 : 100;

                foreach ($channelRequired as $field) {
                    if (!$this->isEmpty($product, $field)) {
                        $complete += $coefficient;
                    }
                }

                $channelCompleteness[] = [
                    'id' => $channel->get('id'),
                    'name' => $channel->get('name'),
                    'complete' => round($complete, 2)
                ];
            }
            $result = ['total' => count($channelCompleteness), 'list' => $channelCompleteness];

            $product->set('channelCompleteness', $result);
        };

        return $result;
    }

    /**
     * Get required attributes Global
     *
     * @param Entity $product
     * @param bool $isMultilang
     *
     * @return array
     */
    protected function getRequiredAttrGlobal(Entity $product, bool $isMultilang = false): array
    {
        // prepare data
        $where = [
            'productId' => $product->get('id'),
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
            /** @var Entity $attribute */
            foreach ($attributes as $attribute) {
                if (!in_array($attribute->get('id'), $this->getExcludedAttributes($product))) {
                    $result[] = $attribute;
                }
            }
        }

        return $result;
    }

    /**
     * Get required attributes with scope Channel
     *
     * @param Entity $product
     * @param string $channelId
     *
     * @return array
     */
    protected function getRequiredAttrChannels(Entity $product, string $channelId)
    {
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->distinct()
            ->join(['productFamilyAttribute'])
            ->where([
                'productId' => $product->get('id'),
                'productFamilyAttribute.isRequired' => true
            ])
            ->find();

        // prepare result
        $result = [];

        if (count($attributes) > 0) {
            /** @var Entity $attribute */
            foreach ($attributes as $attribute) {
                if (!in_array($attribute->get('id'), $this->getExcludedAttributes($product))) {
                    if ($attribute->get('scope') == 'Global' && !isset($result[$attribute->get('attributeId')])) {
                        $result[$attribute->get('attributeId')] = $attribute;
                    } elseif ($attribute->get('scope') == 'Channel'
                        && in_array($channelId, array_column($attribute->get('channels')->toArray(), 'id'))) {
                        $result[$attribute->get('attributeId')] = $attribute;
                    }
                }
            }
        }

        return array_values($result);
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
     * @param mixed $value
     * @param string $language
     *
     * @return bool
     */
    private function isEmpty(Entity $entity, $value, string $language = ''): bool
    {
        $result = true;

        if (is_string($value) && !empty($entity->get($value . $language))) {
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

    /**
     * @param Entity $product
     *
     * @return EntityCollection
     */
    protected function getChannels(Entity $product): EntityCollection
    {
        if ($product->get('type') == 'productVariant'
            && !in_array('channels', $product->get('data')->customRelations)) {
            $result = $product->get('configurableProduct')->get('channels');
        } else {
            $result = $product->get('channels');
        }

        return $result;
    }

    /**
     * @param Entity $product
     *
     * @return array
     */
    protected function getExcludedAttributes(Entity $product): array
    {
        $result = [];

        if ($product->get('type') == 'configurableProduct') {
            $variants = $product->get('productVariants');

            if (count($variants) > 0) {
                /** @var Entity $variant */
                foreach ($variants as $variant) {
                    $result = array_merge($result, array_column($variant->get('data')->attributes, 'id'));
                }

                $result = array_unique($result);
            }
        }

        return $result;
    }

    /**
     * @param Entity $entity
     * @param $complete
     *
     * @return bool
     */
    protected function updateActive(Entity $entity, $complete): bool
    {
        $result = $entity->get('isActive');
        if (!empty($result) && round($complete) < 100) {
            $entity->set('isActive', 0);
            $result = 0;
        }
        return !empty($result);
    }

    /**
     * @param Entity $entity
     * @param array $completeness
     */
    protected function setFieldCompleteInEntity(Entity $entity, array $completeness): void
    {
        // update db
        foreach ($completeness as $field => $complete) {
            $entity->set($field, (string)round($complete, 2));
        }

        $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
    }

    /**
     * @return array
     */
    protected function getAllFieldComplete(): array
    {
        return array_unique($this->allFieldsComplete);
    }
}
