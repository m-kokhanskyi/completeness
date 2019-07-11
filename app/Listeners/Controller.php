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
use Espo\Core\Exceptions\Error;

/**
 * Class Controller
 *
 * @author r.zablodskiy@treolabs.com
 */
class Controller extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @throws Error
     */
    public function afterAction(Event $event)
    {
        $data = $event->getArguments();

        if ($this->hasCompleteness($data['controller']) && isset($data['result']->id)) {
            $entity = $this->getEntityManager()->getEntity($data['controller'], $data['result']->id);

            if (!empty($entity)) {
                $this
                    ->getContainer()
                    ->get('serviceFactory')
                    ->create('Completeness')
                    ->runUpdateCompleteness($entity);
            }
        }
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasCompleteness(string $entityName): bool
    {
        // hack for Product
        if ($entityName == 'ProductAttributeValue') {
            $entityName = 'Product';
        }

        return !empty($this->getContainer()->get('metadata')->get("scopes.$entityName.hasCompleteness"));
    }
}
