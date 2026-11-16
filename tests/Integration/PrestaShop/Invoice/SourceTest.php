<?php

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Integration\PrestaShop\Invoice;

use Configuration;
use Context;
use Siel\Acumulus\PrestaShop\Invoice\Source;
use Siel\Acumulus\Tests\PrestaShop\TestCase;

use function sprintf;

/**
 * SourceTest tests the {@see \Siel\Acumulus\PrestaShop\Invoice\Source} class.
 */
class SourceTest extends TestCase
{
    public function testCreditSlipReference(): void
    {
        static::assertSame('CN', Configuration::get('PS_CREDIT_SLIP_PREFIX', 1)); // nl
        static::assertSame('AV', Configuration::get('PS_CREDIT_SLIP_PREFIX', 2)); // fr
        static::assertSame('CN', Configuration::get('PS_CREDIT_SLIP_PREFIX', 3)); // en

        $source = static::getContainer()->createSource(Source::CreditNote, 1);
        static::assertSame(
            'CN000001',
            sprintf('%s%06d', Configuration::get('PS_CREDIT_SLIP_PREFIX', Context::getContext()->language->id), $source->getShopObject()->id)
        );
    }
}
