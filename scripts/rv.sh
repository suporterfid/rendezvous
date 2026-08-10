#!/usr/bin/env bash
set -e

# Rendezvous CLI Utility Script (rv.sh)

COMPOSE="docker compose"

case "$1" in
  up)
    echo "🚀 Starting Rendezvous Docker containers..."
    $COMPOSE up -d --build
    echo "✅ Rendezvous environment running at http://localhost:8080"
    echo "📧 Mailpit UI running at http://localhost:8025"
    ;;
  down)
    echo "🛑 Stopping Rendezvous Docker containers..."
    $COMPOSE down
    ;;
  artisan)
    shift
    $COMPOSE exec app php artisan "$@"
    ;;
  composer)
    shift
    $COMPOSE exec app composer "$@"
    ;;
  npm)
    shift
    $COMPOSE exec node npm "$@"
    ;;
  test)
    echo "🧪 Running backend & domain unit tests..."
    $COMPOSE exec app php artisan test
    ;;
  logs)
    $COMPOSE logs -f
    ;;
  release)
    echo "📦 Packaging release for Hostinger Shared Hosting..."
    mkdir -p dist_release
    $COMPOSE exec frontend npm run build || true
    mkdir -p build_output
    cp -r public/* build_output/ 2>/dev/null || true
    cp -r app build_output/
    cp .htaccess build_output/ 2>/dev/null || true
    cd build_output && zip -r ../dist_release/rendezvous-release.zip . && cd ..
    sha256sum dist_release/rendezvous-release.zip > dist_release/SHA256SUMS
    echo "✅ Release zip generated at dist_release/rendezvous-release.zip"
    ;;
  *)
    echo "Usage: ./scripts/rv.sh {up|down|artisan|composer|npm|test|logs|release}"
    exit 1
    ;;
esac
