<?php
/*
 * Author: Aldo Medina
 * Created on: 4/14/2026
 * Last updated: 4/18/2026
 * Purpose: Show ingredients and directions for one recipe.
 */

require_once dirname(__DIR__) . '/src/api.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();

$recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$recipe = null;
$ingredients = [];
$steps = [];
$errorMessage = '';
$environmentWarnings = get_environment_warnings();

// =============================================
// LOAD RECIPE DETAILS
// =============================================
if (!$recipeId) {
    $errorMessage = 'Recipe ID is missing or invalid.';
} else {
    $recipeResponse = fetch_recipe_information($recipeId);

    if (!$recipeResponse['success']) {
        $errorMessage = $recipeResponse['error'];
    } else {
        $recipe = $recipeResponse['data'];

        foreach ($recipe['extendedIngredients'] ?? [] as $ingredient) {
            if (!empty($ingredient['original'])) {
                $ingredients[] = $ingredient['original'];
            }
        }

        foreach ($recipe['analyzedInstructions'] ?? [] as $instructionSet) {
            foreach ($instructionSet['steps'] ?? [] as $step) {
                if (!empty($step['step'])) {
                    $steps[] = $step['step'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal | Recipe Details</title>
    <link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="brand-row">
                <div>
                    <p class="eyebrow">CIS 435 Project 4</p>
                    <h1><?= e($recipe['title'] ?? 'Recipe Details'); ?></h1>
                    <p class="hero-copy">Take a look at the ingredients and step-by-step directions for this recipe.</p>
                </div>
                <nav class="nav-links">
                    <a href="index.php" class="active">Search</a>
                    <a href="favorites.php">Favorites</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container page-content">
        <?php if (!empty($environmentWarnings)): ?>
            <div class="alert alert-info">
                <strong>Setup warning:</strong> <?= e(implode(' | ', $environmentWarnings)); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <?= e($errorMessage); ?>
            </div>
            <a href="index.php" class="secondary-button detail-back">Back to Search</a>
        <?php elseif ($recipe): ?>
            <section class="panel detail-panel">
                <?php if (!empty($recipe['image'])): ?>
                    <img src="<?= e($recipe['image']); ?>" alt="<?= e($recipe['title'] ?? 'Recipe image'); ?>" class="detail-image">
                <?php endif; ?>

                <div class="detail-meta">
                    <?php if (!empty($recipe['readyInMinutes'])): ?>
                        <span>Ready in: <?= (int) $recipe['readyInMinutes']; ?> min</span>
                    <?php endif; ?>
                    <?php if (!empty($recipe['servings'])): ?>
                        <span>Servings: <?= (int) $recipe['servings']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="detail-layout">
                    <section class="detail-section">
                        <h2>Ingredients</h2>
                        <?php if (!empty($ingredients)): ?>
                            <ul class="detail-list">
                                <?php foreach ($ingredients as $ingredient): ?>
                                    <li><?= e($ingredient); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No ingredient details were returned for this recipe.</p>
                        <?php endif; ?>
                    </section>

                    <section class="detail-section">
                        <h2>How to Make It</h2>
                        <?php if (!empty($steps)): ?>
                            <ol class="detail-list detail-list-ordered">
                                <?php foreach ($steps as $step): ?>
                                    <li><?= e($step); ?></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <p>No step-by-step directions were returned for this recipe.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="card-actions">
                    <a href="index.php" class="secondary-button">Back to Search</a>
                    <?php if (!empty($recipe['sourceUrl'])): ?>
                        <a href="<?= e($recipe['sourceUrl']); ?>" target="_blank" rel="noopener noreferrer" class="secondary-button">Open Full Recipe</a>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
