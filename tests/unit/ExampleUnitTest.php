<?php
/**
 * Prismatic Links plugin for Craft CMS 3.x
 *
 * Link previews
 *
 * @link      https://prismaticbytes.com
 * @copyright Copyright (c) 2021 Prismatic Bytes
 */

namespace prismaticbytes\prismaticlinkstests\unit;

use Codeception\Test\Unit;
use UnitTester;
use Craft;
use prismaticbytes\prismaticlinks\PrismaticLinks;

/**
 * ExampleUnitTest
 *
 *
 * @author    Prismatic Bytes
 * @package   PrismaticLinks
 * @since     1.0.0
 */
class ExampleUnitTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testPluginInstance()
    {
        $this->assertInstanceOf(
            PrismaticLinks::class,
            PrismaticLinks::$plugin
        );
    }

    /**
     *
     */
    public function testCraftEdition()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }

}
