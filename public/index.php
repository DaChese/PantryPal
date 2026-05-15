<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 5/13/2026
 * Purpose: Show the main search page and recipe results.
 */

require_once dirname(__DIR__) . '/src/db.php';
require_once dirname(__DIR__) . '/src/api.php';
require_once dirname(__DIR__) . '/src/validation.php';
require_once dirname(__DIR__) . '/src/helpers.php';
require_once dirname(__DIR__) . '/src/auth.php';

ensure_session_started();

$flash               = get_flash_message();
$recipes             = [];
$errorMessage        = '';
$searchQuery         = '';
$calorieTarget       = '';
$searchMode          = 'ingredients'; // 'ingredients' or 'calories'
$recentSearches      = [];
$environmentWarnings = get_environment_warnings();
$savings             = [];
$quotaInfo           = null;

$user = get_logged_in_user();
$tier = $user['tier'] ?? 'free';
$tiers = get_tier_config();

try {
    $pdo = get_pdo();

    // =============================================
    // HANDLE SEARCH SUBMIT
    // =============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Guests must log in to search
        if (!$user) {
            set_flash_message('info', 'Please log in or create a free account to search for recipes.');
            redirect('login.php');
        }

        // Check daily quota
        $quota = check_and_increment_search_quota($user['id'], $tier);
        $quotaInfo = $quota;

        if (!$quota['allowed']) {
            $errorMessage = $quota['error'];
        } else {
            $rawCalories   = trim($_POST['calorie_target'] ?? '');
            $calorieTarget = ($rawCalories !== '') ? filter_var($rawCalories, FILTER_VALIDATE_INT) : null;
            $resultLimit   = get_result_limit_for_tier($tier);

            if ($calorieTarget !== null && $calorieTarget !== false && $calorieTarget > 0) {
                // ---- CALORIE-BASED SEARCH ----
                if ($calorieTarget < 50 || $calorieTarget > 5000) {
                    $errorMessage  = 'Please enter a calorie target between 50 and 5000.';
                    $calorieTarget = $rawCalories;
                } else {
                    $searchMode    = 'calories';
                    $calorieTarget = (int) $calorieTarget;

                    $historyStmt = $pdo->prepare(
                        'INSERT INTO search_history (ingredients_query, user_id) VALUES (:q, :uid)'
                    );
                    $historyStmt->execute([':q' => "~{$calorieTarget} kcal", ':uid' => $user['id']]);

                    $apiResponse = search_recipes_by_calories($calorieTarget, $resultLimit);

                    if (!$apiResponse['success']) {
                        $errorMessage = $apiResponse['error'];
                    } else {
                        $recipes = $apiResponse['data'];
                        if (empty($recipes)) {
                            $errorMessage = "No recipes found near {$calorieTarget} kcal. Try a different target.";
                        } elseif (in_array($tier, ['pro', 'chef'], true)) {
                            $savings = calculate_savings($recipes);
                        }
                    }
                }
            } else {
                // ---- INGREDIENT-BASED SEARCH ----
                $validation = validate_ingredient_input($_POST['ingredients'] ?? '');

                if (!$validation['valid']) {
                    $errorMessage = $validation['error'];
                    $searchQuery  = trim((string) ($_POST['ingredients'] ?? ''));
                } else {
                    $searchQuery = $validation['query'];

                    $historyStmt = $pdo->prepare(
                        'INSERT INTO search_history (ingredients_query, user_id) VALUES (:q, :uid)'
                    );
                    $historyStmt->execute([':q' => $validation['query'], ':uid' => $user['id']]);

                    $apiResponse = search_recipes_by_ingredients($validation['api_query'], $resultLimit);

                    if (!$apiResponse['success']) {
                        $errorMessage = $apiResponse['error'];
                    } else {
                        $recipes = $apiResponse['data'];
                        if (empty($recipes)) {
                            $errorMessage = 'No recipes matched those ingredients. Try adding one or two more.';
                        } elseif (in_array($tier, ['pro', 'chef'], true)) {
                            $savings = calculate_savings($recipes);
                        }
                    }
                }
            }
        }
    }

    // =============================================
    // LOAD RECENT SEARCHES (user-specific)
    // =============================================
    if ($user) {
        $historyStmt = $pdo->prepare(
            'SELECT ingredients_query, created_at FROM search_history
             WHERE user_id = :uid ORDER BY created_at DESC LIMIT 6'
        );
        $historyStmt->execute([':uid' => $user['id']]);
        $recentSearches = $historyStmt->fetchAll();
    }

    // Load quota info for display even on GET
    if ($user) {
        $quotaRow = $pdo->prepare('SELECT searches_today, searches_date FROM users WHERE id = :id');
        $quotaRow->execute([':id' => $user['id']]);
        $row   = $quotaRow->fetch();
        $today = date('Y-m-d');
        $used  = ($row && $row['searches_date'] === $today) ? (int) $row['searches_today'] : 0;
        $limit = get_search_limit_for_tier($tier);
        $quotaInfo = ['used' => $used, 'limit' => $limit, 'allowed' => true];
    }

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
    <link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body>
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
                    <?php if ($user): ?>
                        <a href="pricing.php">
                            <span class="tier-badge tier-badge--<?= e($tier); ?>"><?= e(ucfirst($tier)); ?></span>
                            Plans
                        </a>
                        <a href="logout.php">Log Out</a>
                    <?php else: ?>
                        <a href="login.php">Log In</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
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

            <?php if ($user && $quotaInfo): ?>
                <div class="quota-bar">
                    <?php
                        $limitDisplay = $quotaInfo['limit'] >= 999999 ? 'Unlimited' : $quotaInfo['limit'];
                        $pct = $quotaInfo['limit'] >= 999999 ? 0 : min(100, round(($quotaInfo['used'] / $quotaInfo['limit']) * 100));
                    ?>
                    <span>Searches today: <?= $quotaInfo['used']; ?> / <?= $limitDisplay; ?></span>
                    <?php if ($quotaInfo['limit'] < 999999): ?>
                        <div class="quota-track">
                            <div class="quota-fill" style="width:<?= $pct; ?>%"></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($tier !== 'chef'): ?>
                        <a href="pricing.php" class="quota-upgrade">Upgrade for more</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($user): ?>
                <form action="index.php" method="post" class="search-form" novalidate>
                    <label for="ingredients" class="sr-only">Ingredients</label>
                    <textarea id="ingredients" name="ingredients" rows="3"
                              placeholder="ex: eggs, spinach, cheese"><?= e($searchQuery); ?></textarea>
                    <div class="form-meta">
                        <small id="ingredient-help">Use commas to separate ingredients. Max 500 characters.</small>
                        <small id="ingredient-count"><?= strlen($searchQuery); ?>/500</small>
                    </div>
                    <div class="calorie-row">
                        <label for="calorie_target">Calorie target <span class="optional-label">(optional)</span></label>
                        <div class="calorie-input-wrap">
                            <input type="number" id="calorie_target" name="calorie_target"
                                   min="50" max="5000" step="50"
                                   placeholder="e.g. 500"
                                   value="<?= e((string) ($calorieTarget ?? '')); ?>">
                            <span class="calorie-unit">kcal per serving</span>
                        </div>
                        <small>Leave blank to search by ingredients only. Enter a number to find recipes near that calorie count.</small>
                    </div>
                    <button type="submit">Find Recipes</button>
                </form>
            <?php else: ?>
                <div class="guest-cta">
                    <p>Create a free account to start searching for recipes.</p>
                    <div class="card-actions">
                        <a href="register.php" class="secondary-button">Create Free Account</a>
                        <a href="login.php" class="secondary-button">Log In</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Savings calculator panel (Pro/Chef only, shown after search) -->
        <?php if (!empty($savings)): ?>
            <section class="panel savings-panel">
                <h2>Your Savings Estimate</h2>
                <p class="section-copy">
                    Based on <?= $savings['recipe_count']; ?> recipes found
                    <?= $savings['price_source'] === 'api' ? '(prices from Spoonacular)' : '(estimated prices)'; ?>.
                    Assumes <?= $savings['meals_per_month']; ?> home-cooked meals per month.
                </p>
                <div class="savings-grid">
                    <div class="savings-stat">
                        <span class="savings-label">Avg. home cook cost</span>
                        <span class="savings-value savings-value--home">$<?= number_format($savings['avg_home_cost'], 2); ?></span>
                        <span class="savings-sub">per meal</span>
                    </div>
                    <div class="savings-stat">
                        <span class="savings-label">Avg. eating out cost</span>
                        <span class="savings-value savings-value--out">$<?= number_format($savings['avg_eat_out_cost'], 2); ?></span>
                        <span class="savings-sub">per meal</span>
                    </div>
                    <div class="savings-stat savings-stat--highlight">
                        <span class="savings-label">You save per meal</span>
                        <span class="savings-value savings-value--save">$<?= number_format($savings['savings_per_meal'], 2); ?></span>
                        <span class="savings-sub">cooking at home</span>
                    </div>
                    <div class="savings-stat savings-stat--highlight">
                        <span class="savings-label">Monthly savings</span>
                        <span class="savings-value savings-value--save">$<?= number_format($savings['monthly_savings'], 2); ?></span>
                        <span class="savings-sub"><?= $savings['meals_per_month']; ?> meals/month</span>
                    </div>
                    <div class="savings-stat savings-stat--big">
                        <span class="savings-label">Yearly savings potential</span>
                        <span class="savings-value savings-value--year">$<?= number_format($savings['yearly_savings'], 2); ?></span>
                        <span class="savings-sub">if you cook instead of eating out</span>
                    </div>
                </div>
            </section>
        <?php elseif ($user && $tier === 'free' && !empty($recipes)): ?>
            <section class="panel savings-teaser">
                <h2>Want to see your savings?</h2>
                <p class="section-copy">Upgrade to Pro or Chef to unlock the savings calculator and see how much you could save cooking at home vs. eating out.</p>
                <a href="pricing.php" class="secondary-button">See Plans</a>
            </section>
        <?php endif; ?>

        <!-- Results and recent searches -->
        <section class="content-grid">
            <div class="main-column">
                <div class="section-heading">
                    <h2>Recipe Results</h2>
                    <?php if (!empty($recipes)): ?>
                        <?php if ($searchMode === 'calories'): ?>
                            <p><?= count($recipes); ?> recipes found near <?= (int) $calorieTarget; ?> kcal per serving</p>
                        <?php else: ?>
                            <p><?= count($recipes); ?> recipes found for "<?= e($searchQuery); ?>"</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Search results will show up here.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($recipes)): ?>
                    <div class="recipe-grid">
                        <?php foreach ($recipes as $recipe): ?>
                            <?php
                            $usedList   = ingredient_names_to_text($recipe['usedIngredients'] ?? []);
                            $missedList = ingredient_names_to_text($recipe['missedIngredients'] ?? []);
                            ?>
                            <article class="recipe-card">
                                <img src="<?= e($recipe['image'] ?? ''); ?>"
                                     alt="<?= e($recipe['title'] ?? 'Recipe image'); ?>"
                                     class="recipe-image">
                                <div class="recipe-body">
                                    <h3><?= e($recipe['title'] ?? 'Untitled Recipe'); ?></h3>
                                    <div class="recipe-stats">
                                        <?php if ($searchMode === 'calories'): ?>
                                            <span><?= (int) ($recipe['calories'] ?? 0); ?> kcal</span>
                                            <?php if (!empty($recipe['protein'])): ?>
                                                <span>Protein: <?= e($recipe['protein']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($recipe['fat'])): ?>
                                                <span>Fat: <?= e($recipe['fat']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($recipe['carbs'])): ?>
                                                <span>Carbs: <?= e($recipe['carbs']); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span>You have: <?= (int) ($recipe['usedIngredientCount'] ?? 0); ?></span>
                                            <span>Still need: <?= (int) ($recipe['missedIngredientCount'] ?? 0); ?></span>
                                            <?php if (!empty($recipe['pricePerServing'])): ?>
                                                <span>~$<?= number_format($recipe['pricePerServing'] / 100, 2); ?>/serving</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <p><strong>Have:</strong> <?= e($usedList !== '' ? $usedList : 'None listed'); ?></p>
                                    <p><strong>Need:</strong> <?= e($missedList !== '' ? $missedList : 'Nothing else needed'); ?></p>
                                    <div class="card-actions">
                                        <a href="recipe_details.php?id=<?= (int) ($recipe['id'] ?? 0); ?>"
                                           class="secondary-button">How to Make It</a>

                                        <?php if ($user): ?>
                                            <form action="save_recipe.php" method="post" class="save-form">
                                                <input type="hidden" name="recipe_api_id" value="<?= (int) ($recipe['id'] ?? 0); ?>">
                                                <input type="hidden" name="title" value="<?= e($recipe['title'] ?? ''); ?>">
                                                <input type="hidden" name="image_url" value="<?= e($recipe['image'] ?? ''); ?>">
                                                <input type="hidden" name="used_ingredients" value="<?= e($usedList); ?>">
                                                <input type="hidden" name="missed_ingredients" value="<?= e($missedList); ?>">
                                                <button type="submit">Save Favorite</button>
                                            </form>
                                        <?php endif; ?>
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
                <?php elseif ($user): ?>
                    <p>No searches saved yet.</p>
                <?php else: ?>
                    <p><a href="login.php">Log in</a> to see your search history.</p>
                <?php endif; ?>

                <?php if ($user): ?>
                    <div class="sidebar-tier">
                        <p>Plan: <strong><?= e(ucfirst($tier)); ?></strong></p>
                        <?php if ($tier !== 'chef'): ?>
                            <a href="pricing.php" class="secondary-button" style="margin-top:0.5rem;display:inline-block;">Upgrade Plan</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </section>
    </main>
    <script src="assets/app.js?v=3"></script>
</body>
</html>
