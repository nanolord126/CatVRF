#!/bin/bash

set -e

echo "🚀 Setting up CatVRF development environment..."

# Install PHP extensions required for Laravel
echo "📦 Installing PHP extensions..."
sudo apt-get update
sudo apt-get install -y \
    php8.3-pgsql \
    php8.3-redis \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-gd \
    php8.3-sqlite3 \
    php8.3-soap \
    php8.3-imagick \
    postgresql-client \
    redis-tools \
    git

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
if [ -f "package.json" ]; then
    npm install
fi

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
if [ -f "composer.json" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Copy .env file if it doesn't exist
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    
    # Generate application key
    php artisan key:generate
fi

# Set up database in Codespaces (use SQLite for simplicity)
echo "🗄️ Setting up database..."
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=\/workspace\/database\/database.sqlite/' .env

# Create SQLite database file
touch database/database.sqlite

# Run migrations
echo "🔄 Running migrations..."
php artisan migrate --force

# Create storage links
echo "🔗 Creating storage links..."
php artisan storage:link

# Clear and cache configs
echo "🧹 Clearing and caching configs..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install Laravel Sail (optional, for local development)
echo "🐳 Installing Laravel Sail..."
composer require laravel/sail --dev

# Publish Sail configuration
echo "📝 Publishing Sail configuration..."
php artisan sail:install --no-interaction

# Install PHP extensions for Sail
echo "📦 Installing PHP extensions for Sail..."
./vendor/bin/sail up -d
docker exec -it $(docker ps -q -f "name=laravel.test") bash -c "apt-get update && apt-get install -y php8.3-pgsql php8.3-redis php8.3-imagick"

echo "✅ Setup complete!"
echo ""
echo "🎉 CatVRF development environment is ready!"
echo ""
echo "📌 Quick start commands:"
echo "  - Run tests: php artisan test"
echo "  - Run migrations: php artisan migrate"
echo "  - Start server: php artisan serve --host=0.0.0.0 --port=80"
echo "  - Use Sail: ./vendor/bin/sail up"
echo ""
echo "🌐 Access the application at: http://localhost:80"
echo ""
