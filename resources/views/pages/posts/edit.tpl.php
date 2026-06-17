<?php /** @var \App\Models\Post $post */ ?>
<div class="row d-flex justify-content-center g-3">
    <div class="col-md-9">
        <h1>EDIT POST</h1>
        <form action="/posts/<?= $post->id ?>" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input required type="email" value="<?= htmlspecialchars($post->email, ENT_QUOTES, 'UTF-8') ?>"
                       name="email" class="form-control" id="email" placeholder="name@example.com">
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" required value="<?= htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') ?>"
                       name="title" class="form-control" id="title">
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea required name="message" class="form-control" id="message" rows="3"><?= htmlspecialchars($post->message, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="mb-3 text-center">
                <button class="btn btn-success">SAVE</button>
            </div>
        </form>
    </div>
</div>
