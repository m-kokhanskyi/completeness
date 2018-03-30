<?php
declare(strict_types=1);

namespace Espo\Modules\Completeness\Listeners;

use Espo\Modules\TreoCore\Listeners\AbstractListener;

/**
 * EntityManager listener
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EntityManager extends AbstractListener
{
    /**
     * After update action
     *
     * @param array $data
     *
     * @return void
     */
    public function afterUpdate(array $data)
    {
        if (!empty($data['data']['hasCompleteness'])) {
            // recalc complete param
            $this
                ->getContainer()
                ->get('serviceFactory')
                ->create('Completeness')
                ->recalcEntity($data['name']);
        }
    }
}
