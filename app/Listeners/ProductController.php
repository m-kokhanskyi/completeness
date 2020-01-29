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

use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class ProductController
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class ProductController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionRead(Event $event): void
    {
        if ($this->hasCompleteness('Product')) {
            $this->removeNotLinkChannels($event);
        }
    }

    /**
     * @param Event $event
     */
    protected function removeNotLinkChannels(Event $event): void
    {
        $fields = $this->getMetadata()->get(['entityDefs', 'Product', 'fields'], []);
        $result = $event->getArguments('result')['result'];

        $idFind = $result->type === 'productVariant' ? $result->configurableProductId : $result->id;


        $channels = $this
            ->getEntityManager()
            ->getRepository('Channel')
            ->select(['code'])
            ->leftJoin('products')
            ->where(['products.id' => $idFind])
            ->find()
            ->toArray();

        $channels = array_column($channels, 'code');
        if (!empty($channels)) {
            foreach ($result as $field => $value) {
                if ($this->isNotExistChannelField($fields, $field, $channels)) {
                    //remove channel completeness field
                    unset($result->{$field});
                }
            }
        }

        $event->setArgument('result', $result);
    }

    /**
     * @param array $fields
     * @param string $field
     * @param array $channels
     * @return bool
     */
    protected function isNotExistChannelField(array $fields, string $field, array $channels): bool
    {
        $result = false;
        if (!empty($fields[$field]['isCompleteness']) && !empty($fields[$field]['isChannel'])) {
            $channel = str_replace('completeness_channel_', '', $field);
            $result = !in_array($channel, $channels, true);
        }
        return $result;
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasCompleteness(string $entityName): bool
    {
        return $this->getService('Completeness')->hasCompleteness($entityName);
    }
}
