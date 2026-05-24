<?php

namespace Bazaar\Mail;

final class SmtpMailer implements Mailer
{
    private array $sent = [];

    public function __construct(
        private readonly string $host,
        private readonly int $port = 587,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly int $timeout = 10
    ) {
    }

    public function send(Email $email): void
    {
        $socket = fsockopen($this->host, $this->port, $errno, $error, $this->timeout);
        if ($socket === false) {
            throw new \RuntimeException("SMTP connection failed: {$error} ({$errno})");
        }

        $this->expect($socket, 220);
        $this->command($socket, 'EHLO bazaar', 250);

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
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ]);
        fwrite($socket, $headers . "\r\n\r\n" . $email->body . "\r\n.\r\n");
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
        $response = (string) fgets($socket, 512);
        $code = (int) substr($response, 0, 3);
        if ($code !== $expected) {
            throw new \RuntimeException("SMTP expected {$expected}, got: " . trim($response));
        }
    }
}
