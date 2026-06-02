<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;
use Bazaar\Support\Role;

final class UserController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.users.index', [
            'users' => $this->ctx->users->all(),
            'roles' => Role::all(),
        ], 'admin.layout');
    }

    public function updateRole(string $id): Response
    {
        $userId = (int) $id;
        $role = (string) $this->ctx->request->input('role', Role::customer);
        if (!in_array($role, Role::all(), true)) {
            return $this->redirect('/admin/users');
        }

        if ($userId === (int) $this->ctx->auth->id()) {
            $this->ctx->session->flash('error', 'You cannot change your own role.');
            return $this->redirect('/admin/users');
        }

        $this->ctx->users->setRole($userId, $role);
        $this->ctx->session->flash('success', 'Role updated.');
        return $this->redirect('/admin/users');
    }
}
