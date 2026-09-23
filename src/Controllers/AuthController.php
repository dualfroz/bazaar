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
            $errors['name'] = $this->t('auth.errors.nameRequired');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = $this->t('auth.errors.emailInvalid');
        } elseif ($this->ctx->users->emailExists($email)) {
            $errors['email'] = $this->t('auth.errors.emailTaken');
        }
        if (strlen($password) < 8) {
            $errors['password'] = $this->t('auth.errors.passwordShort');
        }

        if ($errors !== []) {
            return $this->view('auth.register', [
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email],
            ], 'layout', 422);
        }

        $userId = $this->ctx->auth->register($email, $password, $name);
        $this->ctx->auth->login($this->ctx->users->findById($userId));
        $this->ctx->session->flash('success', $this->t('auth.welcome', ['name' => $name]));
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
                'errors' => ['email' => $this->t('auth.errors.credentials')],
                'old' => ['email' => $email],
            ], 'layout', 422);
        }

        $this->ctx->auth->login($user);
        $this->ctx->session->flash('success', $this->t('auth.signedIn'));
        return $this->redirect($user['isAdmin'] ? '/admin' : '/');
    }

    public function logout(): Response
    {
        $this->ctx->auth->logout();
        $this->ctx->session->flash('success', $this->t('auth.signedOut'));
        return $this->redirect('/');
    }
}
