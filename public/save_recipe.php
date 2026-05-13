<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Save a selected recipe into the favorites table.
 */

require_once dirname(__DIR__) . '/src/db.php';
require_once dirname(__DIR__) . '/src/api.php';
require_once dirname(__DIR__) . '/src/validation.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();

// Only allow recipe saves through the POST form ///
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Invalid request method for saving a recipe.');
    redirect('index.php');
}

// Validate the hidden form fields before writing anything to MySQL //
$validation = validate_recipe_payload($_POST);

if (!$validation['valid']) {
    set_flash_message('error', $validation['error']);
    redirect('index.php');
}

try {
    $pdo = get_pdo();

    // =============================================
    // DUPLICATE CHECK
    // =============================================
    $checkStmt = $pdo->prepare('SELECT id FROM saved_recipes WHERE recipe_api_id = :recipe_api_id LIMIT 1');
    $checkStmt->execute([
        ':recipe_api_id' => $validation['recipe_api_id'],
    ]);

    if ($checkStmt->fetch()) {
        set_flash_message('info', 'That recipe is already in your favorites.');
        redirect('favorites.php');
    }

    // Try to grab a source URL from Spoonacular before saving the record //
    $recipeInfoResponse = fetch_recipe_information($validation['recipe_api_id']);
    $sourceUrl = build_spoonacular_recipe_url($validation['recipe_api_id'], $validation['title']);

    // If Spoonacular gives us the original source URL, save that instead of the fallback link //
    if ($recipeInfoResponse['success'] && !empty($recipeInfoResponse['data']['sourceUrl'])) {
        $sourceUrl = $recipeInfoResponse['data']['sourceUrl'];
    }

    // =============================================
    // SAVE FAVORITE
    // =============================================
    $insertStmt = $pdo->prepare(
        'INSERT INTO saved_recipes (
            recipe_api_id,
            title,
            image_url,
            used_ingredients,
            missed_ingredients,
            notes,
            source_url
        ) VALUES (
            :recipe_api_id,
            :title,
            :image_url,
            :used_ingredients,
            :missed_ingredients,
            :notes,
            :source_url
        )'
    );

    $insertStmt->execute([
        ':recipe_api_id' => $validation['recipe_api_id'],
        ':title' => $validation['title'],
        ':image_url' => $validation['image_url'],
        ':used_ingredients' => $validation['used_ingredients'],
        ':missed_ingredients' => $validation['missed_ingredients'],
        ':notes' => '',
        ':source_url' => $sourceUrl,
    ]);

    set_flash_message('success', 'Recipe saved to favorites.');
} catch (RuntimeException $exception) {
    set_flash_message('error', $exception->getMessage());
} catch (Throwable $exception) {
    set_flash_message('error', 'Something went wrong while saving the recipe.');
}

redirect('favorites.php');
