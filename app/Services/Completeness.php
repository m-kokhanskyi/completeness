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

use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;
use Treo\Services\AbstractService;

/**
 * Completeness service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Completeness extends AbstractService
{
    public const LIMIT = 10000;

    /**
     * Update completeness
     *
     * @param Entity $entity
     *
     * @return array
     */
    public function runUpdateCompleteness(Entity $entity): array
    {
        /** @var CompletenessInterface $completeness */
        $servicesName = $this->getNameServiceEntity($entity->getEntityName());

        $completeness= new $servicesName();
        $completeness->setContainer($this->getContainer());
        $completeness->setEntity($entity);

        $result = $completeness->calculate();
        $completeness->saveEntity();

        return $result;
    }

    /**
     * Recalc all completeness for entity instances
     *
     * @param string $entityName
     * @param array $where
     *
     * @return void
     * @throws Error
     */
    public function recalcEntities(string $entityName, array $where = []): void
    {
        $count = $this->getEntityManager()
            ->getRepository($entityName)
            ->where($where)
            ->count();

        if ($count > 0) {
            $max = (int)$this->getConfig()->get('webMassUpdateMax', 200);
            if ($count < $max) {
                $entities = $this->getEntityManager()->getRepository($entityName)->where($where)->find();
                foreach ($entities as $entity) {
                    $this->runUpdateCompleteness($entity);
                }
            } else {
                for ($j = 0; $j <= $count; $j += self::LIMIT) {
                    $entities = $this->selectLimitById($entityName, self::LIMIT, $j, $where);
                    if (count($entities) > 0) {
                        $chunks = array_chunk($entities, $max);
                        foreach ($chunks as $chunk) {
                            $name = 'Updated completeness for ' . $entityName;
                            $this->qmPush(
                                $name,
                                'QueueManagerMassUpdateComplete',
                                ['entitiesIds' => array_column($chunk, 'id'), 'entityName' => $entityName]
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    public function hasCompleteness(string $entityName): bool
    {
        $entityName = $this
            ->getContainer()
            ->get('metadata')
            ->get(['scopes', $entityName, 'completeness', 'replacement'], $entityName);

        return !empty($this->getContainer()->get('metadata')->get("scopes.$entityName.hasCompleteness"));
    }

    /**
     * @param string $entityName
     * @return string
     */
    public function getNameServiceEntity(string $entityName): string
    {
        $result = CommonCompleteness::class;
        $service = $this
            ->getContainer()
            ->get('metadata')
            ->get(['scopes', $entityName, 'completeness', 'service']);
        if (!empty($service) && class_exists($service) && new $service instanceof CompletenessInterface) {
            $result = $service;
        }

        return $result;
    }

    /**
     * @param string $entityName
     *
     * @param int $limit
     * @param int $offset
     * @param array $where
     * @return array
     */
    protected function selectLimitById(string $entityName, $limit = 2000, $offset = 0, array $where = []): array
    {
        return $this->getEntityManager()
            ->getRepository($entityName)
            ->select(['id'])
            ->where($where)
            ->limit($offset, $limit)
            ->find()
            ->toArray();
    }

    /**
     * @param string $name
     * @param string $serviceName
     * @param array $data
     *
     * @return bool
     */
    private function qmPush(string $name, string $serviceName, array $data): bool
    {
        return $this
            ->getContainer()
            ->get('queueManager')
            ->push($name, $serviceName, $data);
    }
}
