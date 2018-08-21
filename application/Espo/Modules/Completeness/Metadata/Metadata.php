<?php
/**
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) Zinit Solutions GmbH
 *
 * This Software is the property of Zinit Solutions GmbH and is protected
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

namespace Espo\Modules\Completeness\Metadata;

use Espo\Core\Utils\Util;
use Espo\Modules\TreoCore\Metadata\AbstractMetadata;

/**
 * Metadata class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Metadata extends AbstractMetadata
{

    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    public function modify(array $data): array
    {
        // get config
        $config = $this->getContainer()->get('config');

        // get languages
        $languages = $config->get('inputLanguageList');
        if (empty($languages)) {
            $languages = [];
        }

        foreach ($data['entityDefs'] as $entity => $row) {
            if (!empty($data['scopes'][$entity]['hasCompleteness'])) {
                // set complete
                $data['entityDefs'][$entity]['fields']['complete'] = [
                    'type'                     => 'varcharMultiLang',
                    'view'                     => 'completeness:views/fields/completeness-varchar-multilang',
                    'readOnly'                 => true,
                    'default'                  => '0',
                    "trim"                     => true,
                    'layoutFiltersDisabled'    => true,
                    'layoutMassUpdateDisabled' => true,
                    'customizationDisabled'    => true,
                    'importDisabled'           => true,
                    'exportDisabled'           => true,
                    'isCompleteness'           => true
                ];

                foreach ($languages as $language) {
                    // prepare key
                    $key = Util::toCamelCase('complete_' . strtolower($language));

                    $data['entityDefs'][$entity]['fields'][$key] = [
                        'type'                     => 'varcharMultiLang',
                        'view'                     => 'completeness:views/fields/completeness-varchar-multilang',
                        'default'                  => '0',
                        'layoutListDisabled'       => false,
                        'layoutDetailDisabled'     => true,
                        'layoutFiltersDisabled'    => true,
                        'layoutMassUpdateDisabled' => true,
                        'customizationDisabled'    => true,
                        'importDisabled'           => true,
                        'exportDisabled'           => true,
                        'isCompleteness'           => true
                    ];
                }

                // add active
                if (!isset($data['entityDefs'][$entity]['fields']['isActive'])) {
                    $data['entityDefs'][$entity]['fields']['isActive'] = [
                        'type'                     => 'bool',
                        'default'                  => false,
                        'layoutFiltersDisabled'    => true,
                        'layoutMassUpdateDisabled' => true,
                        'customizationDisabled'    => true
                    ];
                }
            }
        }

        return $data;
    }
}
