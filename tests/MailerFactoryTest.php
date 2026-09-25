<?php

namespace Bazaar\Tests;

use Bazaar\Core\Config;
use Bazaar\Mail\LogMailer;
use Bazaar\Mail\MailerFactory;
use Bazaar\Mail\NullMailer;
use Bazaar\Mail\OrderMailer;
use Bazaar\Mail\SmtpMailer;
use PHPUnit\Framework\TestCase;

final class MailerFactoryTest extends TestCase
{
    private const mailKeys = ['MAIL_DRIVER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION'];

    private array $saved = [];

    protected function setUp(): void
    {
        foreach (self::mailKeys as $key) {
            $this->saved[$key] = Config::get($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $key => $value) {
            Config::set($key, $value);
        }
    }

    public function testDefaultDriverIsLog(): void
    {
        $this->assertInstanceOf(LogMailer::class, MailerFactory::fromConfig());
    }

    public function testDriverNameIsCaseInsensitive(): void
    {
        Config::set('MAIL_DRIVER', ' LOG ');
        $this->assertInstanceOf(LogMailer::class, MailerFactory::fromConfig());
    }

    public function testSmtpDriverBuildsSmtpMailer(): void
    {
        Config::set('MAIL_DRIVER', 'smtp');
        Config::set('MAIL_HOST', 'smtp.example.com');
        Config::set('MAIL_ENCRYPTION', 'ssl');

        $this->assertInstanceOf(SmtpMailer::class, MailerFactory::fromConfig());
    }

    public function testSmtpDriverRequiresHost(): void
    {
        Config::set('MAIL_DRIVER', 'smtp');
        Config::set('MAIL_HOST', '');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MAIL_HOST');
        MailerFactory::fromConfig();
    }

    public function testUnknownEncryptionIsRejected(): void
    {
        Config::set('MAIL_DRIVER', 'smtp');
        Config::set('MAIL_HOST', 'smtp.example.com');
        Config::set('MAIL_ENCRYPTION', 'starttls');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MAIL_ENCRYPTION');
        MailerFactory::fromConfig();
    }

    public function testUnknownDriverIsRejected(): void
    {
        Config::set('MAIL_DRIVER', 'sendmail');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sendmail');
        MailerFactory::fromConfig();
    }

    public function testOrderMailerSendsFromConfiguredAddress(): void
    {
        $mailer = new NullMailer();
        $orderMailer = new OrderMailer($mailer, 'usd', 'Bazaar', 'shop@example.com');

        $orderMailer->statusUpdate('buyer@example.com', ['id' => 7, 'status' => 'fulfilled', 'locale' => 'en']);

        $this->assertSame('shop@example.com', $mailer->sent()[0]->from);
        $this->assertSame('buyer@example.com', $mailer->sent()[0]->to);
    }
}
