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

namespace Completeness\Listeners;

/**
 * Class ProductTest
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for afterActionRead method
     */
    public function testAfterActionReadMethod()
    {
        // create mock
        $mock = $this->createPartialMock(Product::class, ['getChannelCompleteness']);
        $mock
            ->expects($this->any())
            ->method('getChannelCompleteness')
            ->willReturn([1]);

        $std = new \stdClass();
        $std->channelCompleteness = null;

        // get data
        $data = $mock->afterActionRead(['result' => clone $std, 'params' => ['id' => '1']]);

        // expected
        $std->channelCompleteness = [1];

        // test 1
        $this->assertEquals(['result' => $std, 'params' => ['id' => '1']], $data);

    }
}
