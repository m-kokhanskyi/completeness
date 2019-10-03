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
        $event->setArgument('data', $data);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function addComplete(array $data): array
    {
        // get config
        $config = $this->getContainer()->get('config');

        // get languages
        $languages = $config->get('inputLanguageList');
        if (empty($languages)) {
            $languages = [];
        }

        $fieldsComplete = [
            'complete',
            'completeTotal',
            'completeGlobal'
        ];

        foreach ($data['entityDefs'] as $entity => $row) {
            if (!empty($data['scopes'][$entity]['hasCompleteness'])) {
                //create main fields for complete
                foreach ($fieldsComplete as $field) {
                    $data['entityDefs'][$entity]['fields'][$field] = $this->getCompleteConfigField();
                }
                //create lang field for complete
                foreach ($languages as $language) {
                    // prepare key
                    $key = Util::toCamelCase('complete_' . strtolower($language));
                    $data['entityDefs'][$entity]['fields'][$key] = $this->getCompleteConfigField();
                }

                // add active
                if (!isset($data['entityDefs'][$entity]['fields']['isActive'])) {
                    $data['entityDefs'][$entity]['fields']['isActive'] = [
                        'type' => 'bool',
                        'default' => false,
                        'layoutFiltersDisabled' => true,
                        'layoutMassUpdateDisabled' => true,
                        'customizationDisabled' => true,
                        'view' => 'completeness:views/fields/is-active'
                    ];
                } else {
                    $data['entityDefs'][$entity]['fields']['isActive']['view'] = 'completeness:views/fields/is-active';
                }
                $data['entityDefs'][$entity]['fields']['channelCompleteness'] = $this->getMetaDataFieldChannelsData();
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getMetaDataFieldChannelsData(): array
    {
        return [
            'type' => 'jsonObject',
            'layoutDetailDisabled' => true,
            'layoutListDisabled' => true,
            "importDisabled" => true,
            'layoutDetailDisabled' => true,
            'importDisabled' => true,
        ];
    }

    /**
     * @return array
     */
    protected function getCompleteConfigField(): array
    {
        return [
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
    }
}
