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

use Treo\Core\Utils\Util;
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
        $data = $event->getArguments();

        if ($this->hasCompleteness($data['controller'])) {
            if (isset($data['result']->id)) {
                $entity = $this->getEntityManager()->getEntity($data['controller'], $data['result']->id);

                if (!empty($entity)) {
                    $data['result'] = $this->getUpdatedCompleteness($entity, $data['result']);
                }

            } elseif (isset($data['result']['list'])) {
                $list = $this
                    ->getEntityManager()
                    ->getRepository($data['controller'])
                    ->where([
                        'id' => array_column($data['result']['list'], 'id')
                    ])
                    ->find();

                if (count($list) > 0) {
                    foreach ($data['result']['list'] as $key => $item) {
                        foreach ($list as $entity) {
                            if ($entity->get('id') == $item->id) {
                                $data['result']['list'][$key] = $this->getUpdatedCompleteness($entity, $item);
                                break;
                            }
                        }
                    }

                }
            }

            $event->setArgument('result', $data['result']);
        }
    }

    /**
     * @param Entity $entity
     * @param \stdClass $result
     *
     * @return \stdClass
     */
    protected function getUpdatedCompleteness(Entity $entity, \stdClass $result): \stdClass
    {
        $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Completeness')
            ->runUpdateCompleteness($entity);

        $result->complete = $entity->get('complete');

        if (!empty($this->getConfig()->get('isMultilangActive'))) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $field = Util::toCamelCase('complete' . $locale);

                $result->$field = $entity->get($field);
            }
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
        // hack for Product
        if ($entityName == 'ProductAttributeValue') {
            $entityName = 'Product';
        }

        return !empty($this->getContainer()->get('metadata')->get("scopes.$entityName.hasCompleteness"));
    }
}
