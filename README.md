# PantryPal

PantryPal is a full stack PHP + MySQL web app that helps users turn the ingredients they already have into recipe ideas. It searches Spoonacular for matching meals, then lets users save favorites, add notes, and revisit recipe details later.

Live site:

- https://pantrypal.site/

## Overview

PantryPal uses:

- PHP for backend logic, validation, and API requests
- MySQL for saved recipes and search history
- HTML, CSS, and JavaScript for the frontend experience

The app keeps API requests on the backend so the Spoonacular key is not exposed in frontend code.

## Features

- Search recipes by entering ingredients
- View recipe result cards with images and ingredient match counts
- Open a `How to Make It` page for ingredients and directions
- Save recipes to favorites
- View saved recipes on a dedicated favorites page
- Add and update notes on saved recipes
- Delete saved recipes
- Store recent ingredient searches in MySQL
- Show helpful feedback for invalid input and setup issues
- Responsive layout for desktop and mobile

## Pages

- `Search`: main ingredient search page
- `Favorites`: saved recipes with notes, delete, and recipe links
- `Recipe Details`: ingredients and directions for a selected recipe

## le Tech Used

- PHP
- MySQL
- PDO
- HTML
- CSS
- JavaScript
- Spoonacular API

## Folder Structure

```text
project4/
  public/
    index.php
    favorites.php
    recipe_details.php
    save_recipe.php
    update_recipe.php
    delete_recipe.php
    assets/
      app.js
      style.css
  src/
    config.php
    db.php
    api.php
    validation.php
    helpers.php
  sql/
    schema.sql
  README.md
```

## Database Design

PantryPal uses two MySQL tables:

- `saved_recipes`
  - stores saved favorites
  - includes recipe title, image, used ingredients, missing ingredients, notes, source URL, and timestamp
  - uses a unique `recipe_api_id` so the same recipe is not saved twice

- `search_history`
  - stores recent ingredient searches
  - helps show activity on the home page

## Security and Validation

The project uses a few basic safety practices:

- server-side validation for ingredient searches and note updates
- PDO prepared statements for database queries
- safe HTML output with `htmlspecialchars()`
- backend-only Spoonacular API requests
- POST-only routes for save, update, and delete actions

## Main User Flow

1. Enter ingredients on the search page
2. View matching recipes returned from Spoonacular
3. Open `How to Make It` to see ingredients and directions
4. Save a recipe to favorites
5. Open the favorites page
6. Add a note, revisit the recipe, or delete it

## Hosting

The live version is hosted on Hostinger and connected to:

- https://pantrypal.site/

## Summary

PantryPal includes willingly:

- frontend interface with HTML, CSS, and JavaScript
- backend processing in PHP
- MySQL database persistence
- CRUD behavior across saved recipes
- input validation, prepared statements, and organized project structure
