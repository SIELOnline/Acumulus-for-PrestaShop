<?php
/**
 * @noinspection PhpStaticAsDynamicMethodCallInspection
 */

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Joomla\Unit;

use Module;
use Siel\Acumulus\Config\Environment;
use Siel\Acumulus\Tests\PrestaShop\PrestaShopTest;

/**
 * Tests that WooCommerce and Acumulus have been initialized.
 */
class InitTest extends PrestaShopTest
{
    /**
     * A single test to see if the test framework (including the plugins) has been
     * initialized correctly:
     * 1 We have access to the Container.
     * 2 PrestaShop and the database have been initialized.
     */
    public function testInit(): void
    {
        // 1.
        /** @var \Acumulus $module */
        $module = Module::getInstanceByName('acumulus')->version;
        $container = $module->getAcumulusContainer();
        $environmentInfo = $container->getEnvironment()->get();
        // 2.
        $this->assertMatchesRegularExpression('|\d+\.\d+\.\d+|', $environmentInfo['shopVersion']);
        $this->assertNotEquals(Environment::Unknown, $environmentInfo['dbVersion']);
    }
}
