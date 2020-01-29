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

namespace Completeness;

use Completeness\Services\CommonCompleteness;
use Completeness\Services\CompletenessInterface as ICompleteness;
use Espo\Core\ORM\Entity;
use Treo\Core\ModuleManager\AbstractEvent;
use Treo\Core\Utils\Auth;

/**
 * Class Event
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Event extends AbstractEvent
{
    /**
     * @inheritdoc
     */
    public function afterInstall(): void
    {
        (new Auth($this->getContainer()))->useNoAuth();

        $metadata = $this->getContainer()->get('metadata');
        $entityDefs = $metadata->get('entityDefs');

        foreach ($entityDefs as $entity => &$row) {
            if ($this->hasCompleteness($entity)) {
                /** @var ICompleteness $service */
                $service = CommonCompleteness::class;
                if (!empty($class = $metadata->get(['scopes', $entity, 'completeness', 'service']))
                    && class_exists($class) && new $class instanceof ICompleteness) {
                    $service = $class;
                }
                try {
                    $service::setHasCompleteness($this->getContainer(), $entity, false);
                    $service::setHasCompleteness($this->getContainer(), $entity, true);

                    // rebuild DB
                    $this->getContainer()->get('dataManager')->rebuild();

                    $this
                        ->getContainer()
                        ->get('serviceFactory')
                        ->create('Completeness')
                        ->recalcEntities($entity);
                } catch (\Exception $e) {
                    $this->setLog($entity, $e);
                    continue;
                }
            }
        }
    }

    /**
     * After module delete event
     */
    public function afterDelete(): void
    {
        try {
            $metadata = $this->getContainer()->get('metadata');
            if (!empty($metadata->get(['scopes', 'Product', 'hasCompleteness']))
                    && !empty($metadata->get(['scopes','Channel']))) {
                $channels = $this
                    ->getContainer()
                    ->get('entityManager')
                    ->getRepository('Channel')
                    ->select(['code'])
                    ->find();
                foreach ($channels as $channel) {
                    $this->dropChannelProduct($channel);
                }
            }
        } catch (\Exception $e) {
            $this->setLog('Product', $e);
        }
    }

    /**
     * @param Entity $channel
     */
    protected function dropChannelProduct(Entity $channel): void
    {
        $nameField = 'completeness_channel_' . $channel->get('code');;
        if (!empty($this->getContainer()->get('metadata')->get(['entityDefs', 'Product', 'fields', $nameField]))) {
            $this->getContainer()->get('fieldManager')->delete('Product', $nameField);
            $columns = $this
                ->getContainer()
                ->get('entityManager')
                ->nativeQuery("SHOW COLUMNS FROM `product` LIKE '{$nameField}'")
                ->fetch(PDO::FETCH_ASSOC);
            if (!empty($columns)) {
                $this
                    ->getContainer()
                    ->get('entityManager')
                    ->getPDO()
                    ->exec('ALTER TABLE product DROP COLUMN ' . $nameField);
            }
        }
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    protected function hasCompleteness(string $entityName): bool
    {
        return  $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Completeness')
            ->hasCompleteness($entityName);
    }

    /**
     * @param string $entity
     * @param \Exception $e
     */
    protected function setLog(string $entity, \Exception $e): void
    {
        $GLOBALS['log']
            ->error('Error install Completeness for ' . $entity . ': '
                . $e->getMessage() . ';File:' . $e->getFile() . ';Line:'. $e->getLine());
    }
}
