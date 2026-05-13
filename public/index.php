<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Show the main search page and recipe results.
 */

require_once dirname(__DIR__) . '/src/db.php';
require_once dirname(__DIR__) . '/src/api.php';
require_once dirname(__DIR__) . '/src/validation.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();

$flash = get_flash_message();
$recipes = [];
$errorMessage = '';
$searchQuery = '';
$recentSearches = [];
$environmentWarnings = get_environment_warnings();

try {
    $pdo = get_pdo();

    // =============================================
    // HANDLE SEARCH SUBMIT
    // =============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $validation = validate_ingredient_input($_POST['ingredients'] ?? '');

        if (!$validation['valid']) {
            $errorMessage = $validation['error'];
            $searchQuery = trim((string) ($_POST['ingredients'] ?? ''));
        } else {
            $searchQuery = $validation['query'];

            // Save the cleaned search text so we can show it again later ///
            $historyStmt = $pdo->prepare('INSERT INTO search_history (ingredients_query) VALUES (:ingredients_query)');
            $historyStmt->execute([
                ':ingredients_query' => $validation['query'],
            ]);

            // Call the backend API helper instead of exposing the key in the browser ///
            $apiResponse = search_recipes_by_ingredients($validation['api_query']);

            if (!$apiResponse['success']) {
                $errorMessage = $apiResponse['error'];
            } else {
                $recipes = $apiResponse['data'];

                if (empty($recipes)) {
                    $errorMessage = 'No recipes matched those ingredients. Try adding one or two more ingredients.';
                }
            }
        }
    }

    // =============================================
    // LOAD RECENT SEARCHES
    // =============================================
    $historyStmt = $pdo->query('SELECT ingredients_query, created_at FROM search_history ORDER BY created_at DESC LIMIT 6');
    $recentSearches = $historyStmt->fetchAll();
} catch (RuntimeException $exception) {
    $errorMessage = $exception->getMessage();
} catch (Throwable $exception) {
    $errorMessage = 'Something went wrong while loading PantryPal. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal | Find Recipes From Your Ingredients</title>
    <link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>
    <!-- Main app header + nav -->
    <header class="site-header">
        <div class="container">
            <div class="brand-row">
                <div>
                    <p class="eyebrow">Try this instead of going out to eat</p>
                    <h1>PantryPal</h1>
                    <p class="hero-copy">Type in the ingredients you already have, and PantryPal will help you find recipes you can make tonight.</p>
                </div>
                <nav class="nav-links">
                    <a href="index.php" class="active">Search</a>
                    <a href="favorites.php">Favorites</a>
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

        <!-- Ingredient search form -->
        <section class="panel search-panel">
            <h2>Search by Ingredients</h2>
            <p class="section-copy">Enter ingredients separated by commas. Example: <span>chicken, rice, garlic, onion</span></p>

            <form action="index.php" method="post" class="search-form" novalidate>
                <label for="ingredients" class="sr-only">Ingredients</label>
                <textarea id="ingredients" name="ingredients" rows="3" placeholder="ex: eggs, spinach, cheese"><?= e($searchQuery); ?></textarea>
                <div class="form-meta">
                    <small id="ingredient-help">Use commas to separate ingredients. Max 500 characters.</small>
                    <small id="ingredient-count"><?= strlen($searchQuery); ?>/500</small>
                </div>
                <button type="submit">Find Recipes</button>
            </form>
        </section>

        <!-- Results and recent searches -->
        <section class="content-grid">
            <div class="main-column">
                <div class="section-heading">
                    <h2>Recipe Results</h2>
                    <?php if (!empty($recipes)): ?>
                        <p><?= count($recipes); ?> recipes found for "<?= e($searchQuery); ?>"</p>
                    <?php else: ?>
                        <p>Search results will show up here.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($recipes)): ?>
                    <div class="recipe-grid">
                        <?php foreach ($recipes as $recipe): ?>
                            <?php
                            // Turn Spoonacular ingredient arrays into plain text for display and saving!!!!///
                            $usedList = ingredient_names_to_text($recipe['usedIngredients'] ?? []);
                            $missedList = ingredient_names_to_text($recipe['missedIngredients'] ?? []);
                            ?>
                            <article class="recipe-card">
                                <img src="<?= e($recipe['image'] ?? ''); ?>" alt="<?= e($recipe['title'] ?? 'Recipe image'); ?>" class="recipe-image">
                                <div class="recipe-body">
                                    <h3><?= e($recipe['title'] ?? 'Untitled Recipe'); ?></h3>
                                    <div class="recipe-stats">
                                        <span>You have: <?= (int) ($recipe['usedIngredientCount'] ?? 0); ?></span>
                                        <span>Still need: <?= (int) ($recipe['missedIngredientCount'] ?? 0); ?></span>
                                    </div>
                                    <p><strong>Have:</strong> <?= e($usedList !== '' ? $usedList : 'None listed'); ?></p>
                                    <p><strong>Need:</strong> <?= e($missedList !== '' ? $missedList : 'Nothing else needed'); ?></p>
                                    <div class="card-actions">
                                        <a href="recipe_details.php?id=<?= (int) ($recipe['id'] ?? 0); ?>" class="secondary-button">How to Make It</a>

                                        <form action="save_recipe.php" method="post" class="save-form">
                                            <!-- Hidden fields pass recipe info to the PHP save handler. -->
                                            <input type="hidden" name="recipe_api_id" value="<?= (int) ($recipe['id'] ?? 0); ?>">
                                            <input type="hidden" name="title" value="<?= e($recipe['title'] ?? ''); ?>">
                                            <input type="hidden" name="image_url" value="<?= e($recipe['image'] ?? ''); ?>">
                                            <input type="hidden" name="used_ingredients" value="<?= e($usedList); ?>">
                                            <input type="hidden" name="missed_ingredients" value="<?= e($missedList); ?>">
                                            <button type="submit">Save Favorite</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Ready when you are</h3>
                        <p>Try a few ingredients from your pantry and PantryPal will look for matching recipes.</p>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="panel sidebar-panel">
                <h2>Recent Searches</h2>
                <?php if (!empty($recentSearches)): ?>
                    <ul class="history-list">
                        <?php foreach ($recentSearches as $item): ?>
                            <li>
                                <span><?= e($item['ingredients_query']); ?></span>
                                <small><?= e(date('M j, Y g:i A', strtotime($item['created_at']))); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No searches saved yet.</p>
                <?php endif; ?>
            </aside>
        </section>
    </main>
    <script src="assets/app.js?v=2"></script>
</body>
</html>
