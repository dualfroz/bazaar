<?php

namespace Bazaar\Core;

final class Csrf
{
    private const KEY = 'csrfToken';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->put(self::KEY, $token);
        }
        return $token;
    }

    public function verify(?string $candidate): bool
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || !is_string($candidate) || $candidate === '') {
            return false;
        }
        return hash_equals($token, $candidate);
    }

    public function field(): string
    {
        return '<input type="hidden" name="csrfToken" value="' . e($this->token()) . '">';
    }
}
