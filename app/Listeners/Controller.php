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

use Espo\ORM\Entity as EntityOrm;
use stdClass;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;

/**
 * Class Controller
 *
 * @author r.zablodskiy@treolabs.com
 */
class Controller extends AbstractListener
{

    /**
     * @param Event $event
     *
     * @throws Error
     */
    public function afterAction(Event $event)
    {
        $args = $event->getArguments();
        if ($this->hasCompleteness($args['controller'])) {
            if ($args['action'] == 'afterActionUpdate' || $args['action'] == 'afterActionCreate') {
                /** @var EntityOrm $entity */
                $entity = $this->getEntityManager()->getEntity($args['controller'], $args['result']->id);
                $args = $this->setCompletenessArgs($args, $entity);
            } elseif (isset($args['result']->id)) {
                if ($args['result']->complete === 0) {
                    $entity = $this->getEntityManager()->getEntity($args['controller'], $args['result']->id);
                    $args = $this->setCompletenessArgs($args, $entity);
                }
            } elseif (isset($args['result']['list'])) {
                foreach ($args['result']['list'] as $key => $item) {
                    if ($item->complete === 0) {
                        $entity = $this->getEntityManager()->getEntity($args['controller'], $item->id);
                        $args['result']['list'][$key] = $this->setCompletenessItem($item, $entity);
                    }
                }
            }
            $event->setArgument('result', $args['result']);
        }
    }

    /**
     * @param array $args
     * @param Entity $entity
     *
     * @return array
     */
    protected function setCompletenessArgs(array $args, Entity $entity): array
    {
        $resultCompleteness = $this
            ->getService('Completeness')
            ->runUpdateCompleteness($entity);

        foreach ($resultCompleteness as $field => $value) {
            $args['result']->{$field} = $value;
        }

        return $args;
    }

    /**
     * @param stdClass $item
     * @param Entity $entity
     *
     * @return stdClass
     */
    protected function setCompletenessItem(StdClass $item, Entity $entity): StdClass
    {
        $resultCompleteness = $this
            ->getService('Completeness')
            ->runUpdateCompleteness($entity);

        foreach ($resultCompleteness as $field => $value) {
            if (isset($item->{$field})) {
                $item->{$field} = $value;
            }
        }
        return $item;
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasCompleteness(string $entityName): bool
    {
        // hack for Product
        if ($entityName == ['ProductAttributeValue', 'ProductFamilyAttribute']) {
            $entityName = 'Product';
        }

        return !empty($this->getContainer()->get('metadata')->get("scopes.$entityName.hasCompleteness"));
    }
}
