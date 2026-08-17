#!/usr/bin/env bash
set -e

# Detect PHP binary (RunCloud path if present, otherwise system php)
PHP_BIN="/RunCloud/Packages/php82rc/bin/php"
if [ ! -f "$PHP_BIN" ]; then
    PHP_BIN="php"
fi

echo "=== Using PHP binary: $PHP_BIN ==="

echo "=== Running database migrations ==="
$PHP_BIN bin/console doctrine:migrations:migrate --no-interaction

echo "=== Clearing application cache ==="
$PHP_BIN bin/console cache:clear

echo "=== Building Tailwind CSS (minified) ==="
$PHP_BIN bin/console tailwind:build --minify

echo "=== Compiling AssetMap ==="
$PHP_BIN bin/console asset-map:compile

echo "=== Build completed successfully ==="
