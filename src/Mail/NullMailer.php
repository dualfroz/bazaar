<?php

namespace Bazaar\Mail;

final class NullMailer implements Mailer
{
    private array $sent = [];

    public function send(Email $email): void
    {
        $this->sent[] = $email;
    }

    public function sent(): array
    {
        return $this->sent;
    }
}
