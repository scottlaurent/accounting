#!/bin/sh
set -e

# Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Run tests
exec "$@"
