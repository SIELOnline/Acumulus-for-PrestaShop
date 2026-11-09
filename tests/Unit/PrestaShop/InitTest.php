<?php

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Unit\PrestaShop;

use Siel\Acumulus\Config\Environment;
use Siel\Acumulus\Tests\PrestaShop\TestCase;

/**
 * Tests that WooPrestaShop and Acumulus have been initialized.
 */
class InitTest extends TestCase
{
    /**
     * A single test to see if the test framework (including the plugins) has been
     * initialised correctly:
     * 1 We have access to the Container.
     * 2 PrestaShop and the database have been initialised.
     */
    public function testInit(): void
    {
        // 1.
        $environmentInfo = self::getContainer()->getEnvironment()->toArray();
        // 2.
        static::assertMatchesRegularExpression('|\d+\.\d+\.\d+|', $environmentInfo['shopVersion']);
        static::assertNotEquals(Environment::Unknown, $environmentInfo['dbVersion']);
    }
}
