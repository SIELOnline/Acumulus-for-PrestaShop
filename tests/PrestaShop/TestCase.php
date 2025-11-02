<?php

declare(strict_types=1);

namespace Siel\Acumulus\Tests\PrestaShop;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * PrestaShopTest is a base class for PrestaShop Acumulus integration tests.
 */
class TestCase extends PHPUnitTestCase
{
    use AcumulusTestUtils;
}
