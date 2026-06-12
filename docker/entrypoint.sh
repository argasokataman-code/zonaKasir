#!/bin/sh
set -e

# Ensure storage and cache directories exist with correct permissions
chown -R wwwuser:wwwgroup /var/www/html/storage
chown -R wwwuser:wwwgroup /var/www/html/bootstrap/cache

# Publish Filament/Livewire assets — public/ comes from the bind-mounted
# source tree, so assets baked into the image are shadowed and must be
# re-published. Without filament's app.js the sidebar has no Alpine store
# and overlaps the page content.
if ! php artisan filament:assets; then
    echo "WARNING: 'php artisan filament:assets' failed during container startup." >&2
fi
php artisan livewire:publish --assets >/dev/null 2>&1 || true

# Cache Blade views before starting the app
# This ensures Tailwind CSS classes from compiled views are available
# (important after container restart when storage may be empty)
if ! php artisan view:cache; then
    echo "WARNING: 'php artisan view:cache' failed during container startup; continuing without cached views." >&2
fi

# Start supervisor (which starts php-fpm + nginx)
exec "$@"