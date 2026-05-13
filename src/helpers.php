<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Store shared helper functions used across the app.
 */

// =============================================
// SESSION and HELPERS
// =============================================
function ensure_session_started(): void
{
    // Only start the session once so the messages keep working //
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function set_flash_message(string $type, string $message): void
{
    ensure_session_started();

    // Store a one-time message that can be shown after a redirect or on the next page load //
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash_message(): ?array
{
    ensure_session_started();

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    // Read once, then clear it so messages do not keep showing after refresh // or on multiple pages.
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

// =============================================
// OUTPUT and REDIRECT HELPERS
// =============================================
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    // Small helper so all redirects behave the same way // and we don't forget to exit after a redirect///
    header('Location: ' . $path);
    exit;
}

// =============================================
// RECIPE DISPLAY HELPERS
// =============================================
function ingredient_names_to_text(array $ingredients): string
{
    $names = [];

    // Spoonacular gives back ingredient objects, but we only want the names // for display purposes, so we extract those here//
    foreach ($ingredients as $ingredient) {
        if (!empty($ingredient['name'])) {
            $names[] = $ingredient['name'];
        }
    }

    return implode(', ', $names);
}

function build_spoonacular_recipe_url(int $recipeId, string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string) $slug, '-');

    return 'https://spoonacular.com/recipes/' . $slug . '-' . $recipeId;
}

// =============================================
// ENVIRONMENT CHECKS
// =============================================
function get_environment_warnings(): array
{
    $warnings = [];

    // These are the PHP pieces this project depends on to run normally // If any of these are missing, we can show a warning to the user so they know why things might not work//
    if (!extension_loaded('pdo_mysql')) {
        $warnings[] = 'PHP extension missing: pdo_mysql';
    }

    if (!extension_loaded('mysqli')) {
        $warnings[] = 'PHP extension missing: mysqli';
    }

    if (!extension_loaded('curl')) {
        $warnings[] = 'PHP extension missing: curl';
    }

    return $warnings;
}
