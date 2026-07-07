<?php

namespace Bazaar\Tests;

use Bazaar\Core\Csrf;
use Bazaar\Core\Session;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    private Csrf $csrf;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->csrf = new Csrf(new Session());
    }

    public function testTokenIsStableWithinASession(): void
    {
        $this->assertSame($this->csrf->token(), $this->csrf->token());
    }

    public function testVerifyAcceptsTheIssuedToken(): void
    {
        $token = $this->csrf->token();

        $this->assertTrue($this->csrf->verify($token));
    }

    public function testVerifyRejectsAWrongToken(): void
    {
        $this->csrf->token();

        $this->assertFalse($this->csrf->verify('forged-token'));
    }

    public function testVerifyRejectsEmptyAndNull(): void
    {
        $this->csrf->token();

        $this->assertFalse($this->csrf->verify(''));
        $this->assertFalse($this->csrf->verify(null));
    }

    public function testVerifyFailsWhenNoTokenWasIssued(): void
    {
        $this->assertFalse($this->csrf->verify('anything'));
    }

    public function testFieldEmbedsTheToken(): void
    {
        $token = $this->csrf->token();

        $this->assertStringContainsString($token, $this->csrf->field());
        $this->assertStringContainsString('name="csrfToken"', $this->csrf->field());
    }
}
