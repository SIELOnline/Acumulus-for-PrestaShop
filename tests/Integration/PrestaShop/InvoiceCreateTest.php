<?php
/**
 * @noinspection PhpStaticAsDynamicMethodCallInspection
 */

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Integration\PrestaShop;

use Siel\Acumulus\Invoice\Source;
use Siel\Acumulus\Tests\PrestaShop\PrestaShopTest;

/**
 * InvoiceCreateTest tests the process of creating an {@see Invoice}.
 *
 * Note that the VAT checker module installs an override of the
 * {@see \TaxRulesTaxManagerCore} class that will always return an "empty" tax rule
 * manager once it has decided that tax calculations should be disabled. This is a problem
 * this test class but also for the batch send form.
 *
 * @todo: add tests for gift wrapping and payment fee (paypal with a fee module) lines.
 * @todo: add a margin scheme invoice.
 */
class InvoiceCreateTest extends PrestaShopTest
{
    public static function InvoiceDataProvider(): array
    {
        return [
            'NL consument + Mollie betaal fee' => [Source::Order, 6,],
            'NL bedrijf' => [Source::Order, 7,],
            'BE consument' => [Source::Order, 8,],
            'FR consument' => [Source::Order, 9,],
            'FR bedrijf' => [Source::Order, 10,],
        ];


    }

    /**
     * Tests the Creation process, i.e. collecting and completing an
     * {@see \Siel\Acumulus\Data\Invoice}.
     *
     * @dataProvider InvoiceDataProvider
     * @throws \JsonException
     */
    public function testCreate(string $type, int $id, array $excludeFields = []): void
    {
        $this->_testCreate($type, $id, $excludeFields);
    }
}
