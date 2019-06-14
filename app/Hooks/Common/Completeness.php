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

namespace Completeness\Hooks\Common;

use Espo\ORM\Entity;

/**
 * Class Completeness
 *
 * @author r.ratsun@treolabs.com
 */
class Completeness extends \Espo\Core\Hooks\Base
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
            $this->updateCompleteness($entity);
        }
    }

    /**
     * @param Entity $entity
     */
    protected function updateCompleteness(Entity $entity): void
    {
        $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Completeness')
            ->runUpdateCompleteness($entity);
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
        // hack for Product
        if ($entityName == 'ProductAttributeValue') {
            $entityName = 'Product';
        }

        return !empty($this->getMetadata()->get("scopes.$entityName.hasCompleteness"));
    }
}
