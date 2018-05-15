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
    public function afterActionUpdateEntity(array $data): array
    {
        // prepare post data
        $postData = get_object_vars($data['data']);

        if (!empty($postData['hasCompleteness'])) {
            // recalc complete param
            $this
                ->getContainer()
                ->get('serviceFactory')
                ->create('Completeness')
                ->recalcEntity($postData['name']);
        }

        return $data;
    }
}
