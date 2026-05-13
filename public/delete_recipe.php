<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Delete a saved recipe from the favorites table.
 */

require_once dirname(__DIR__) . '/src/db.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();

// Keep deletes POST-only so links cannot remove data by accident ///
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Invalid request method for deleting a recipe.');
    redirect('favorites.php');
}

// Only allow a real numeric ID to be deleted ///
$recipeId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$recipeId) {
    set_flash_message('error', 'Recipe ID is missing or invalid.');
    redirect('favorites.php');
}

try {
    $pdo = get_pdo();

    // =============================================
    // DELETE FAVORITE
    // =============================================
    $deleteStmt = $pdo->prepare('DELETE FROM saved_recipes WHERE id = :id');
    $deleteStmt->execute([
        ':id' => $recipeId,
    ]);

    // rowCount lets us tell the user whether something was actually deleted ///
    if ($deleteStmt->rowCount() > 0) {
        set_flash_message('success', 'Recipe removed from favorites.');
    } else {
        set_flash_message('info', 'That recipe was not found.');
    }
} catch (RuntimeException $exception) {
    set_flash_message('error', $exception->getMessage());
} catch (Throwable $exception) {
    set_flash_message('error', 'Something went wrong while deleting the recipe.');
}

redirect('favorites.php');
