<?php

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Integration\PrestaShop;

use Siel\Acumulus\Invoice\Source;
use Siel\Acumulus\Tests\PrestaShop\TestCase;

/**
 * InvoiceCreateTest tests the process of creating an {@see Invoice}.
 *
 * Note that the VAT checker module installs an override of the
 * {@see \TaxRulesTaxManagerCore} class that will always return an "empty" tax rule
 * manager once it has decided that tax calculations should be disabled. This is a problem
 * for this test class but also for the batch send form.
 *
 * @todo: add tests for gift wrapping and payment fee (paypal with a fee module) lines.
 * @todo: add a margin scheme invoice.
 */
class InvoiceCreateTest extends TestCase
{
    public static function InvoiceDataProvider(): array
    {
        return [
            'NL consument + Mollie payment fee' => [Source::Order, 6,],
            'Credit Slip' => [Source::CreditNote, 1,],
            'NL bedrijf' => [Source::Order, 7,],
            'BE consument' => [Source::Order, 8,],
            'FR consument + PayPal account via example module' => [Source::Order, 9,],
            'FR bedrijf' => [Source::Order, 10,],
            'BE consument, PS9, catalogus en winkelwagenregel' => [Source::Order, 11,],
            'BE consument, PS9, catalogus en winkelwagenregel, credit slip' => [Source::CreditNote, 2,],
            'BE consument, PS9, catalogus en winkelwagenregel, cadeauverpakking' => [Source::Order, 12,],
            'BE consument, PS9, catalogus en winkelwagenregel, cadeauverpakking, credit slip' => [Source::CreditNote, 3,],
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
