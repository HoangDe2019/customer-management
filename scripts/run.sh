#!/usr/bin/env bash
# Run all services (Linux/macOS). Usage: ./scripts/run.sh [--deploy-first]
set -e
cd "$(dirname "$0")/.."
DEPLOY_FIRST=false
for arg in "$@"; do
  [ "$arg" = "--deploy-first" ] && DEPLOY_FIRST=true
done

if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate
fi

if [ "$DEPLOY_FIRST" = true ]; then
  composer install -n
  php artisan migrate --force
  (cd frontend && (npm ci 2>/dev/null || npm install) && npm run build)
fi

echo "Starting API, Queue, Reverb, Frontend..."
npx concurrently -k -n "api,queue,reverb,frontend" -c "blue,magenta,yellow,green" \
  "php artisan serve" \
  "php artisan queue:work --tries=3" \
  "php artisan reverb:start" \
  "npm run dev --prefix frontend"
