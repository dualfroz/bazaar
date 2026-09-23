<?php

namespace Bazaar\Mail;

final class SmtpMailer implements Mailer
{
    public const encryptionNone = 'none';
    public const encryptionTls = 'tls';
    public const encryptionSsl = 'ssl';

    private array $sent = [];

    public function __construct(
        private readonly string $host,
        private readonly int $port = 587,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly string $encryption = self::encryptionTls,
        private readonly int $timeout = 10
    ) {
    }

    public function send(Email $email): void
    {
        $address = $this->encryption === self::encryptionSsl ? 'ssl://' . $this->host : $this->host;
        $socket = fsockopen($address, $this->port, $errno, $error, $this->timeout);
        if ($socket === false) {
            throw new \RuntimeException("SMTP connection failed: {$error} ({$errno})");
        }
        stream_set_timeout($socket, $this->timeout);

        $this->expect($socket, 220);
        $this->command($socket, 'EHLO bazaar', 250);

        if ($this->encryption === self::encryptionTls) {
            $this->command($socket, 'STARTTLS', 220);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new \RuntimeException('SMTP STARTTLS negotiation failed');
            }
            $this->command($socket, 'EHLO bazaar', 250);
        }

        if ($this->username !== null && $this->password !== null) {
            $this->command($socket, 'AUTH LOGIN', 334);
            $this->command($socket, base64_encode($this->username), 334);
            $this->command($socket, base64_encode($this->password), 235);
        }

        $this->command($socket, 'MAIL FROM:<' . $email->from . '>', 250);
        $this->command($socket, 'RCPT TO:<' . $email->to . '>', 250);
        $this->command($socket, 'DATA', 354);

        $headers = implode("\r\n", [
            'From: ' . $email->from,
            'To: ' . $email->to,
            'Subject: ' . $email->subject,
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ]);
        $body = preg_replace('/^\./m', '..', preg_replace("/\r?\n/", "\r\n", $email->body));
        fwrite($socket, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
        $this->expect($socket, 250);

        $this->command($socket, 'QUIT', 221);
        fclose($socket);

        $this->sent[] = $email;
    }

    public function sent(): array
    {
        return $this->sent;
    }

    private function command($socket, string $line, int $expected): void
    {
        fwrite($socket, $line . "\r\n");
        $this->expect($socket, $expected);
    }

    private function expect($socket, int $expected): void
    {
        do {
            $response = (string) fgets($socket, 512);
            $code = (int) substr($response, 0, 3);
        } while ($code === $expected && ($response[3] ?? ' ') === '-');

        if ($code !== $expected) {
            throw new \RuntimeException("SMTP expected {$expected}, got: " . trim($response));
        }
    }
}
