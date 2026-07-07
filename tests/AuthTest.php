<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Repositories\UserRepository;
use Bazaar\Services\Auth;

final class AuthTest extends DatabaseTestCase
{
    private UserRepository $users;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->db);
        $this->auth = new Auth(new Session(), $this->users);
    }

    public function testRegisterStoresAHashNotThePlaintext(): void
    {
        $userId = $this->auth->register('buyer@bazaar.test', 'sup3rsecret', 'Buyer');

        $user = $this->users->findById($userId);
        $this->assertNotSame('sup3rsecret', $user['passwordHash']);
        $this->assertTrue(password_verify('sup3rsecret', $user['passwordHash']));
    }

    public function testEmailIsNormalizedAndUniquenessDetected(): void
    {
        $this->auth->register('Mixed@Bazaar.test', 'sup3rsecret', 'Buyer');

        $this->assertTrue($this->users->emailExists('mixed@bazaar.test'));
        $this->assertNotNull($this->users->findByEmail('MIXED@BAZAAR.TEST'));
    }

    public function testAttemptSucceedsWithCorrectPassword(): void
    {
        $this->auth->register('ok@bazaar.test', 'correcthorse', 'Ok');

        $this->assertNotNull($this->auth->attempt('ok@bazaar.test', 'correcthorse'));
    }

    public function testAttemptFailsWithWrongPassword(): void
    {
        $this->auth->register('nope@bazaar.test', 'correcthorse', 'Nope');

        $this->assertNull($this->auth->attempt('nope@bazaar.test', 'wrongpassword'));
        $this->assertNull($this->auth->attempt('ghost@bazaar.test', 'correcthorse'));
    }

    public function testLoginEstablishesSessionAndIdentity(): void
    {
        $userId = $this->auth->register('session@bazaar.test', 'correcthorse', 'Session');
        $this->assertFalse($this->auth->check());

        $this->auth->login($this->users->findById($userId));

        $this->assertTrue($this->auth->check());
        $this->assertSame($userId, $this->auth->id());
        $this->assertSame('session@bazaar.test', $this->auth->user()['email']);
    }

    public function testLogoutClearsIdentity(): void
    {
        $userId = $this->auth->register('bye@bazaar.test', 'correcthorse', 'Bye');
        $this->auth->login($this->users->findById($userId));

        $this->auth->logout();

        $this->assertFalse($this->auth->check());
        $this->assertNull($this->auth->id());
    }

    public function testAdminFlagIsReflected(): void
    {
        $adminId = $this->users->create('boss@bazaar.test', password_hash('correcthorse', PASSWORD_DEFAULT), 'Boss', true);
        $this->auth->login($this->users->findById($adminId));

        $this->assertTrue($this->auth->isAdmin());
    }
}
