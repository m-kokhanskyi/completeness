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

namespace Completeness\Migrations;

use Completeness\Services\CommonCompleteness;
use Completeness\Services\Completeness;
use Completeness\Services\ProductCompleteness;
use Espo\ORM\EntityCollection;
use Treo\Composer\PostUpdate;
use Treo\Core\Migration\AbstractMigration;
use Treo\Core\Utils\Auth;

/**
 * Class V1Dot12Dot2
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class V1Dot12Dot2 extends AbstractMigration
{
    public const LIMIT = 10000;

    /**
     * Up to current
     */
    public function up(): void
    {
        (new Auth($this->getContainer()))->useNoAuth();

        $service = $this->getContainer()->get('serviceFactory')->create('Completeness');
        if (method_exists($service, 'runUpdateCompleteness')) {
            $scopes = $this->getContainer()->get('metadata')->get(['scopes'], []);
            if (!empty($scopes['Product']['hasCompleteness'])&& !empty($scopes['Product']['entity'])) {
                $this->recalcEntities('Product');
            }
        }
    }

    /**
     * @param string $entityName
     */
    protected function recalcEntities(string $entityName): void
    {
        // prepare data
        $fields = [];
        $channels = $this->getContainer()
            ->get('entityManager')
            ->getRepository('Channel')
            ->select(['name'])
            ->find()
            ->toArray();

        $defs = CommonCompleteness::CONFIG_COMPLETE_FIELDS;
        $defs['isChannel'] = true;

        foreach ($channels as $k => $ch) {
            $defs['sortOrder'] = ProductCompleteness::START_SORT_ORDER_CHANNEL + (int)$k;
            $fields[ProductCompleteness::getNameChannelField($ch['name'])] = $defs;
        }

        $this->getContainer()->get('metadata')->set('entityDefs', $entityName, ['fields' => $fields]);
        $this->getContainer()->get('metadata')->save();

        $this->getContainer()->get('dataManager')->rebuild();

        /** @var Completeness $service */
        $service = $this->getContainer()->get('serviceFactory')->create('Completeness');
        $count = $this->getEntityManager()->getRepository($entityName)->count();
        PostUpdate::renderLine('Update completeness fields in ' . $entityName);
        if ($count > 0) {
            for ($j = 0; $j <= $count; $j += self::LIMIT) {
                $entities = $this->selectLimitById($entityName, self::LIMIT, $j);
                foreach ($entities as $entity) {
                    $service->runUpdateCompleteness($entity);
                }
            }
        }
    }

    /**
     * @param string $entityName
     *
     * @param int $limit
     * @param int $offset
     * @return EntityCollection
     */
    protected function selectLimitById(string $entityName, $limit = 2000, $offset = 0): EntityCollection
    {
        return $this->getEntityManager()
            ->getRepository($entityName)
            ->limit($offset, $limit)
            ->find();
    }
}
