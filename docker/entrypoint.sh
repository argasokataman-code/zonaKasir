#!/bin/sh
set -e

# Cache Blade views before starting the app
# This ensures Tailwind CSS classes from compiled views are available
# (important after container restart when storage may be empty)
php artisan view:cache || true

# Start supervisor (which starts php-fpm + nginx)
exec "$@"