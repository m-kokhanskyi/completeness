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

use Treo\Core\Migration\AbstractMigration;

/**
 * Class V1Dot12Dot0
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class V1Dot12Dot0 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $defs = $this->getContainer()->get('metadata')->get(['entityDefs']);
        $scopes = $this->getContainer()->get('metadata')->get(['scopes']);
        foreach ($defs as $entity => $row) {
            if (!empty($scopes[$entity]['hasCompleteness']) && !empty($scopes[$entity]['entity'])) {
                $this->recalcEntities($entity);
            }
        }
    }

    /**
     * @param string $entityName
     */
    protected function recalcEntities(string $entityName): void
    {
        $this->getContainer()
            ->get('serviceFactory')
            ->create('Completeness')
            ->recalcEntities($entityName);
    }
}
