<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A blogging platform">
    <meta name="author" content="Hidran Arias">
    <link rel='shortcut icon' href='data:image/x-icon;,' type='image/x-icon'>
    <title>Free blog</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"
          integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body class="d-flex flex-column h-100">

<header>
    <!-- Fixed navbar -->
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">COMMENTING SYSTEM</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="d-flex justify-content-between collapse navbar-collapse" id="navbarCollapse">
                <ul class="d-flex justify-content-between navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/posts">Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/posts/create">New post</a>
                    </li>
                </ul>
                <ul class="d-flex justify-content-end navbar-nav mb-2 mb-md-0">
                    <?php if (isUserLoggedIn()): ?>
                        <li class="nav-item order-2 order-md-1">
                            <a href="#" class="nav-link" title="settings">
                                <i class="fa fa-cog fa-fw fa-lg"></i>
                            </a>
                        </li>
                        <li class="nav-link">
                            <strong>Welcome <?= htmlspecialchars(getUserLoggedInFullname(), ENT_QUOTES, 'UTF-8') ?></strong>
                        </li>
                        <li class="m-1">&nbsp;</li>
                        <li class="nav-item">
                            <form class="form" role="form" method="post" action="/auth/logout">
                                <input type="hidden" name="action" value="logout">
                                <button class="btn btn-info">LOGOUT</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item d-flex">
                            <a href="/auth/signup" class="px-3 mx-2 nav-link btn btn-primary">SIGN UP</a>
                            <a href="/auth/login" class="px-3 mx-2 nav-link btn btn-success">LOGIN</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- Begin page content -->
<main class="flex-shrink-0 mx-3 pt-5 mt-3">
    <?= $content ?? '' ?>
</main>

<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <span class="text-muted">
            &copy;Hidran Arias
            <?= date('Y-m-d') ?>
        </span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>
