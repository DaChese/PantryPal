<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Validate form input before it hits the database or API.
 */

// =============================================
// SMALL VALIDATION HELPERS
// =============================================
function safe_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

// =============================================
// INGREDIENT SEARCH VALIDATION
// =============================================
function validate_ingredient_input(?string $rawInput): array
{
    $rawInput = trim((string) $rawInput);

    if ($rawInput === '') {
        return [
            'valid' => false,
            'error' => 'Please enter at least one ingredient.',
        ];
    }

    if (safe_length($rawInput) > 500) {
        return [
            'valid' => false,
            'error' => 'Ingredient input is too long. Keep it under 500 characters.',
        ];
    }

    // Block sentences, URLs, and anything that isn't ingredient-like
    if (!preg_match('/^[a-zA-Z0-9,\-\s\']+$/', $rawInput)) {
        return [
            'valid' => false,
            'error' => 'Ingredients can only contain letters, numbers, spaces, commas, apostrophes, and hyphens. Example: chicken, rice, garlic',
        ];
    }

    // Break into individual ingredients and remove duplicates
    $parts = array_filter(array_map('trim', explode(',', $rawInput)));
    $parts = array_values(array_unique($parts));

    if (empty($parts)) {
        return [
            'valid' => false,
            'error' => 'Please separate ingredients with commas, like: chicken, rice, garlic.',
        ];
    }

    if (count($parts) > 15) {
        return [
            'valid' => false,
            'error' => 'Please keep it to 15 ingredients or fewer for this search.',
        ];
    }

    foreach ($parts as $ingredient) {
        if (safe_length($ingredient) < 2) {
            return [
                'valid' => false,
                'error' => 'Each ingredient should be at least 2 characters long.',
            ];
        }

        // Catch single ingredients that look like sentences (more than 4 words)
        $wordCount = str_word_count($ingredient);
        if ($wordCount > 4) {
            return [
                'valid' => false,
                'error' => '"' . $ingredient . '" doesn\'t look like an ingredient. Enter individual items like: chicken, rice, garlic.',
            ];
        }
    }

    return [
        'valid'       => true,
        'error'       => null,
        'ingredients' => $parts,
        'query'       => implode(', ', $parts),
        'api_query'   => implode(',', $parts),
    ];
}

// =============================================
// SAVE RECIPE VALIDATION
// =============================================
function validate_recipe_payload(array $input): array
{
    // Pull out just the fields we expect from the save form ///
    $recipeApiId = filter_var($input['recipe_api_id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim((string) ($input['title'] ?? ''));
    $imageUrl = trim((string) ($input['image_url'] ?? ''));
    $usedIngredients = trim((string) ($input['used_ingredients'] ?? ''));
    $missedIngredients = trim((string) ($input['missed_ingredients'] ?? ''));

    if (!$recipeApiId) {
        return [
            'valid' => false,
            'error' => 'Recipe ID is missing or invalid.',
        ];
    }

    if ($title === '' || safe_length($title) > 255) {
        return [
            'valid' => false,
            'error' => 'Recipe title is missing or too long.',
        ];
    }

    if ($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return [
            'valid' => false,
            'error' => 'Recipe image URL is invalid.',
        ];
    }

    return [
        'valid' => true,
        'error' => null,
        'recipe_api_id' => $recipeApiId,
        'title' => $title,
        'image_url' => $imageUrl,
        'used_ingredients' => $usedIngredients,
        'missed_ingredients' => $missedIngredients,
    ];
}

// =============================================
// NOTE UPDATE VALIDATION
// =============================================
function validate_recipe_note_update(array $input): array
{
    $recipeId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $notes = trim((string) ($input['notes'] ?? ''));

    if (!$recipeId) {
        return [
            'valid' => false,
            'error' => 'Recipe ID is missing or invalid.',
        ];
    }

    if (safe_length($notes) > 500) {
        return [
            'valid' => false,
            'error' => 'Notes must be 500 characters or fewer.',
        ];
    }

    return [
        'valid' => true,
        'error' => null,
        'id' => $recipeId,
        'notes' => $notes,
    ];
}
