<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends BaseController
{
    public function __construct(
        View $view,
        private readonly AuthService $auth,
    ) {
        parent::__construct($view);
    }

    /**
     * @param array<string, string> $args
     */
    public function showLogin(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return $this->respond($this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => false,
        ]));
    }

    /**
     * @param array<string, string> $args
     */
    public function showSignup(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return $this->respond($this->view->render('pages/auth/login', [
            'token'  => $this->csrfToken(),
            'signup' => true,
        ]));
    }

    /**
     * @param array<string, string> $args
     */
    public function login(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $result = $this->auth->verifyLogin(
            email: (string) ($post['email'] ?? ''),
            password: (string) ($post['password'] ?? ''),
            token: (string) ($post['_csrf'] ?? ''),
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

        if (strtoupper($request->getHeaderLine('x-requested-with')) === 'XMLHTTPREQUEST') {
            return $this->json(['success' => $result->success, 'message' => $result->message]);
        }
        return $this->redirect($result->success ? '/' : '/auth/login');
    }

    /**
     * @param array<string, string> $args
     */
    public function signup(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $email    = (string) ($post['email'] ?? '');
        $password = (string) ($post['password'] ?? '');
        $username = (string) ($post['username'] ?? '');
        $result = $this->auth->verifySignup(
            email: $email,
            password: $password,
            token: (string) ($post['_csrf'] ?? ''),
            sessionToken: (string) ($_SESSION['csrf'] ?? ''),
        );

        if ($result->success) {
            $user = $this->auth->createUser($username !== '' ? $username : $email, $email, $password);
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['userData'] = [
                'id'       => $user->id,
                'email'    => $user->email,
                'username' => $user->username,
                'roletype' => 'user',
            ];
        } else {
            $_SESSION['message'] = $result->message;
        }

        if (strtoupper($request->getHeaderLine('x-requested-with')) === 'XMLHTTPREQUEST') {
            return $this->json(['success' => $result->success, 'message' => $result->message]);
        }
        return $this->redirect($result->success ? '/' : '/auth/signup');
    }

    /**
     * @param array<string, string> $args
     */
    public function logout(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $_SESSION = [];
        return $this->redirect('/auth/login');
    }

    private function csrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = $token;
        return $token;
    }
}
