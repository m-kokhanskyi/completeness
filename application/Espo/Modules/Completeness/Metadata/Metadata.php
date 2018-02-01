<?php
declare(strict_types = 1);

namespace Espo\Modules\Completeness\Metadata;

use Espo\Core\Utils\Util;
use Espo\Modules\TreoCrm\Metadata\AbstractMetadata;

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
        $languages = [];
        if (!empty($config->get('isMultilangActive'))) {
            $languages = $config->get('inputLanguageList');
        }

        foreach ($data['entityDefs'] as $entity => $row) {
            if (!empty($data['scopes'][$entity]['hasCompleteness'])) {
                // add complete
                if (!isset($data['entityDefs'][$entity]['fields']['complete'])) {
                    $data['entityDefs'][$entity]['fields']['complete'] = [
                        'type'                     => 'completenessVarcharMultiLang',
                        'readOnly'                 => true,
                        'default'                  => '0',
                        "trim"                     => true,
                        'layoutFiltersDisabled'    => true,
                        'layoutMassUpdateDisabled' => true,
                        'customizationDisabled'    => true
                    ];

                    foreach ($languages as $language) {
                        // prepare key
                        $key = Util::toCamelCase('complete_'.strtolower($language));

                        $data['entityDefs'][$entity]['fields'][$key] = [
                            'type'                     => 'varchar',
                            'default'                  => '0',
                            'layoutListDisabled'       => true,
                            'layoutDetailDisabled'     => true,
                            'layoutFiltersDisabled'    => true,
                            'layoutMassUpdateDisabled' => true,
                            'customizationDisabled'    => true
                        ];
                    }
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
