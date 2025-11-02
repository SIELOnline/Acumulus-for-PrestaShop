<?php

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Integration\PrestaShop\Helpers;

use Siel\Acumulus\PrestaShop\Helpers\Log;
use Siel\Acumulus\Tests\PrestaShop\TestCase;

/**
 * LogTest tests whether the log class logs messages to a log file.
 *
 * This test is mainly used to test if the log feature still works in new versions of the
 * shop.
 */
class LogTest extends TestCase
{
    protected function getLogPath(): string
    {
        return Log::LogFolder . '/' . Log::LogFile;
    }

    public function testLog(): void
    {
        $this->_testLog();
    }
}
