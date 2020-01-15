<?php
/**
 * Multilang
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Completeness\Listeners;

use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class Language
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Language extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function modify(Event $event)
    {
        // get data
        $data = $event->getArgument('data');

        if (!empty($fields = $this->container->get('metadata')->get('entityDefs.Product.fields', []))) {
            foreach ($fields as $field => $defs) {
                if (!empty($fields[$field]['isCompleteness']) && !empty($fields[$field]['isChannel'])) {
                    foreach ($data as $local => $translate) {
                        $complete = $data[$local]['Global']['fields']['complete'];
                        $data[$local]['Product']['fields'][$field] = $complete . ' ' . $field;
                    }
                }
            }
        }
        // set data
        $event->setArgument('data', $data);
    }
}
