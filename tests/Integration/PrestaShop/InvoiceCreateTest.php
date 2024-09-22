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
 */
class InvoiceCreateTest extends PrestaShopTest
{
    public function InvoiceDataProvider(): array
    {
        $dataPath = __DIR__ . '/Data';
        return [
            'NL consument, mixed rates, virtual + physical' => [$dataPath, Source::Order, 7,],
            'FR consument, mixed rates, NL shipping' => [$dataPath, Source::Order, 8,],
            'FR consument, mixed rates, FR shipping' => [$dataPath, Source::Order, 9,],
            'FR bedrijf, standard rate' => [$dataPath, Source::Order, 10,],
            'FR bedrijf, reverse vat' => [$dataPath, Source::Order, 11,],
            'FR consument, productkorting + coupon code' => [$dataPath, Source::Order, 14,],
            'Credit note for FR consument, productkorting + coupon code' => [$dataPath, Source::CreditNote, 3,],
            'FR consument, productkorting + coupon code that will not be refunded' => [$dataPath, Source::Order, 15,],
            'Credit note for FR consument, productkorting + coupon code not refunded' => [$dataPath, Source::CreditNote, 4,],
            'FR consument, partial refund, coupon code will be revoked' => [$dataPath, Source::Order, 16,],
            'Credit note for FR consument, partial refund, productkorting + coupon code refunded' => [$dataPath, Source::CreditNote, 5,],
            'FR consument,NL shipping, partial refund, coupon code revoked, shipping refunded' => [$dataPath, Source::Order, 17,],
            'Credit note FR consument, NL shipping, partial, coupon code revoked, shipping refunded' => [$dataPath, Source::CreditNote, 6,],
            'FR consument, EUR 85.84' => [$dataPath, Source::Order, 20,],
            'FR consument, GBP 72.96' => [$dataPath, Source::Order, 21,],
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
