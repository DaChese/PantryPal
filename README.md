# PantryPal

PantryPal is a full stack PHP + MySQL web app that helps users turn the ingredients they already have into recipe ideas. It searches Spoonacular for matching meals, lets users save favorites, add notes, and revisit recipe details later. It also includes user accounts, a tier subscription system, a savings calculator, calorie-based search, and nutrition info on recipe details.

Live site: https://pantrypal-production.up.railway.app

## Features

- User registration and login with bcrypt password hashing
- Three subscription tiers (Free, Pro, Chef) with different search limits and result counts
- Daily search quota tracking per user with a visual progress bar
- Search recipes by ingredients you already have
- Search recipes by calorie target (±150 kcal tolerance)
- View recipe cards with ingredient match counts, price per serving, and macros
- Open a How to Make It page with full ingredients, amounts, directions, and calorie count
- Save recipes to a personal favorites list
- Add and update notes on saved recipes
- Delete saved recipes
- Savings calculator for Pro and Chef users — estimates home cook cost vs. eating out with monthly and yearly projections
- Recent search history per user
- Input validation on both client (JS) and server (PHP) sides
- Responsive layout for desktop and mobile

## Subscription Tiers

| Tier  | Price     | Searches/day | Results/search |
|-------|-----------|--------------|----------------|
| Free  | Free      | 5            | 10             |
| Pro   | $4.99/mo  | 25           | 50             |
| Chef  | $9.99/mo  | Unlimited    | 100            |

Pro and Chef tiers unlock the savings calculator. Billing is simulated — no real charges are made.

## Pages

- `index.php` — ingredient or calorie search, results, savings panel, recent history
- `favorites.php` — saved recipes with notes, delete, and recipe links
- `recipe_details.php` — full ingredients with amounts, directions, cook time, servings, calories
- `pricing.php` — tier comparison and simulated upgrade/downgrade
- `register.php` — create a new account
- `login.php` — log in
- `logout.php` — end session

## Tech Stack

- PHP 8.3
- MySQL 9.4
- PDO
- HTML, CSS, JavaScript
- Spoonacular API
- Docker (php:8.3-cli image)
- Railway (hosting + managed MySQL)

## Folder Structure

```text
pantrypal/
  public/
    index.php
    favorites.php
    recipe_details.php
    save_recipe.php
    update_recipe.php
    delete_recipe.php
    login.php
    logout.php
    register.php
    pricing.php
    router.php
    assets/
      app.js
      style.css
  src/
    config.php
    db.php
    api.php
    auth.php
    validation.php
    helpers.php
  sql/
    schema.sql
    migrations.sql
  Dockerfile
  nixpacks.toml
  README.md
```

## Database Design

PantryPal uses three MySQL tables:

- `users` — stores accounts, tier, and daily search quota tracking
- `saved_recipes` — stores saved favorites per user with title, image, ingredients, notes, source URL, and timestamp
- `search_history` — stores recent ingredient and calorie searches per user

## Security and Validation

- bcrypt password hashing via `password_hash()` and `password_verify()`
- Session ID regeneration on login and logout to prevent fixation
- Server-side validation on all form inputs with clear error messages
- Client-side JS validation for instant feedback before submit
- Ingredient input blocked from sentences, URLs, and non-ingredient characters
- PDO prepared statements for all database queries
- `htmlspecialchars()` on all output
- POST-only routes for save, update, delete, and tier upgrade actions
- Spoonacular API key stored in Railway environment variables only — never in code

## Deployment

The app runs on Railway using a `php:8.3-cli` Docker image. The PHP built-in server serves the `public/` directory via a router script that handles static file serving.

### Environment Variables (set in Railway)

| Variable              | Source                        |
|-----------------------|-------------------------------|
| `MYSQLHOST`           | Auto-injected by Railway MySQL |
| `MYSQL_DATABASE`      | Auto-injected by Railway MySQL |
| `MYSQLUSER`           | Auto-injected by Railway MySQL |
| `MYSQL_ROOT_PASSWORD` | Auto-injected by Railway MySQL |
| `MYSQLPORT`           | Auto-injected by Railway MySQL |
| `SPOONACULAR_API_KEY` | Set manually in Railway        |

### Running Migrations

After deploying, run `sql/migrations.sql` against the Railway MySQL instance:

```powershell
Get-Content "sql/migrations.sql" | & "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe" -h <MYSQLHOST> -u root -p<PASSWORD> --port <MYSQLPORT> --protocol=TCP railway
```

## Main User Flow

1. Register a free account or log in
2. Enter ingredients or a calorie target on the search page
3. Browse matching recipe cards
4. Open How to Make It to see full ingredients with amounts, directions, and calories
5. Save a recipe to your personal favorites
6. Add notes, revisit the recipe, or delete it from favorites
7. Upgrade your plan on the pricing page for more searches and the savings calculator
