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

use Completeness\Services\CommonCompleteness;
use Completeness\Services\CompletenessInterface as ICompleteness;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class EntityManagerController
 *
 * @author r.ratsun@treolabs.com
 */
class EntityManagerController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeActionUpdateEntity(Event $event)
    {
        // run recalc completeness if it needs
        $this->recalcCompleteness($event->getArguments());
    }

    /**
     * Run recalc completeness if it needs
     *
     * @param array $data
     */
    protected function recalcCompleteness(array $data): void
    {
        // prepare data
        $scope = $data['data']->name;
        $hasCompleteness = !empty($data['data']->hasCompleteness);

        if ($hasCompleteness !== $this->getHasCompleteness($scope)) {
            // update scope
            $this->setHasCompleteness($scope, $hasCompleteness);

            // rebuild DB
            $this->getContainer()->get('dataManager')->rebuild();

            if ($hasCompleteness) {
                // reload entity manager
                $this->getContainer()->reload('entityManager');

                // recalc complete param
                $this
                    ->getService('Completeness')
                    ->recalcEntities($scope);
            }
        }
    }

    /**
     * Set hasCompleteness param
     *
     * @param string $scope
     * @param bool   $value
     */
    protected function setHasCompleteness(string $scope, bool $value): void
    {
        $metadata = $this->getContainer()->get('metadata');
        /** @var ICompleteness $service */
        $service = CommonCompleteness::class;
        if (!empty($class = $metadata->get(['scopes', $scope, 'completeness', 'service']))
            && class_exists($class) && new $class instanceof ICompleteness) {
            $service = $class;
        }

        $service::setHasCompleteness($this->getContainer(), $scope, $value);
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
