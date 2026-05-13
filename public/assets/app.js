/*
 * Author:
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Add small frontend checks and quality-of-life behavior.
 */

document.addEventListener('DOMContentLoaded', function () {
    // =============================================
    // INGREDIENT FORM HELPERS
    // =============================================
    var ingredientField = document.getElementById('ingredients');
    var ingredientCount = document.getElementById('ingredient-count');
    var searchForm = ingredientField ? ingredientField.closest('form') : null;

    function updateIngredientCount() {
        if (!ingredientField || !ingredientCount) {
            return;
        }

        ingredientCount.textContent = ingredientField.value.length + '/500';
    }

    function removeInlineError() {
        var existingError = document.querySelector('.inline-error');

        if (existingError) {
            existingError.remove();
        }
    }

    function showInlineError(message) {
        removeInlineError();

        if (!searchForm) {
            return;
        }

        // Keep the client-side message right next to the form controls.
        var error = document.createElement('p');
        error.className = 'inline-error';
        error.textContent = message;
        searchForm.insertBefore(error, searchForm.lastElementChild);
    }

    if (ingredientField) {
        updateIngredientCount();

        ingredientField.addEventListener('input', function () {
            updateIngredientCount();
            removeInlineError();
        });
    }

    if (searchForm && ingredientField) {
        searchForm.addEventListener('submit', function (event) {
            var value = ingredientField.value.trim();

            // This does not replace PHP validation. It just gives faster feedback.
            if (value === '') {
                event.preventDefault();
                showInlineError('Please enter at least one ingredient before searching.');
                return;
            }

            if (value.length > 500) {
                event.preventDefault();
                showInlineError('Please keep your ingredient search under 500 characters.');
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

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});
