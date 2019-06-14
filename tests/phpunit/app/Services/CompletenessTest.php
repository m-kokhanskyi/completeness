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

namespace Completeness\Services;

/**
 * Class CompletenessTest
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CompletenessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test is runUpdateCompleteness method exists
     */
    public function testIsRunUpdateCompletenessExists()
    {
        // create mock
        $mock = $this->createPartialMock(Completeness::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'runUpdateCompleteness'));
    }

    /**
     * Test is recalcEntity method exists
     */
    public function testIsRecalcEntityExists()
    {
        // create mock
        $mock = $this->createPartialMock(Completeness::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'recalcEntity'));
    }

    /**
     * Test is getChannelCompleteness method exists
     */
    public function testIsGetChannelCompletenessExists()
    {
        // create mock
        $mock = $this->createPartialMock(Completeness::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'getChannelCompleteness'));
    }
}
