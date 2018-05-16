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

namespace Espo\Modules\Completeness\Listeners;

use Espo\Modules\TreoCore\Listeners\AbstractListener;

/**
 * EntityManager listener
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EntityManager extends AbstractListener
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionUpdateEntity(array $data): array
    {
        // prepare data
        $postData = get_object_vars($data['data']);
        $scope = $postData['name'];
        $hasCompleteness = !empty($postData['hasCompleteness']);

        if ($hasCompleteness !== $this->getHasCompleteness($scope)) {
            // update scope
            $this->setHasCompleteness($scope, $hasCompleteness);

            // rebuild DB
            $this
                ->getContainer()
                ->get('dataManager')
                ->rebuild();

            if ($hasCompleteness) {
                // recalc complete param
                $this
                    ->getContainer()
                    ->get('serviceFactory')
                    ->create('Completeness')
                    ->recalcEntity($scope, true);
            }
        }

        return $data;
    }

    /**
     * Set hasCompleteness param
     *
     * @param string $scope
     * @param bool   $value
     */
    protected function setHasCompleteness(string $scope, bool $value): void
    {
        // prepare data
        $data = $this
            ->getContainer()
            ->get('metadata')
            ->get("scopes.{$scope}");
        $data['hasCompleteness'] = $value;

        $this
            ->getContainer()
            ->get('metadata')
            ->set("scopes", $scope, $data);

        // save
        $this->getContainer()->get('metadata')->save();
    }

    /**
     * Get hasCompleteness param
     *
     * @param string $scope
     *
     * @return bool
     */
    protected function getHasCompleteness(string $scope): bool
    {
        $result = $this
            ->getContainer()
            ->get('metadata')
            ->get("scopes.{$scope}.hasCompleteness");

        return !empty($result);
    }
}
