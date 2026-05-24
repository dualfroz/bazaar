<?php

namespace Bazaar\Mail;

interface Mailer
{
    public function send(Email $email): void;

    public function sent(): array;
}
