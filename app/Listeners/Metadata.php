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

namespace Completeness\Listeners;

use Espo\Core\Utils\Util;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Metadata class
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Metadata extends AbstractListener
{

    const CONFIG_IS_ACTIVE = [
        'type' => 'bool',
        'default' => false,
        'layoutFiltersDisabled' => true,
        'layoutMassUpdateDisabled' => true,
        'customizationDisabled' => true,
        'view' => 'completeness:views/fields/is-active'
    ];

    const CONFIG_COMPLETE_FIELDS = [
        'type' => 'varcharMultiLang',
        'view' => 'completeness:views/fields/completeness-varchar-multilang',
        'readOnly' => true,
        'default' => '0',
        "trim" => true,
        'layoutDetailDisabled' => true,
        'layoutFiltersDisabled' => true,
        'layoutMassUpdateDisabled' => true,
        'customizationDisabled' => true,
        'importDisabled' => true,
        'exportDisabled' => true,
        'advancedFilterDisabled' => true,
        'isCompleteness' => true
    ];

    const CONFIG_FIELD_CHANNELS_DATA = [
        'type' => 'jsonObject',
        'layoutDetailDisabled' => true,
        'layoutListDisabled' => true,
        "importDisabled" => true,
    ];

    /**
     * Modify
     *
     * @param Event $event
     */
    public function modify(Event $event): void
    {
        // get data
        $data = $event->getArgument('data');
        // inject complete
        $data = $this->addComplete($data);
        $data = $this->addDashlet($data);

        $event->setArgument('data', $data);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function addDashlet(array $data): array
    {
        if (!empty($data['completeness'])) {
            foreach ($data['completeness'] as $key => $item) {
                if (empty($data['dashlets'][$key]) && !empty($item['isDashlet'])) {
                    $data['dashlets'][$key] = $item;
                }
            }
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function addComplete(array $data): array
    {
        $config = $this->getContainer()->get('config');
        $languages = $config->get('inputLanguageList', []);

        foreach ($data['entityDefs'] as $entity => $row) {
            if (!empty($data['scopes'][$entity]['hasCompleteness'])) {
                $this->createMainCompleteFields($data, $entity);
                $this->createLangCompleteFields($data, $entity, $languages);
                $this->createIsActiveField($data, $entity);

                $data['entityDefs'][$entity]['fields']['channelCompleteness'] = self::CONFIG_FIELD_CHANNELS_DATA;
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $entity
     */
    protected function createMainCompleteFields(array &$data, string $entity)
    {
        $fieldsComplete = ['complete', 'completeTotal', 'completeGlobal'];

        foreach ($fieldsComplete as $field) {
            $data['entityDefs'][$entity]['fields'][$field] = self::CONFIG_COMPLETE_FIELDS;
        }
    }

    /**
     * @param array $data
     * @param string $entity
     * @param array $languages
     */
    protected function createLangCompleteFields(array &$data, string $entity, array $languages)
    {
        foreach ($languages as $language) {
            // prepare key
            $key = Util::toCamelCase('complete_' . strtolower($language));
            $data['entityDefs'][$entity]['fields'][$key] = self::CONFIG_COMPLETE_FIELDS;
        }
    }

    /**
     * @param array $data
     * @param string $entity
     */
    protected function createIsActiveField(array &$data, string $entity): void
    {
        if (!isset($data['entityDefs'][$entity]['fields']['isActive'])) {
            $data['entityDefs'][$entity]['fields']['isActive'] = self::CONFIG_IS_ACTIVE;
        } else {
            $data['entityDefs'][$entity]['fields']['isActive']['view'] = self::CONFIG_IS_ACTIVE['view'];
        }
    }
}
