<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PostController extends BaseController
{
    public function __construct(
        View $view,
        private readonly PostRepository $posts,
        private readonly CommentRepository $comments,
    ) {
        parent::__construct($view);
    }

    /**
     * @param array<string, string> $args
     */
    public function index(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        return $this->respond($this->view->render('pages/posts/index', ['posts' => $this->posts->all()]));
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $post = $this->posts->findById((int) ($args['id'] ?? 0));
        if ($post === null) {
            return $this->respond($this->view->render('pages/errors/404'), 404);
        }
        return $this->respond($this->view->render('pages/posts/show', [
            'post'     => $post,
            'comments' => $this->comments->allForPost($post->id),
        ]));
    }

    /**
     * @param array<string, string> $args
     */
    public function create(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        return $this->respond($this->view->render('pages/posts/create'));
    }

    /**
     * @param array<string, string> $args
     */
    public function edit(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $post = $this->posts->findById((int) ($args['id'] ?? 0));
        if ($post === null) {
            return $this->redirect('/');
        }
        return $this->respond($this->view->render('pages/posts/edit', ['post' => $post]));
    }

    /**
     * @param array<string, string> $args
     */
    public function save(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $data = [
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'title'   => (string) ($post['title'] ?? ''),
            'message' => (string) ($post['message'] ?? ''),
        ];
        if (isset($args['id'])) {
            $this->posts->update((int) $args['id'], ['title' => $data['title'], 'message' => $data['message']]);
        } else {
            $this->posts->save($data);
        }
        return $this->redirect('/');
    }

    /**
     * @param array<string, string> $args
     */
    public function saveComment(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $post = (array) ($request->getParsedBody() ?? $_POST);
        $postId = (int) ($args['id'] ?? 0);
        $this->comments->save([
            'post_id' => $postId,
            'user_id' => (int) ($_SESSION['userData']['id'] ?? 0),
            'email'   => (string) ($post['email'] ?? ''),
            'comment' => (string) ($post['comment'] ?? ''),
        ]);
        return $this->redirect('/posts/' . $postId);
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (empty($_SESSION['loggedin'])) {
            return $this->redirect('/auth/login');
        }
        $this->posts->delete((int) ($args['id'] ?? 0));
        return $this->redirect('/');
    }
}
