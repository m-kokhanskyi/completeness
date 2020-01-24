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
use Completeness\Services\ProductCompleteness;
use Pim\Entities\Channel;
use Treo\Core\Utils\FieldManager;
use Treo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ChannelEntity
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class ChannelEntity extends AbstractListener
{
   public function afterSave(Event $event): void
   {
       /** @var Channel $channel */
       $channel = $event->getArgument('entity');
       if ($channel->isNew() && $this->hasCompleteness('Product')) {

           $defs = CommonCompleteness::CONFIG_COMPLETE_FIELDS;
           $defs['isChannel'] = true;
           $defs['isCustom'] = false;
           $defs['sortOrder'] = ProductCompleteness::START_SORT_ORDER_CHANNEL;

           $fieldsEntityDefs = $this->getMetadata()->get(['entityDefs', 'fields'], []);
           //find maximum sortOrder
           foreach ($fieldsEntityDefs as $field => $entityDefs) {
               if (!empty($entityDefs['isChannel']) && $entityDefs['sortOrder'] > $defs['sortOrder']) {
                   $defs['sortOrder'] = $entityDefs['sortOrder'];
               }
           }
           ProductCompleteness::createFieldChannel($this->getContainer(), $channel, $defs, false);
       }
   }

    /**
     * @param Event $event
     */
   public function afterRemove(Event $event): void
   {
      if ($this->hasCompleteness('Product')) {
          ProductCompleteness::dropFieldChannel($this->getContainer(), $event->getArgument('entity'), false);
      }
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
