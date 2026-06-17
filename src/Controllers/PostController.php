<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Support\View;

final class PostController extends BaseController
{
    public function __construct(
        View $view,
        private readonly PostRepository $posts,
        private readonly CommentRepository $comments,
        private readonly Request $request,
    ) {
        parent::__construct($view);
    }

    public function index(): void
    {
        $this->content = $this->view->render('pages/posts/index', ['posts' => $this->posts->all()]);
    }

    public function show(string $id): void
    {
        $postId = (int) $id;
        $post   = $this->posts->findById($postId);
        if ($post === null) {
            http_response_code(404);
            $this->content = $this->view->render('pages/errors/404');
            return;
        }
        $this->content = $this->view->render('pages/posts/show', [
            'post'     => $post,
            'comments' => $this->comments->allForPost($postId),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->content = $this->view->render('pages/posts/create');
    }

    public function edit(string $id): void
    {
        $this->requireLogin();
        $post = $this->posts->findById((int) $id);
        if ($post === null) {
            Response::redirect('/');
        }
        $this->content = $this->view->render('pages/posts/edit', ['post' => $post]);
    }

    public function save(?string $id = null): void
    {
        $this->requireLogin();
        $data = [
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'title'   => $this->request->postString('title'),
            'message' => $this->request->postString('message'),
        ];
        if ($id === null) {
            $this->posts->save($data);
        } else {
            $this->posts->update((int) $id, ['title' => $data['title'], 'message' => $data['message']]);
        }
        Response::redirect('/');
    }

    public function saveComment(string $id): void
    {
        $this->requireLogin();
        $this->comments->save([
            'post_id' => (int) $id,
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'email'   => $this->request->postString('email'),
            'comment' => $this->request->postString('comment'),
        ]);
        Response::redirect('/posts/' . $id);
    }

    public function delete(string $id): void
    {
        $this->requireLogin();
        $this->posts->delete((int) $id);
        Response::redirect('/');
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['loggedin'])) {
            Response::redirect('/auth/login');
        }
    }
}
