<?php
/**
 * @noinspection PhpIllegalPsrClassPathInspection
 */

declare(strict_types=1);

namespace Siel\Acumulus\Tests;

use Siel\Acumulus\Tests\PrestaShop\AcumulusTestUtils;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

/**
 * UpdateTestSources updates {type}{id}.json test data based on regexp search and replace.
 */
class UpdateTestSources
{
    use AcumulusTestUtils;

    public function execute(): void
    {
        $this->updateTestSources();
    }
}

(new UpdateTestSources())->execute();
