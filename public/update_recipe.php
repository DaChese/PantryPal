<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Update the note attached to a saved recipe.
 */

require_once dirname(__DIR__) . '/src/db.php';
require_once dirname(__DIR__) . '/src/validation.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();

// route POST-only //
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Invalid request method for updating a recipe.');
    redirect('favorites.php');
}

// Validates the note before trying to update anything in the database //
$validation = validate_recipe_note_update($_POST);

if (!$validation['valid']) {
    set_flash_message('error', $validation['error']);
    redirect('favorites.php');
}

try {
    $pdo = get_pdo();

    // =============================================
    // UPDATE NOTE
    // =============================================
    $updateStmt = $pdo->prepare('UPDATE saved_recipes SET notes = :notes WHERE id = :id');
    $updateStmt->execute([
        ':notes' => $validation['notes'],
        ':id' => $validation['id'],
    ]);

    // rowCount can be 0 if the note text did not actually change //
    if ($updateStmt->rowCount() > 0) {
        set_flash_message('success', 'Recipe note updated.');
    } else {
        set_flash_message('info', 'No recipe note was changed.');
    }
} catch (RuntimeException $exception) {
    set_flash_message('error', $exception->getMessage());
} catch (Throwable $exception) {
    set_flash_message('error', 'Something went wrong while updating the recipe note.');
}

redirect('favorites.php');
