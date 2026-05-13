<?php
/*
 * Purpose: User login page.
 */

require_once dirname(__DIR__) . '/src/auth.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();

if (get_logged_in_user()) {
    redirect('index.php');
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = attempt_login($username, $password);

    if (!$result['success']) {
        $error = $result['error'];
    } else {
        login_user($result['user']);
        set_flash_message('success', 'Welcome back, ' . $result['user']['username'] . '!');
        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal | Log In</title>
    <link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="brand-row">
                <div>
                    <p class="eyebrow">Welcome back</p>
                    <h1>Log In</h1>
                    <p class="hero-copy">Log in to access your saved recipes, search history, and tier benefits.</p>
                </div>
                <nav class="nav-links">
                    <a href="login.php" class="active">Log In</a>
                    <a href="register.php">Register</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container page-content">
        <div class="auth-wrap">
            <section class="panel auth-panel">
                <h2>Log in to PantryPal</h2>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error"><?= e($error); ?></div>
                <?php endif; ?>

                <?php $flash = get_flash_message(); if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
                <?php endif; ?>

                <form action="login.php" method="post" class="auth-form" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username"
                               value="<?= e($username); ?>"
                               placeholder="Your username"
                               required autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="Your password"
                               required autocomplete="current-password">
                    </div>

                    <button type="submit" class="full-width-btn">Log In</button>
                </form>

                <p class="auth-switch">Don't have an account? <a href="register.php">Create one free</a></p>
            </section>
        </div>
    </main>
    <script src="assets/app.js?v=3"></script>
</body>
</html>
