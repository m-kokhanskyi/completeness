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
use Espo\ORM\IEntity;
use Espo\ORM\EntityCollection;
use Treo\Core\Container;
use Treo\Core\Utils\Condition\Condition;
use Treo\Services\AbstractService;

/**
 * Class CommonCompleteness
 * @package Completeness\Services
 */
class CommonCompleteness extends AbstractService implements CompletenessInterface
{
    public const CONFIG_COMPLETE_FIELDS = [
        'type' => 'float',
        'view' => 'completeness:views/fields/completeness-float',
        'readOnly' => true,
        'default' => 0,
        'layoutDetailDisabled' => true,
        'layoutFiltersDisabled' => false,
        'layoutMassUpdateDisabled' => true,
        'customizationDisabled' => true,
        'importDisabled' => true,
        'exportDisabled' => true,
        'advancedFilterDisabled' => true,
        'isCompleteness' => true,
        'isMultilang' => false
    ];

    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @param Entity $entity
     * @return array
     * @throws Error
     */
    public function calculate(IEntity $entity): array
    {
        $items = $this->prepareRequiredFields($entity);

        $completeness['complete'] = $this->calculationLocalComplete($items['localComplete']);
        $completeness = array_merge($completeness, $this->calculationCompleteMultiLang($items['multiLang']));
        $completeness['completeTotal'] = $this->calculationTotalComplete($items['completeTotal']);

        $this->setFieldsCompletenessInEntity($completeness, $entity);

        return $completeness;
    }
    /**
     * @param IEntity $entity
     * @return void
     */
    public function saveEntity(IEntity $entity): void
    {
        $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
    }

    /**
     * @param string $entityName
     */
    public function afterDisable(string $entityName): void {}

    /**
     * @param Container $container
     * @param string $entity
     * @param bool $value
     */
    public static function setHasCompleteness(Container $container, string $entity, bool $value): void
    {
        //set hasCompleteness
        $scope['hasCompleteness'] = $value;
        $container->get('metadata')->set('scopes', $entity, $scope);

        // save
        $container->get('metadata')->save();

        $filters = json_decode($container->get('layout')->get($entity, 'filters', []), true);
        if ($value && !in_array('complete', $filters, true)) {
            $filters[] = 'complete';
            $container->get('layout')->set($filters, $entity, 'filters');
            $container->get('layout')->save();
        }
    }

    /**
     * @return array
     */
    public static function getCompleteField(): array
    {
        $fieldsComplete = [1 => 'completeTotal', 4 => 'complete'];
        $fields = [];
        foreach ($fieldsComplete as $k => $field) {
            $defs = self::CONFIG_COMPLETE_FIELDS;
            if ($field === 'complete') {
                $defs['isMultilang'] = true;
            }
            $defs['sortOrder'] = $k;
            $fields[$field] = $defs;
        }

        return $fields;
    }

    /**
     * Prepare required fields and check on empty
     * @param IEntity $entity
     * @return array
     * @throws Error
     */
    protected function prepareRequiredFields(IEntity $entity): array
    {
        $entityDefs = $this
            ->getContainer()
            ->get('metadata')
            ->get(['entityDefs', $entity->getEntityType(), 'fields'], []);

        $result = [
            'fields' => [],
            'multiLang' => [],
            'localComplete' => [],
            'completeTotal' => []
        ];

        foreach ($entityDefs as $name => $data) {
            if ($this->isRequiredField($name, $data, $entity)) {
                if (!empty($data['multilangLocale'])) {
                    $isEmpty = $this->isEmpty($name, $entity);
                    $item = ['name' => $name, 'isEmpty' => $isEmpty, 'isMultiLang' => true];

                    $result['fields'][] = $item;
                    $result['multiLang'][$data['multilangLocale']][] = $item;
                    $result['completeTotal'] = $isEmpty;
                } else {
                    $isEmpty = $this->isEmpty($name, $entity);
                    $item = ['name' => $name, 'isEmpty' => $isEmpty];

                    $result['fields'][] = $item;
                    $result['localComplete'][] = $item;
                    $result['completeTotal'][] = $isEmpty;
                }
            }
        }
        return $result;
    }

    /**
     * @param array $fieldsLocalComplete
     * @return float
     */
    protected function calculationLocalComplete(array $fieldsLocalComplete): float
    {
        return $this->commonCalculationComplete($fieldsLocalComplete);
    }

    /**
     * @param array $fields
     * @return array
     */
    protected function calculationCompleteMultiLang(array $fields): array
    {
        $completenessLang = [];
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getLanguages() as $locale => $language) {
                $name = 'complete' . $language;
                $completenessLang[$name] = $this->commonCalculationComplete($fields[$locale]);
            }
        }
        return $completenessLang;
    }

    /**
     * @param array $fields
     * @return float
     */
    protected function calculationTotalComplete(array $fields): float
    {
        $totalComplete = 100;

        if (!empty($fields)) {
            $coefficient = 100 / count($fields);
            $totalComplete = 0;
            foreach ($fields as $isEmpty) {
                if (empty($isEmpty)) {
                    $totalComplete += $coefficient;
                }
            }
        }
        return (float)round($totalComplete, 2);
    }

    /**
     * @param mixed $value
     * @param IEntity $entity
     * @return bool
     */
    protected function isEmpty($value, IEntity $entity): bool
    {
        $result = true;
        if (is_string($value) && !empty($valueCurrent = $entity->get($value))) {
            if ($valueCurrent instanceof EntityCollection) {
                $result = (bool)$valueCurrent->count();
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param array $completeness
     * @param IEntity $entity
     */
    protected function setFieldsCompletenessInEntity(array $completeness, IEntity $entity): void
    {
        foreach ($completeness as $field => $complete) {
            if ($entity->has($field)) {
                $entity->set($field, $complete);
            }
        }
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
     * @param string $field
     * @param array $data
     * @param IEntity $entity
     *
     * @return bool
     * @throws Error
     */
    protected function isRequiredField(string $field, array $data, IEntity $entity): bool
    {
        $condition = $this
            ->getContainer()
            ->get('metadata')
            ->get("clientDefs.{$entity->getEntityName()}.dynamicLogic.fields.$field.required.conditionGroup", []);

        return !empty($data['required'])
            || (!empty($condition) && Condition::isCheck(Condition::prepare($entity, $condition)));
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
}
