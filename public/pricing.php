<?php
/*
 * Purpose: Show tier options and handle simulated upgrades.
 */

require_once dirname(__DIR__) . '/src/auth.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();
$user  = require_login();
$flash = get_flash_message();
$tiers = get_tier_config();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newTier = trim($_POST['tier'] ?? '');

    if ($newTier === $user['tier']) {
        set_flash_message('info', "You are already on the " . ucfirst($newTier) . " plan.");
    } elseif (upgrade_tier($user['id'], $newTier)) {
        $user['tier'] = $newTier;
        set_flash_message('success', "You are now on the " . ucfirst($newTier) . " plan. Enjoy your new limits!");
    } else {
        set_flash_message('error', 'Something went wrong. Please try again.');
    }

    redirect('pricing.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal | Plans</title>
    <link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="brand-row">
                <div>
                    <p class="eyebrow">Choose your plan</p>
                    <h1>PantryPal Plans</h1>
                    <p class="hero-copy">Pick the plan that fits how often you cook. Upgrade or downgrade any time.</p>
                </div>
                <nav class="nav-links">
                    <a href="index.php">Search</a>
                    <a href="favorites.php">Favorites</a>
                    <a href="pricing.php" class="active">Plans</a>
                    <a href="logout.php">Log Out</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container page-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
        <?php endif; ?>

        <p class="section-copy" style="margin-bottom:1.5rem;">
            You are currently on the <strong><?= e(ucfirst($user['tier'])); ?></strong> plan.
        </p>

        <div class="pricing-grid">
            <?php foreach ($tiers as $key => $tier): ?>
                <div class="pricing-card <?= $key === $user['tier'] ? 'pricing-card--active' : ''; ?>">
                    <div class="pricing-header <?= e($tier['color']); ?>">
                        <h2><?= e($tier['label']); ?></h2>
                        <p class="pricing-price">
                            <?= $tier['price'] === 0 ? 'Free' : '$' . number_format($tier['price'], 2) . '/mo'; ?>
                        </p>
                    </div>
                    <div class="pricing-body">
                        <p class="pricing-desc"><?= e($tier['description']); ?></p>
                        <ul class="pricing-features">
                            <li>
                                <?= $tier['searches_per_day'] === PHP_INT_MAX
                                    ? 'Unlimited searches per day'
                                    : $tier['searches_per_day'] . ' searches per day'; ?>
                            </li>
                            <li><?= $tier['results_per_search']; ?> recipes per search</li>
                            <li>Save unlimited favorites</li>
                            <?php if ($key !== 'free'): ?>
                                <li>Savings calculator</li>
                            <?php endif; ?>
                            <?php if ($key === 'chef'): ?>
                                <li>Priority results</li>
                            <?php endif; ?>
                        </ul>

                        <?php if ($key === $user['tier']): ?>
                            <span class="current-plan-badge">Current Plan</span>
                        <?php else: ?>
                            <form action="pricing.php" method="post">
                                <input type="hidden" name="tier" value="<?= e($key); ?>">
                                <button type="submit" class="full-width-btn <?= $key === 'free' ? 'btn-secondary' : ''; ?>">
                                    <?= $tier['price'] === 0 ? 'Switch to Free' : 'Upgrade to ' . e($tier['label']); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="pricing-note">
            This is a simulated billing system for demonstration purposes. No real charges are made.
        </p>
    </main>
    <script src="assets/app.js?v=3"></script>
</body>
</html>
