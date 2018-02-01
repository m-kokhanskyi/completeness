<?php
declare(strict_types = 1);

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
     * Before save action
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function beforeSave(Entity $entity)
    {
        // update completeness
        $entity = $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('Completeness')
            ->updateCompleteness($entity);
    }
}
