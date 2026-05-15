<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Handle Spoonacular API calls for recipe search and details.
 */

require_once __DIR__ . '/config.php';

// =============================================
// SPOONACULAR REQUESTS
// =============================================
function spoonacular_get(string $endpoint, array $params = []): array
{
    // Stop early with a helpful message if the key was never added, i used this for debugging and it is still useful for anyone who clones the code without setting up their own key.
    if (SPOONACULAR_API_KEY === 'YOUR_SPOONACULAR_API_KEY_HERE' || SPOONACULAR_API_KEY === '') {
        return [
            'success' => false,
            'data' => null,
            'error' => 'Please add your Spoonacular API key in src/config.php before searching.',
        ];
    }

    $params['apiKey'] = SPOONACULAR_API_KEY;
    $url = SPOONACULAR_BASE_URL . $endpoint . '?' . http_build_query($params);

    $response = false;
    $statusCode = 0;
    $requestError = '';

    // Prefer curl when it is available because it handles HTTPS requests better, this was when i was hosting it locally on my MACHINE.
    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
        ]);

        $response = curl_exec($ch);
        $requestError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    } else {
        // Fallback for PHP setups where curl is not enabled //
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $requestError = error_get_last()['message'] ?? '';

        $responseHeaders = function_exists('http_get_last_response_headers')
            ? http_get_last_response_headers()
            : ($http_response_header ?? []);

        if (!empty($responseHeaders[0]) && preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches)) {
            $statusCode = (int) $matches[1];
        }
    }

    // If the request failed entirely or returned an error, with the message from the request attempt ///
    if ($response === false) {
        return [
            'success' => false,
            'data' => null,
            'error' => 'Could not reach the recipe service right now. ' . $requestError,
        ];
    }

    $decoded = json_decode($response, true);

    // Spoonacular if it returns an HTTP error, so pass along the message if possible ///
    if ($statusCode >= 400) {
        $message = $decoded['message'] ?? 'Recipe service returned an error.';

        return [
            'success' => false,
            'data' => null,
            'error' => $message,
        ];
    }

    // If decoding fails, treat it like a bad API response ///
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'data' => null,
            'error' => 'Recipe service returned invalid data.',
        ];
    }

    return [
        'success' => true,
        'data' => $decoded,
        'error' => null,
    ];
}

// =============================================
// SEARCH BY INGREDIENTS
// =============================================
function search_recipes_by_ingredients(string $ingredientQuery): array
{
    // This endpoint is built for ingredient-based recipe matching !!! ///
    return spoonacular_get('/findByIngredients', [
        'ingredients' => $ingredientQuery,
        'number' => RESULT_LIMIT,
        'ranking' => 1,
        'ignorePantry' => 'true',
    ]);
}

// =============================================
// RECIPE DETAILS
// =============================================
function fetch_recipe_information(int $recipeId): array
{
    return spoonacular_get('/' . $recipeId . '/information', [
        'includeNutrition' => 'true',
    ]);
}
