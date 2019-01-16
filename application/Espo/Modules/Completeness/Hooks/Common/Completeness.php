<?php
/**
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) TreoLabs GmbH
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

namespace Espo\Modules\Completeness\Hooks\Common;

use Espo\Core\Utils\Util;
use Espo\Core\Hooks\Base;
use Espo\ORM\Entity;
use Espo\Core\Exceptions;

/**
 * Completeness hook
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Completeness extends Base
{
    /**
     * After save action
     *
     * @param Entity $entity
     * @param array  $options
     *
     * @return void
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        if ($this->hasCompleteness($entity->getEntityType())) {
            $this->updateCompleteness($entity, $options);
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    protected function updateCompleteness(Entity $entity, array $options): void
    {
        $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Completeness')
            ->updateCompleteness($entity);
    }

    /**
     * Is entity has completeness?
     *
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasCompleteness(string $entityName): bool
    {
        return !empty($this->getMetadata()->get("scopes.$entityName.hasCompleteness"));
    }
}
