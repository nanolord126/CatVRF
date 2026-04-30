#!/bin/bash

# Install Laravel Pennant for Feature Flags
# This script installs the package and publishes the configuration

set -e

echo "Installing Laravel Pennant..."
composer require laravel/pennant

echo "Publishing Pennant configuration..."
php artisan vendor:publish --tag=pennant-config

echo "Publishing Pennant migration..."
php artisan vendor:publish --tag=pennant-migrations

echo "Running migrations..."
php artisan migrate

echo "✅ Laravel Pennant installed successfully!"
echo ""
echo "Usage:"
echo "  php artisan feature:rollout --list"
echo "  php artisan feature:rollout medical-ai-diagnosis --percentage=10"
echo "  php artisan feature:rollout medical-ai-diagnosis --enable"
