<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;

final class AuthController extends Controller
{
    public function showRegister(): Response
    {
        return $this->view('auth.register', ['errors' => [], 'old' => []]);
    }

    public function register(): Response
    {
        $request = $this->ctx->request;
        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Please enter your name.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif ($this->ctx->users->emailExists($email)) {
            $errors['email'] = 'An account with this email already exists.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($errors !== []) {
            return $this->view('auth.register', [
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email],
            ], 'layout', 422);
        }

        $userId = $this->ctx->auth->register($email, $password, $name);
        $this->ctx->auth->login($this->ctx->users->findById($userId));
        $this->ctx->session->flash('success', 'Welcome to Bazaar, ' . $name . '.');
        return $this->redirect('/');
    }

    public function showLogin(): Response
    {
        return $this->view('auth.login', ['errors' => [], 'old' => []]);
    }

    public function login(): Response
    {
        $request = $this->ctx->request;
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        $user = $this->ctx->auth->attempt($email, $password);
        if ($user === null) {
            return $this->view('auth.login', [
                'errors' => ['email' => 'Those credentials do not match our records.'],
                'old' => ['email' => $email],
            ], 'layout', 422);
        }

        $this->ctx->auth->login($user);
        $this->ctx->session->flash('success', 'Signed in successfully.');
        return $this->redirect($user['isAdmin'] ? '/admin' : '/');
    }

    public function logout(): Response
    {
        $this->ctx->auth->logout();
        $this->ctx->session->flash('success', 'You have been signed out.');
        return $this->redirect('/');
    }
}
