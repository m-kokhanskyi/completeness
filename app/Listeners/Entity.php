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

use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity as EntityOrm;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class Entity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Entity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        /** @var EntityOrm $entity */
        $entity = $event->getArgument('entity');
        if ($this->hasCompleteness($entity->getEntityName())) {
            $this->updateCompleteness($entity);
        }
    }

    /**
     * @param Event $event
     * @throws Error
     */
    public function afterUnrelate(Event $event)
    {
        $foreign = $event->getArgument('foreign');
        if ($foreign instanceof EntityOrm && $this->hasCompleteness($foreign->getEntityName())) {
            //refresh entity
            $entityNew = $this->getEntityManager()->getEntity($foreign->getEntityName(), $foreign->get('id'));
            $this->updateCompleteness($entityNew);
        }

        $entity = $event->getArgument('entity');
        if ($this->hasCompleteness($entity->getEntityName())) {
            $this->updateCompleteness($entity);
        }
    }


    /**
     * @param Event $event
     * @throws Error
     */
    public function afterRelate(Event $event)
    {
        $foreign = $event->getArgument('foreign');
        if ($foreign instanceof EntityOrm && $this->hasCompleteness($foreign->getEntityName())) {
            //refresh entity
            $entityNew = $this->getEntityManager()->getEntity($foreign->getEntityName(), $foreign->get('id'));
            $this->updateCompleteness($entityNew);
        }

        $entity = $event->getArgument('entity');
        if ($this->hasCompleteness($entity->getEntityName())) {
            $this->updateCompleteness($entity);
        }
    }

    /**
     * @param EntityOrm $entity
     */
    protected function updateCompleteness(EntityOrm $entity): void
    {
        $this->getService('Completeness')->runUpdateCompleteness($entity);
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
