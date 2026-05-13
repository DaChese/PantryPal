FROM php:8.3-cli

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy app files
COPY . /app

WORKDIR /app

# Railway injects $PORT at runtime - use PHP built-in server, no Apache conflicts
CMD php -S 0.0.0.0:${PORT:-8080} -t public public/router.php
