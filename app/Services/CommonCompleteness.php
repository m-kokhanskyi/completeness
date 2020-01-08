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
use Espo\ORM\EntityCollection;
use Treo\Core\Container;
use Treo\Core\Utils\Condition\Condition;
use Treo\Services\AbstractService;

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
     * @var array
     */
    protected $itemsForTotalComplete = [];

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @return array
     * @throws Error
     */
    public function calculate(): array
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
     * @param Entity $entity
     */
    public function setEntity(Entity $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return void
     */
    public function saveEntity(): void
    {
        $this->getEntityManager()->saveEntity($this->entity, ['skipAll' => true]);
    }

    /**
     * @param Container $container
     * @param string $scope
     * @param bool $value
     */
    public static function setHasCompleteness(Container $container, string $scope, bool $value): void
    {
        // prepare data
        $data = $container->get('metadata')->get("scopes.{$scope}");
        //set hasCompleteness
        $data['hasCompleteness'] = $value;

        $container->get('metadata')->set("scopes", $scope, $data);

        // save
        $container->get('metadata')->save();

        $filters = json_decode($container->get('layout')->get($scope, 'filters'), true);
        if ($value && !in_array('complete', $filters, true)) {
            $filters[] = 'complete';
            $container->get('layout')->set($filters, $scope, 'filters');
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
     * @throws Error
     */
    protected function prepareRequiredFields(): void
    {
        $entityDefs = $this
            ->getContainer()
            ->get('metadata')
            ->get('entityDefs.' . $this->entity->getEntityType() . '.fields');

        foreach ($entityDefs as $name => $data) {
            if ($this->isRequiredField($name, $data)) {
                if (!empty($data['multilangLocale'])) {
                    $isEmpty = $this->isEmpty($name);
                    $item = ['name' => $name, 'isEmpty' => $isEmpty, 'isMultiLang' => true];

                    $this->items['fields'][] = $item;
                    $this->items['multiLang'][$data['multilangLocale']][] = $item;
                    $this->itemsForTotalComplete[] = $isEmpty;
                } else {
                    $isEmpty = $this->isEmpty($name);
                    $item = ['name' => $name, 'isEmpty' => $isEmpty];

                    $this->items['fields'][] = $item;
                    $this->items['localComplete'][] = $item;
                    $this->itemsForTotalComplete[] = $isEmpty;
                }
            }
        }
    }

    /**
     * @return float
     */
    protected function calculationLocalComplete(): float
    {
        return $this->commonCalculationComplete($this->getItem('localComplete'));
    }

    /**
     * @return array
     */
    protected function calculationCompleteMultiLang(): array
    {
        $completenessLang = [];
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getLanguages() as $locale => $language) {
                $name = 'complete' . $language;
                $completenessLang[$name] = $this->commonCalculationComplete($this->getItem('multiLang')[$locale]);
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

        if (!empty($this->itemsForTotalComplete)) {
            $coefficient = 100 / count($this->itemsForTotalComplete);
            $totalComplete = 0;
            foreach ($this->itemsForTotalComplete as $isEmpty) {
                if (empty($isEmpty)) {
                    $totalComplete += $coefficient;
                }
            }
        }
        return (float)round($totalComplete, 2);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty($value): bool
    {
        $result = true;
        if (is_string($value) && !empty($valueCurrent = $this->entity->get($value))) {
            if ($valueCurrent instanceof EntityCollection) {
                $result = (bool)$valueCurrent->count();
            } else {
                $result = false;
            }
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
     * @param string $name
     * @return array
     */
    protected function getItem(string $name): array
    {
        return (array)$this->items[$name];
    }

    /**
     * @param string $field
     * @param array $data
     * @return bool
     * @throws Error
     */
    protected function isRequiredField(string $field, array $data): bool
    {
        $condition = $this
            ->getContainer()
            ->get('metadata')
            ->get("clientDefs.{$this->entity->getEntityName()}.dynamicLogic.fields.$field.required.conditionGroup", []);

        return !empty($data['required'])
            || (!empty($condition) && Condition::isCheck(Condition::prepare($this->entity, $condition)));
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
