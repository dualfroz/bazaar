<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Repositories\UserRepository;
use Bazaar\Services\Auth;
use Bazaar\Support\Permissions;
use Bazaar\Support\Role;

final class PermissionsTest extends DatabaseTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->db);
    }

    private function authFor(int $userId): Auth
    {
        $_SESSION = [];
        $auth = new Auth(new Session(), $this->users);
        $auth->login($this->users->findById($userId));
        return $auth;
    }

    public function testManagerIsStaffButNotAdmin(): void
    {
        $id = $this->users->create('m@bazaar.test', 'hash', 'Manager', false, Role::manager);
        $auth = $this->authFor($id);

        $this->assertTrue($auth->isStaff());
        $this->assertFalse($auth->isAdmin());
        $this->assertSame(Role::manager, $auth->role());
    }

    public function testManagerCanRunCatalogButNotCouponsOrUsers(): void
    {
        $id = $this->users->create('m@bazaar.test', 'hash', 'Manager', false, Role::manager);
        $auth = $this->authFor($id);

        $this->assertTrue($auth->can(Permissions::catalogManage));
        $this->assertTrue($auth->can(Permissions::ordersManage));
        $this->assertTrue($auth->can(Permissions::reviewsModerate));
        $this->assertFalse($auth->can(Permissions::couponsManage));
        $this->assertFalse($auth->can(Permissions::usersManage));
    }

    public function testAdminCanDoEverything(): void
    {
        $id = $this->users->create('admin@bazaar.test', 'hash', 'Admin', true);
        $auth = $this->authFor($id);

        $this->assertTrue($auth->isAdmin());
        $this->assertTrue($auth->isStaff());
        $this->assertTrue($auth->can(Permissions::couponsManage));
        $this->assertTrue($auth->can(Permissions::usersManage));
    }

    public function testCustomerHasNoStaffAccess(): void
    {
        $id = $this->users->create('c@bazaar.test', 'hash', 'Customer');
        $auth = $this->authFor($id);

        $this->assertFalse($auth->isStaff());
        $this->assertFalse($auth->can(Permissions::catalogManage));
    }

    public function testSetRolePromotesAndDemotes(): void
    {
        $id = $this->users->create('u@bazaar.test', 'hash', 'User');

        $this->users->setRole($id, Role::admin);
        $this->assertTrue($this->authFor($id)->isAdmin());

        $this->users->setRole($id, Role::manager);
        $promoted = $this->authFor($id);
        $this->assertFalse($promoted->isAdmin());
        $this->assertTrue($promoted->isStaff());
    }
}
