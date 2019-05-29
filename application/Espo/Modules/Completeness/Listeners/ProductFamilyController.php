<?php
/**
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
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

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ProductFamilyController
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductFamilyController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionUpdateAttribute(Event $event)
    {
        if (!empty($event->getArgument('data')->productFamilyId)) {
            $this->updateCompleteness((string)$event->getArgument('data')->productFamilyId);
        }
    }

    /**
     * @param Event $event
     */
    public function afterActionCreateLink(Event $event)
    {
        if (!empty($event->getArgument('params')['id'])) {
            $this->updateCompleteness((string)$event->getArgument('params')['id']);
        }
    }

    /**
     * @param Event $event
     */
    public function afterActionRemoveLink(Event $event)
    {
        if (!empty($event->getArgument('params')['id'])) {
            $this->updateCompleteness((string)$event->getArgument('params')['id']);
        }
    }

    /**
     * @param string $productFamilyId
     */
    protected function updateCompleteness(string $productFamilyId): void
    {
        // get products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['productFamilyId' => $productFamilyId])
            ->find();

        if (count($products) > 0) {
            foreach ($products as $product) {
                $this
                    ->getContainer()
                    ->get('serviceFactory')
                    ->create('Completeness')
                    ->runUpdateCompleteness($product);
            }
        }
    }
}
