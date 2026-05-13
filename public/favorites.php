<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Show saved recipes and let the user update or delete them.
 */

require_once dirname(__DIR__) . '/src/db.php';
require_once dirname(__DIR__) . '/src/helpers.php';
require_once dirname(__DIR__) . '/src/auth.php';

ensure_session_started();
$user = require_login();

$flash = get_flash_message();
$savedRecipes = [];
$errorMessage = '';
$tier = $user['tier'] ?? 'free';

// Show setup warnings here too so hosting/config issues are easier to spot //
$environmentWarnings = get_environment_warnings();

try {
    $pdo = get_pdo();

    // =============================================
    // LOAD SAVED RECIPES (user-specific)
    // =============================================
    $stmt = $pdo->prepare('SELECT * FROM saved_recipes WHERE user_id = :uid ORDER BY created_at DESC');
    $stmt->execute([':uid' => $user['id']]);
    $savedRecipes = $stmt->fetchAll();
} catch (RuntimeException $exception) {
    $errorMessage = $exception->getMessage();
} catch (Throwable $exception) {
    $errorMessage = 'Something went wrong while loading your favorites.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal | Favorites</title>
    <link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>
    <!-- Favorites page header -->
    <header class="site-header">
        <div class="container">
            <div class="brand-row">
                <div>
                    <p class="eyebrow">Your saved recipes</p>
                    <h1>Your Favorite Recipes</h1>
                    <p class="hero-copy">Recipes you've saved for later. Add notes, revisit directions, or remove ones you no longer need.</p>
                </div>
                <nav class="nav-links">
                    <a href="index.php">Search</a>
                    <a href="favorites.php" class="active">Favorites</a>
                    <a href="pricing.php">
                        <span class="tier-badge tier-badge--<?= e($tier); ?>"><?= e(ucfirst($tier)); ?></span>
                        Plans
                    </a>
                    <a href="logout.php">Log Out</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container page-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?>">
                <?= e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($environmentWarnings)): ?>
            <div class="alert alert-info">
                <strong>Setup warning:</strong> <?= e(implode(' | ', $environmentWarnings)); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <?= e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Quick count at the top of the page -->
        <section class="section-heading">
            <h2>Saved Recipes</h2>
            <p><?= count($savedRecipes); ?> recipe(s) saved</p>
        </section>

        <?php if (!empty($savedRecipes)): ?>
            <div class="recipe-grid">
                <?php foreach ($savedRecipes as $recipe): ?>
                    <article class="recipe-card">
                        <img src="<?= e($recipe['image_url']); ?>" alt="<?= e($recipe['title']); ?>" class="recipe-image">
                        <div class="recipe-body">
                            <h3><?= e($recipe['title']); ?></h3>
                            <p><strong>Used ingredients:</strong> <?= e($recipe['used_ingredients'] ?: 'Not available'); ?></p>
                            <p><strong>Missing ingredients:</strong> <?= e($recipe['missed_ingredients'] ?: 'None listed'); ?></p>
                            <p><strong>Notes:</strong> <?= e($recipe['notes'] ?: 'No notes yet'); ?></p>
                            <p><strong>Saved on:</strong> <?= e(date('M j, Y g:i A', strtotime($recipe['created_at']))); ?></p>

                            <form action="update_recipe.php" method="post" class="note-form">
                                <!-- Notes give us a real update action for the CRUD requirement. -->
                                <input type="hidden" name="id" value="<?= (int) $recipe['id']; ?>">
                                <label for="notes-<?= (int) $recipe['id']; ?>">Update Note</label>
                                <textarea id="notes-<?= (int) $recipe['id']; ?>" name="notes" rows="3" maxlength="500" placeholder="Add a quick note about this recipe..."><?= e($recipe['notes'] ?? ''); ?></textarea>
                                <button type="submit">Update Note</button>
                            </form>

                            <div class="card-actions">
                                <a href="recipe_details.php?id=<?= (int) $recipe['recipe_api_id']; ?>" class="secondary-button">How to Make It</a>

                                <!-- Open the original recipe in a new tab if the user wants full instructions. -->
                                <a href="<?= e($recipe['source_url']); ?>" target="_blank" rel="noopener noreferrer" class="secondary-button">View Recipe</a>

                                <form action="delete_recipe.php" method="post">
                                    <input type="hidden" name="id" value="<?= (int) $recipe['id']; ?>">
                                    <button type="submit" class="danger-button">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No favorites saved yet</h3>
                <p>Search for recipes on the home page and save a few favorites to see them here.</p>
                <a href="index.php" class="secondary-button">Go to Search</a>
            </div>
        <?php endif; ?>
    </main>
    <script src="assets/app.js?v=2"></script>
</body>
</html>
