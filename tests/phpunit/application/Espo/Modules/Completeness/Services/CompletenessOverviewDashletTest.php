<?php
/**
 * Completeness
 * TreoPIM Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see http://treopim.com/eula.
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

namespace Espo\Modules\Completeness\Services;

/**
 * Class CompletenessOverviewDashletTest
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CompletenessOverviewDashletTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for getDashlet method
     */
    public function testGetDashletMethod()
    {
        // prepare methods
        $methods = [
            'getCompletenessFieldInProduct',
            'getChannelComplete',
            'getCompletenessTotal'
        ];

        // create mock
        $mock = $this->createPartialMock(CompletenessOverviewDashlet::class, $methods);
        $mock
            ->expects($this->any())
            ->method('getCompletenessFieldInProduct')
            ->willReturn([[1]]);
        $mock
            ->expects($this->any())
            ->method('getChannelComplete')
            ->willReturn([['item' => 1]]);
        $mock
            ->expects($this->any())
            ->method('getCompletenessTotal')
            ->willReturn(['item' => 2]);

        // test
        $this->assertEquals(['total' => 2, 'list' => [['item' => 1], ['item' => 2]]], $mock->getDashlet());
    }
}
