#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

cp -n .env.example .env 2>/dev/null || true
php artisan key:generate --force --no-interaction 2>/dev/null || true
composer install --no-interaction
php artisan migrate --force --no-interaction 2>/dev/null || true

# Boost trata LARAVEL_SAIL=1 + usuario "sail" como Sail activo, salvo en DevContainer
# (REMOTE_CONTAINERS=true). Dentro del contenedor los comandos van sin vendor/bin/sail.
export REMOTE_CONTAINERS=true

php -r '
$f = "boost.json";
$c = file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
$c["agents"] = $c["agents"] ?? ["cursor"];
$c["guidelines"] = true;
$c["mcp"] = true;
$c["skills"] = $c["skills"] ?? ["laravel-best-practices"];
$c["sail"] = false;
$c["cloud"] = false;
$c["nightwatch"] = false;
file_put_contents($f, json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
'

php artisan boost:install --guidelines --skills --mcp --no-interaction 2>/dev/null || true
