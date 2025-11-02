<?php
/**
 * @noinspection PhpStaticAsDynamicMethodCallInspection
 */

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Integration\PrestaShop\PrestaShop8;

use Siel\Acumulus\Invoice\Source;
use Siel\Acumulus\Tests\PrestaShop\TestCase;

/**
 * InvoiceCreateTest tests the process of creating an {@see Invoice}.
 *
 * Note that the VAT checker module installs an override of the
 * {@see \TaxRulesTaxManagerCore} class that will always return an "empty" tax rule
 * manager once it has decided that tax calculations should be disabled. This is a problem
 * this test class but also for the batch send form.
 */
class InvoiceCreateTest extends TestCase
{
    protected function setUp(): void
    {
        self::createContainer('PrestaShop\PrestaShop8');
        parent::setUp();
    }

    public static function InvoiceDataProvider(): array
    {
        return [
            'NL consument, mixed rates, virtual + physical' => [Source::Order, 7,],
            'FR consument, mixed rates, NL shipping' => [Source::Order, 8,],
            'FR consument, mixed rates, FR shipping' => [Source::Order, 9,],
            'FR bedrijf, standard rate' => [Source::Order, 10,],
            'FR bedrijf, reverse vat' => [Source::Order, 11,],
            'FR consument, productkorting + coupon code' => [Source::Order, 14,],
            'Credit note for FR consument, productkorting + coupon code' => [Source::CreditNote, 3,],
            'FR consument, productkorting + coupon code that will not be refunded' => [Source::Order, 15,],
            'Credit note for FR consument, productkorting + coupon code not refunded' => [Source::CreditNote, 4,],
            'FR consument, partial refund, coupon code will be revoked' => [Source::Order, 16,],
            'Credit note for FR consument, partial refund, productkorting + coupon code refunded' => [Source::CreditNote, 5,],
            'FR consument,NL shipping, partial refund, coupon code revoked, shipping refunded' => [Source::Order, 17,],
            'Credit note FR consument, NL shipping, partial, coupon code revoked, shipping refunded' => [Source::CreditNote, 6,],
            'FR consument, EUR 85.84' => [Source::Order, 20,],
            'FR consument, GBP 72.96' => [Source::Order, 21,],
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
