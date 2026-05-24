<?php

namespace Bazaar\Mail;

final class LogMailer implements Mailer
{
    private array $sent = [];

    public function __construct(private readonly string $logPath)
    {
    }

    public function send(Email $email): void
    {
        $this->sent[] = $email;

        $directory = dirname($this->logPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $entry = sprintf(
            "[%s] To: %s | From: %s | Subject: %s\n%s\n%s\n",
            gmdate('c'),
            $email->to,
            $email->from,
            $email->subject,
            $email->body,
            str_repeat('-', 60)
        );
        file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX);
    }

    public function sent(): array
    {
        return $this->sent;
    }
}
