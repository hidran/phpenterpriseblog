<?php
/**
 * @var string $token
 * @var bool   $signup
 */
?>
<div class="row d-flex justify-content-center g-3">
    <div class="col-md-9">
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>
                <?php $_SESSION['message'] = ''; ?>
            </div>
        <?php endif; ?>

        <h1><?= $signup ? 'Sign up' : 'Sign in' ?></h1>

        <form id="loginform" action="<?= $signup ? '/auth/signup' : '/auth/login' ?>" method="POST">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

            <?php if ($signup): ?>
                <div class="form-group">
                    <label for="username">User name</label>
                    <input class="form-control" name="username" type="text" value="" id="username">
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input required type="email" value="" name="email" class="form-control"
                       id="email" placeholder="name@example.com">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" required value="" name="password" class="form-control" id="password">
            </div>
            <div class="mb-3 text-center">
                <button class="btn btn-success"><?= $signup ? 'SIGN UP' : 'SIGN IN' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    $(function () {
        $('#loginform').on('submit', function (evt) {
            evt.preventDefault();
            const data = $(this).serialize();
            $.ajax({
                method: 'post',
                data: data,
                url: $(this).attr('action'),
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data) {
                        alert(data.message);
                        if (data.success) {
                            location.href = '/';
                        }
                    }
                },
                failure: function () {
                    alert('PROBLEM CONTACTING SERVER');
                },
            });
        });
    });
</script>
