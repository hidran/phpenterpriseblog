<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Support\View;

final class AuthController extends BaseController
{
    public function __construct(
        View $view,
        private readonly AuthService $auth,
        private readonly Request $request,
    ) {
        parent::__construct($view);
    }

    public function showLogin(): void
    {
        $this->content = $this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => false,
        ]);
    }

    public function showSignup(): void
    {
        $this->content = $this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => true,
        ]);
    }

    public function login(): void
    {
        $result = $this->auth->verifyLogin(
            email: $this->request->postString('email'),
            password: $this->request->postString('password'),
            token: $this->request->postString('_csrf'),
            sessionToken: (string) ($_SESSION['csrf'] ?? ''),
        );

        if ($result->success && $result->user !== null) {
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = [
                'id'       => $result->user->id,
                'email'    => $result->user->email,
                'username' => $result->user->username,
                'roletype' => $result->user->roletype,
            ];
        } else {
            $_SESSION['message'] = $result->message;
        }

        if (strtoupper($this->request->header('x-requested-with') ?? '') === 'XMLHTTPREQUEST') {
            Response::json(['success' => $result->success, 'message' => $result->message]);
        }
        Response::redirect($result->success ? '/' : '/auth/login');
    }

    public function signup(): void
    {
        $email    = $this->request->postString('email');
        $password = $this->request->postString('password');
        $username = $this->request->postString('username');
        $result   = $this->auth->verifySignup(
            email: $email,
            password: $password,
            token: $this->request->postString('_csrf'),
            sessionToken: (string) ($_SESSION['csrf'] ?? ''),
        );

        if ($result->success) {
            $user = $this->auth->createUser($username !== '' ? $username : $email, $email, $password);
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = ['id' => $user->id, 'email' => $user->email, 'username' => $user->username, 'roletype' => 'user'];
        } else {
            $_SESSION['message'] = $result->message;
        }

        if (strtoupper($this->request->header('x-requested-with') ?? '') === 'XMLHTTPREQUEST') {
            Response::json(['success' => $result->success, 'message' => $result->message]);
        }
        Response::redirect($result->success ? '/' : '/auth/signup');
    }

    public function logout(): void
    {
        $_SESSION = [];
        Response::redirect('/auth/login');
    }

    private function csrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = $token;
        return $token;
    }
}
