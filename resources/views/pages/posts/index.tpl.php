<?php
/** @var list<\App\Models\Post> $posts */
foreach ($posts as $post): ?>
    <article>
        <h2><a href="/posts/<?= $post->id ?>"><?= htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') ?></a></h2>
        <p>
            <time datetime="<?= htmlspecialchars($post->datecreated, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post->datecreated, ENT_QUOTES, 'UTF-8') ?></time>
            by <span><a href="mailto:<?= htmlspecialchars($post->email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post->email, ENT_QUOTES, 'UTF-8') ?></a></span>
        </p>
        <p><?= htmlspecialchars($post->message, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
<?php endforeach; ?>
