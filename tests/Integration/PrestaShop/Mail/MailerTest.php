<?php

declare(strict_types=1);

namespace Siel\Acumulus\Tests\Integration\PrestaShop\Mail;

use Siel\Acumulus\Tests\PrestaShop\TestCase;

/**
 * MailerTest tests whether the mailer class mails messages to the mail server.
 *
 * This test is mainly used to test if the mail feature still works in new versions of the
 * shop.
 */
class MailerTest extends TestCase
{
    public function testMailer(): void
    {
        $this->_testMailer(hasTextPart: false);
    }

    protected function assertMailServerReceivedMail(string $subject, ?string $bodyText, ?string $bodyHtml, bool $isBase64 = false): void
    {
        //PrestaShop adds [Acumulus PrestaShop Test] in front of the subject, resulting in
        // the original subject being cut off at 13 characters in the eml file name.
        $subject = '[Acumulus PrestaShop Test] ' . substr($subject, 0, 10);
        parent::assertMailServerReceivedMail($subject, $bodyText, $bodyHtml);
    }
}
