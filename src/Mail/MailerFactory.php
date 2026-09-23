<?php

namespace Bazaar\Mail;

use Bazaar\Core\Config;

final class MailerFactory
{
    public const drivers = ['log', 'smtp'];

    public static function fromConfig(): Mailer
    {
        $driver = strtolower(trim((string) Config::get('MAIL_DRIVER', 'log')));

        return match ($driver) {
            'log' => new LogMailer(basePath('storage/logs/mail.log')),
            'smtp' => self::smtp(),
            default => throw new \InvalidArgumentException(
                "Unsupported MAIL_DRIVER \"{$driver}\". Use one of: " . implode(', ', self::drivers)
            ),
        };
    }

    private static function smtp(): SmtpMailer
    {
        $host = trim((string) Config::get('MAIL_HOST', ''));
        if ($host === '') {
            throw new \InvalidArgumentException('MAIL_HOST is required when MAIL_DRIVER=smtp');
        }

        $encryption = strtolower(trim((string) Config::get('MAIL_ENCRYPTION', SmtpMailer::encryptionTls)));
        $encryptions = [SmtpMailer::encryptionNone, SmtpMailer::encryptionTls, SmtpMailer::encryptionSsl];
        if (!in_array($encryption, $encryptions, true)) {
            throw new \InvalidArgumentException(
                "Unsupported MAIL_ENCRYPTION \"{$encryption}\". Use one of: " . implode(', ', $encryptions)
            );
        }

        $username = (string) Config::get('MAIL_USERNAME', '');
        $password = (string) Config::get('MAIL_PASSWORD', '');

        return new SmtpMailer(
            $host,
            (int) Config::get('MAIL_PORT', 587),
            $username === '' ? null : $username,
            $username === '' ? null : $password,
            $encryption
        );
    }
}
