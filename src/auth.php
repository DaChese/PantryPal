<?php
/*
 * Purpose: Auth helpers - login, register, session, tier limits, savings.
 */

require_once __DIR__ . '/db.php';

// =============================================
// TIER DEFINITIONS
// =============================================
function get_tier_config(): array
{
    return [
        'free' => [
            'label'          => 'Free',
            'price'          => 0,
            'searches_per_day' => 5,
            'results_per_search' => 10,
            'description'    => 'Great for getting started',
            'color'          => 'tier-free',
        ],
        'pro' => [
            'label'          => 'Pro',
            'price'          => 4.99,
            'searches_per_day' => 25,
            'results_per_search' => 50,
            'description'    => 'For the home cook who plans ahead',
            'color'          => 'tier-pro',
        ],
        'chef' => [
            'label'          => 'Chef',
            'price'          => 9.99,
            'searches_per_day' => PHP_INT_MAX,
            'results_per_search' => 100,
            'description'    => 'Unlimited searches, maximum results',
            'color'          => 'tier-chef',
        ],
    ];
}

function get_result_limit_for_tier(string $tier): int
{
    $config = get_tier_config();
    return $config[$tier]['results_per_search'] ?? 10;
}

function get_search_limit_for_tier(string $tier): int
{
    $config = get_tier_config();
    $limit = $config[$tier]['searches_per_day'] ?? 5;
    return $limit === PHP_INT_MAX ? 999999 : $limit;
}

// =============================================
// SESSION HELPERS
// =============================================
function get_logged_in_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    // Regenerate session ID on login to prevent fixation
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'tier'     => $user['tier'],
    ];
}

function logout_user(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function require_login(string $redirect = 'login.php'): array
{
    $user = get_logged_in_user();
    if (!$user) {
        header('Location: ' . $redirect);
        exit;
    }
    return $user;
}

// =============================================
// REGISTER
// =============================================
function register_user(string $username, string $email, string $password): array
{
    $username = trim($username);
    $email    = trim($email);

    if ($username === '' || strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'error' => 'Username must be between 3 and 50 characters.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['success' => false, 'error' => 'Username can only contain letters, numbers, and underscores.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    try {
        $pdo = get_pdo();

        // Check for duplicate username or email
        $check = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
        $check->execute([':u' => $username, ':e' => $email]);
        if ($check->fetch()) {
            return ['success' => false, 'error' => 'That username or email is already taken.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :h)'
        );
        $stmt->execute([':u' => $username, ':e' => $email, ':h' => $hash]);

        $userId = (int) $pdo->lastInsertId();
        return [
            'success' => true,
            'user'    => [
                'id'       => $userId,
                'username' => $username,
                'email'    => $email,
                'tier'     => 'free',
            ],
        ];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

// =============================================
// LOGIN
// =============================================
function attempt_login(string $username, string $password): array
{
    $username = trim($username);

    if ($username === '' || $password === '') {
        return ['success' => false, 'error' => 'Please enter your username and password.'];
    }

    try {
        $pdo  = get_pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Incorrect username or password.'];
        }

        return ['success' => true, 'user' => $user];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Login failed. Please try again.'];
    }
}

// =============================================
// DAILY SEARCH QUOTA
// =============================================
function check_and_increment_search_quota(int $userId, string $tier): array
{
    $limit = get_search_limit_for_tier($tier);

    try {
        $pdo  = get_pdo();
        $stmt = $pdo->prepare('SELECT searches_today, searches_date FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $row  = $stmt->fetch();

        $today = date('Y-m-d');

        // Reset counter if it's a new day
        if ($row['searches_date'] !== $today) {
            $pdo->prepare('UPDATE users SET searches_today = 0, searches_date = :d WHERE id = :id')
                ->execute([':d' => $today, ':id' => $userId]);
            $row['searches_today'] = 0;
        }

        if ($row['searches_today'] >= $limit) {
            return [
                'allowed' => false,
                'used'    => $row['searches_today'],
                'limit'   => $limit,
                'error'   => "You have used all {$limit} searches for today on the " . ucfirst($tier) . " plan. Upgrade for more.",
            ];
        }

        $pdo->prepare('UPDATE users SET searches_today = searches_today + 1 WHERE id = :id')
            ->execute([':id' => $userId]);

        return [
            'allowed' => true,
            'used'    => $row['searches_today'] + 1,
            'limit'   => $limit,
        ];
    } catch (Throwable $e) {
        // Fail open so a DB hiccup doesn't block searches
        return ['allowed' => true, 'used' => 0, 'limit' => $limit];
    }
}

// =============================================
// TIER UPGRADE (simulated)
// =============================================
function upgrade_tier(int $userId, string $newTier): bool
{
    $valid = ['free', 'pro', 'chef'];
    if (!in_array($newTier, $valid, true)) {
        return false;
    }
    try {
        $pdo = get_pdo();
        $pdo->prepare('UPDATE users SET tier = :t WHERE id = :id')
            ->execute([':t' => $newTier, ':id' => $userId]);
        // Update session too
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $_SESSION['user']['tier'] = $newTier;
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// =============================================
// SAVINGS CALCULATOR
// =============================================

// Average cost to eat out per meal (USD)
define('AVG_EATING_OUT_COST', 15.00);
// Average home-cooked meal cost fallback (USD) when API has no price data
define('AVG_HOME_COOK_COST', 4.50);
// Assumed meals cooked per month for projection
define('MEALS_PER_MONTH', 20);

function calculate_savings(array $recipes): array
{
    $totalHomeCost  = 0.0;
    $totalEatOutCost = 0.0;
    $recipeCount    = 0;
    $priceSource    = 'estimated'; // 'api' or 'estimated'

    foreach ($recipes as $recipe) {
        $recipeCount++;

        // Spoonacular returns pricePerServing in cents
        if (!empty($recipe['pricePerServing']) && $recipe['pricePerServing'] > 0) {
            $homeCost    = round($recipe['pricePerServing'] / 100, 2);
            $priceSource = 'api';
        } else {
            $homeCost = AVG_HOME_COOK_COST;
        }

        $totalHomeCost   += $homeCost;
        $totalEatOutCost += AVG_EATING_OUT_COST;
    }

    if ($recipeCount === 0) {
        return [];
    }

    $avgHomeCost    = round($totalHomeCost / $recipeCount, 2);
    $avgEatOutCost  = AVG_EATING_OUT_COST;
    $savingsPerMeal = round($avgEatOutCost - $avgHomeCost, 2);
    $monthlySavings = round($savingsPerMeal * MEALS_PER_MONTH, 2);
    $yearlySavings  = round($monthlySavings * 12, 2);

    return [
        'avg_home_cost'    => $avgHomeCost,
        'avg_eat_out_cost' => $avgEatOutCost,
        'savings_per_meal' => max(0, $savingsPerMeal),
        'monthly_savings'  => max(0, $monthlySavings),
        'yearly_savings'   => max(0, $yearlySavings),
        'recipe_count'     => $recipeCount,
        'price_source'     => $priceSource,
        'meals_per_month'  => MEALS_PER_MONTH,
    ];
}
