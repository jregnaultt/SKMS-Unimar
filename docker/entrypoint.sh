#!/bin/sh

# Wait a few seconds to ensure the external database is ready
sleep 3

echo "=== DIAGNOSTICS: Testing Laravel Boot ==="
php artisan route:list

# Run database migrations automatically on startup
echo "Running database migrations..."
php artisan migrate --force --seed

# Start Supervisor (which starts FrankenPHP/Octane and the Queue Worker)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
