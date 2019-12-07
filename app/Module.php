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

namespace Completeness;

use Completeness\Services\CommonCompleteness;
use Completeness\Services\CompletenessInterface as ICompleteness;
use Espo\Core\Utils\Json;
use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Module extends AbstractModule
{
    const CONFIG_IS_ACTIVE = [
        'type' => 'bool',
        'default' => false,
        'layoutFiltersDisabled' => true,
        'layoutMassUpdateDisabled' => true,
        'customizationDisabled' => true,
        'view' => 'completeness:views/fields/is-active'
    ];

    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 5115;
    }

    /**
     * @inheritDoc
     */
    public function loadMetadata(\stdClass &$data)
    {
        parent::loadMetadata($data);

        $result = Json::decode(Json::encode($data), true);

        $result = $this->addComplete($result);

        $data = Json::decode(Json::encode($result));
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function addComplete(array $data): array
    {
        foreach ($data['entityDefs'] as $entity => $row) {
            if (!empty($data['scopes'][$entity]['hasCompleteness']) && !empty($data['scopes'][$entity]['entity'])) {
                /** @var ICompleteness $service */
                $service = CommonCompleteness::class;
                if (!empty($class = $data['scopes'][$entity]['completeness']['service'])
                        && class_exists($class) && new $class instanceof ICompleteness) {
                    $service = $class;
                }

                $this->createCompleteFields($data, $entity, $service::getCompleteField());
                $this->createIsActiveField($data, $entity);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $entity
     */
    protected function createCompleteFields(array &$data, string $entity, array $fields)
    {
        $data['entityDefs'][$entity]['fields'] = array_merge($data['entityDefs'][$entity]['fields'], $fields);
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
