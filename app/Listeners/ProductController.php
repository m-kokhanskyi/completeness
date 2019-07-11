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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ProductController
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionRead(Event $event)
    {
        $result = $event->getArgument('result');
        $result->channelCompleteness = $this->getChannelCompleteness((string)$event->getArgument('params')['id']);
        $event->setArgument('result', $result);
    }

    /**
     * @param Event $event
     *
     * @throws BadRequest
     * @throws Error
     */
    public function beforeActionUpdate(Event $event)
    {
        $entity = $this->getEntityManager()->getEntity('Product', (string)$event->getArgument('params')['id']);
        $data = $event->getArgument('data');

        if (isset($data->isActive) && $data->isActive && $entity->get('complete') < 100) {
            throw new BadRequest($this->getLanguage()->translate('activationFailed', 'exceptions', 'Completeness'));
        }
    }

    /**
     * @param string $productId
     *
     * @return array
     */
    protected function getChannelCompleteness(string $productId): array
    {
        return $this->getService('Completeness')->getChannelCompleteness($productId);
    }
}
