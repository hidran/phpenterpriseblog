<?php
/**
 * @var \App\Models\Post $post
 * @var list<\App\Models\Comment> $comments
 */
?>
<div class="row d-flex justify-content-center g-3">
    <div class="col-md-9">
        <article>
            <h1><?= htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>
                <time datetime="<?= htmlspecialchars($post->datecreated, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post->datecreated, ENT_QUOTES, 'UTF-8') ?></time>
                by <span><a href="mailto:<?= htmlspecialchars($post->email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post->email, ENT_QUOTES, 'UTF-8') ?></a></span>
            </p>
            <p><?= htmlspecialchars($post->message, ENT_QUOTES, 'UTF-8') ?></p>
        </article>

        <div class="row d-flex justify-content-start g-3 mt-3">
            <?php if (isUserLoggedIn() && getUserId() === $post->userId): ?>
                <div class="col-md-3">
                    <form action="/posts/<?= $post->id ?>/edit" method="GET">
                        <button class="btn btn-success">EDIT</button>
                    </form>
                </div>
                <div class="col-md-3">
                    <form action="/posts/<?= $post->id ?>/delete" method="POST">
                        <button class="btn btn-danger">DELETE</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="row d-flex justify-content-start g-3 mt-3">
            <form action="/posts/<?= $post->id ?>/comments" method="POST">
                <?php if (!isUserLoggedIn()): ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input required type="email" name="email" class="form-control" id="email" placeholder="name@example.com">
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="comment" class="form-label">Message</label>
                    <textarea required name="comment" class="form-control" id="message" rows="3"></textarea>
                </div>
                <div class="mb-3 text-center">
                    <button class="btn btn-success">SAVE</button>
                </div>
            </form>

            <?php foreach ($comments as $comment): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <p><?= htmlspecialchars($comment->comment, ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-row align-items-center">
                                <p>
                                    <time datetime="<?= htmlspecialchars($comment->datecreated, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($comment->datecreated, ENT_QUOTES, 'UTF-8') ?></time>
                                    by <span><a href="mailto:<?= htmlspecialchars($comment->email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($comment->email, ENT_QUOTES, 'UTF-8') ?></a></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
