<?php
/**
 * @noinspection PhpStaticAsDynamicMethodCallInspection
 */

declare(strict_types=1);

namespace Siel\Acumulus\Tests\PrestaShop\Integration;

use Siel\Acumulus\Invoice\Source;
use Siel\Acumulus\Tests\PrestaShop\PrestaShopTest;

use function dirname;

/**
 * InvoiceCreateTest tests the process of creating an {@see Invoice}.
 */
class InvoiceCreateTest extends PrestaShopTest
{
    public function InvoiceDataProvider(): array
    {
        $dataPath = dirname(__FILE__, 2) . '/Data';
        return [
            'NL consument, reduced rate' => [$dataPath, Source::Order, 6,],
            'NL consument, standard rate' => [$dataPath, Source::Order, 7,],
            'FR consument, reduced rate' => [$dataPath, Source::Order, 8,],
            'FR consument, standard rate' => [$dataPath, Source::Order, 9,],
            'FR bedrijf, standard rate' => [$dataPath, Source::Order, 10,],
        ];
    }

    /**
     * Tests the Creation process, i.e. collecting and completing an
     * {@see \Siel\Acumulus\Data\Invoice}.
     *
     * @dataProvider InvoiceDataProvider
     * @throws \JsonException
     */
    public function testCreate(string $dataPath, string $type, int $id, array $excludeFields = []): void
    {
        $this->_testCreate($dataPath, $type, $id, $excludeFields);
    }
}
