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

namespace Completeness\Services;

use Completeness;
use Treo\Services\QueueManagerBase;

/**
 * Class QueueManagerMassUpdateComplete
 *
 * @author m.kokhanskyi <m.kokhanskyi@ztreolabs.com>
 */
class QueueManagerMassUpdateComplete extends QueueManagerBase
{
    /**
     * @inheritdoc
     */
    public function run(array $data = []): bool
    {
        $result = false;
        if (!empty($data['entityName']) && isset($data['entitiesIds']) && is_array($data['entitiesIds'])) {
            $this->massUpdateComplete($data['entityName'], $data['entitiesIds']);
            // prepare result
            $result = true;
        }
        return $result;
    }

    /**
     * @param string $entityName
     * @param array $entitiesIds
     */
    protected function massUpdateComplete(string $entityName, array $entitiesIds): void
    {
        /** @var Completeness $service */
        $service = $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Completeness');

        $entities = $this->getEntityManager()
            ->getRepository($entityName)
            ->where(['id' => $entitiesIds])
            ->find();

        foreach ($entities as $entity) {
            $service->runUpdateCompleteness($entity);
        }
    }
}
