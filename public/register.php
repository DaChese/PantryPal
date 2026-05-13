<?php
/*
 * Purpose: New user registration page.
 */

require_once dirname(__DIR__) . '/src/auth.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();

// Already logged in - send them home
if (get_logged_in_user()) {
    redirect('index.php');
}

$error   = '';
$success = '';
$fields  = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields['username'] = trim($_POST['username'] ?? '');
    $fields['email']    = trim($_POST['email'] ?? '');
    $password           = $_POST['password'] ?? '';
    $confirm            = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = register_user($fields['username'], $fields['email'], $password);

        if (!$result['success']) {
            $error = $result['error'];
        } else {
            login_user($result['user']);
            set_flash_message('success', 'Welcome to PantryPal, ' . $result['user']['username'] . '!');
            redirect('index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal | Create Account</title>
    <link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="brand-row">
                <div>
                    <p class="eyebrow">Get started for free</p>
                    <h1>Create Account</h1>
                    <p class="hero-copy">Join PantryPal to save favorites, track searches, and see how much you could save cooking at home.</p>
                </div>
                <nav class="nav-links">
                    <a href="login.php">Log In</a>
                    <a href="register.php" class="active">Register</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container page-content">
        <div class="auth-wrap">
            <section class="panel auth-panel">
                <h2>Create your account</h2>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error"><?= e($error); ?></div>
                <?php endif; ?>

                <form action="register.php" method="post" class="auth-form" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username"
                               value="<?= e($fields['username']); ?>"
                               placeholder="e.g. pantry_chef"
                               maxlength="50" required autocomplete="username">
                        <small>Letters, numbers, and underscores only.</small>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?= e($fields['email']); ?>"
                               placeholder="you@example.com"
                               required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="At least 8 characters"
                               minlength="8" required autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Repeat your password"
                               minlength="8" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="full-width-btn">Create Account</button>
                </form>

                <p class="auth-switch">Already have an account? <a href="login.php">Log in here</a></p>
            </section>
        </div>
    </main>
    <script src="assets/app.js?v=3"></script>
</body>
</html>
