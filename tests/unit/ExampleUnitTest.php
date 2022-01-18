<?php
/**
 * powertools plugin for Craft CMS 3.x
 *
 * A set of tools used internally by Sitemill
 *
 * @link      sitemill.co
 * @copyright Copyright (c) 2022 sitemill
 */

namespace sitemill\powertoolstests\unit;

use Codeception\Test\Unit;
use UnitTester;
use Craft;
use sitemill\powertools\Powertools;

/**
 * ExampleUnitTest
 *
 *
 * @author    sitemill
 * @package   Powertools
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
            Powertools::class,
            Powertools::$plugin
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
