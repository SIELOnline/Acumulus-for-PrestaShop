<?php
/**
 * @noinspection PhpStaticAsDynamicMethodCallInspection
 */

declare(strict_types=1);

namespace Siel\Acumulus\Tests\PrestaShop;

use Module;
use PHPUnit\Framework\TestCase;
use PrestaShop;
use Siel\Acumulus\Helpers\Container;
use Siel\Acumulus\Tests\AcumulusTestUtils;

/**
 * PrestaShopTest is a base class for PrestaShop Acumulus integration tests.
 */
class PrestaShopTest extends TestCase
{
    use AcumulusTestUtils;

    protected static function getAcumulusContainer(): Container
    {
        /** @var \Acumulus $module */
        $module = Module::getInstanceByName('acumulus');
        return $module->getAcumulusContainer();
    }
}
