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
            'NL consument, mixed rates, virtual + physical' => [$dataPath, Source::Order, 7,],
            'FR consument, mixed rates, NL shipping' => [$dataPath, Source::Order, 8,],
            'FR consument, mixed rates, FR shipping' => [$dataPath, Source::Order, 9,],
            'FR bedrijf, standard rate' => [$dataPath, Source::Order, 10,],
            'FR bedrijf, reverse vat' => [$dataPath, Source::Order, 11,],
            'FR consument, productkorting + coupon code' => [$dataPath, Source::Order, 12,],
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
