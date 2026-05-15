/*
 * Author:
 * Created on: 4/12/2026
 * Last updated: 5/13/2026
 * Purpose: Add small frontend checks and quality-of-life behavior.
 */

document.addEventListener('DOMContentLoaded', function () {
    // =============================================
    // INGREDIENT FORM HELPERS
    // =============================================
    var ingredientField = document.getElementById('ingredients');
    var calorieField    = document.getElementById('calorie_target');
    var ingredientCount = document.getElementById('ingredient-count');
    var searchForm      = ingredientField ? ingredientField.closest('form') : null;

    // Only letters, numbers, spaces, commas, apostrophes, hyphens
    var validIngredientPattern = /^[a-zA-Z0-9,\-\s']+$/;

    function updateIngredientCount() {
        if (!ingredientField || !ingredientCount) return;
        ingredientCount.textContent = ingredientField.value.length + '/500';
    }

    function removeInlineError(field) {
        var id = field ? 'inline-error-' + field : 'inline-error-general';
        var existing = document.getElementById(id);
        if (existing) existing.remove();
    }

    function showInlineError(message, field) {
        var id = field ? 'inline-error-' + field : 'inline-error-general';
        removeInlineError(field);

        if (!searchForm) return;

        var error = document.createElement('p');
        error.className = 'inline-error';
        error.id = id;
        error.textContent = message;

        // Insert after the relevant field if possible
        var target = field ? document.getElementById(field) : null;
        if (target && target.parentNode) {
            target.parentNode.insertBefore(error, target.nextSibling);
        } else {
            searchForm.insertBefore(error, searchForm.lastElementChild);
        }
    }

    if (ingredientField) {
        updateIngredientCount();

        ingredientField.addEventListener('input', function () {
            updateIngredientCount();
            removeInlineError('ingredients');

            var val = ingredientField.value;
            if (val.length > 0 && !validIngredientPattern.test(val)) {
                showInlineError('Use letters, numbers, commas, apostrophes, and hyphens only.', 'ingredients');
            }
        });
    }

    if (calorieField) {
        calorieField.addEventListener('input', function () {
            removeInlineError('calorie_target');
            var val = calorieField.value.trim();
            if (val === '') return;

            var num = parseInt(val, 10);
            if (isNaN(num) || num < 50 || num > 5000) {
                showInlineError('Enter a number between 50 and 5000.', 'calorie_target');
            }
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', function (event) {
            var ingredientVal = ingredientField ? ingredientField.value.trim() : '';
            var calorieVal    = calorieField ? calorieField.value.trim() : '';
            var hasError      = false;

            // Must have at least one of: ingredients or calorie target
            if (ingredientVal === '' && calorieVal === '') {
                event.preventDefault();
                showInlineError('Enter ingredients or a calorie target to search.', 'ingredients');
                hasError = true;
            }

            // Ingredient format check
            if (!hasError && ingredientVal !== '' && !validIngredientPattern.test(ingredientVal)) {
                event.preventDefault();
                showInlineError('Use letters, numbers, commas, apostrophes, and hyphens only.', 'ingredients');
                hasError = true;
            }

            // Ingredient length check
            if (!hasError && ingredientVal.length > 500) {
                event.preventDefault();
                showInlineError('Keep your ingredient search under 500 characters.', 'ingredients');
                hasError = true;
            }

            // Calorie range check
            if (!hasError && calorieVal !== '') {
                var calorieNum = parseInt(calorieVal, 10);
                if (isNaN(calorieNum) || calorieNum < 50 || calorieNum > 5000) {
                    event.preventDefault();
                    showInlineError('Enter a calorie target between 50 and 5000.', 'calorie_target');
                    hasError = true;
                }
            }
        });
    }

    // =============================================
    // DELETE CONFIRMATION
    // =============================================
    var deleteForms = document.querySelectorAll('form[action="delete_recipe.php"]');

    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var confirmed = window.confirm('Delete this recipe from your favorites?');
            if (!confirmed) event.preventDefault();
        });
    });
});
