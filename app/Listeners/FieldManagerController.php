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

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class EntityManagerController
 *
 * @author r.ratsun@treolabs.com
 */
class FieldManagerController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterPostActionCreate(Event $event): void
    {
        $scope = $event->getArgument('params')['scope'];
        if ($event->getArgument('data')->required && $this->getHasCompleteness($scope)) {
            $this->recalcEntities($scope);
        }
    }

    /**
     * @param Event $event
     */
    public function afterPatchActionUpdate(Event $event): void
    {
        $scope = $event->getArgument('params')['scope'];
        if ($this->getHasCompleteness($scope)) {
            $this->recalcEntities($scope);
        };
    }

    /**
     * @param Event $event
     */
    public function afterPutActionUpdate(Event $event): void
    {
        $scope = $event->getArgument('params')['scope'];
        if ($this->getHasCompleteness($scope)) {
            $this->recalcEntities($scope);
        };
    }

    /**
     * @param Event $event
     */
    public function afterDeleteActionDelete(Event $event): void
    {
        $scope = $event->getArgument('params')['scope'];
        if ($this->getHasCompleteness($scope)) {
            $this->recalcEntities($scope);
        }
    }

    /**
     * @param string $scope
     */
    protected function recalcEntities(string $scope): void
    {
        $this->getService('Completeness')->recalcEntities($scope, [], true);
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
