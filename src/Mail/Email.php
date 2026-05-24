<?php

namespace Bazaar\Mail;

final class Email
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $from = 'orders@bazaar.test'
    ) {
    }
}
