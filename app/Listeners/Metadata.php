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

use Espo\Core\Utils\Util;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Metadata class
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Metadata extends AbstractListener
{

    /**
     * Modify
     *
     * @param Event $event
     */
    public function modify(Event $event): void
    {
        // get data
        $data = $event->getArgument('data');
        $data = $this->addDashlet($data);

        $event->setArgument('data', $data);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function addDashlet(array $data): array
    {
        if (!empty($data['completeness'])) {
            foreach ($data['completeness'] as $key => $item) {
                if (empty($data['dashlets'][$key]) && !empty($item['isDashlet'])) {
                    $data['dashlets'][$key] = $item;
                }
            }
        }

        return $data;
    }
}
