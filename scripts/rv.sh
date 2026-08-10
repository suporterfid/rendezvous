#!/usr/bin/env bash
set -e

# Rendezvous CLI Utility Script (scripts/rv.sh) for Linux / macOS

# Detect Docker Compose binary (v2 'docker compose' vs v1 'docker-compose')
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker-compose"
else
  echo "❌ Error: Docker Compose is not installed or not in PATH."
  exit 1
fi

CMD="${1:-help}"

case "$CMD" in
  up)
    echo "🚀 Starting Rendezvous Docker containers..."
    $COMPOSE up -d --build
    echo "✅ Rendezvous web running at: http://localhost:8080"
    echo "📧 Mailpit UI running at:     http://localhost:8025"
    ;;
  down)
    echo "🛑 Stopping Rendezvous Docker containers..."
    $COMPOSE down
    ;;
  build)
    echo "🔨 Building frontend assets and optimizing backend inside container..."
    $COMPOSE exec node npm run build
    $COMPOSE exec app php artisan config:cache
    $COMPOSE exec app php artisan route:cache
    echo "✅ Containerized build completed successfully!"
    ;;
  test)
    echo "🧪 Running complete PHPUnit & Pest test suite..."
    shift
    $COMPOSE exec app php artisan test "$@"
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
  logs)
    shift
    $COMPOSE logs -f "$@"
    ;;
  release)
    echo "📦 Packaging release bundle for Hostinger Shared Hosting..."
    mkdir -p dist_release
    
    # Build frontend assets inside container
    $COMPOSE exec node npm run build || true
    
    # Create staging directory
    STAGE_DIR="dist_release/staging"
    rm -rf "$STAGE_DIR"
    mkdir -p "$STAGE_DIR"
    
    # Copy files
    cp -r app bootstrap config database public routes vendor composer.json "$STAGE_DIR/" 2>/dev/null || true
    [ -f .htaccess ] && cp .htaccess "$STAGE_DIR/"
    
    # Compress into zip package
    ZIP_FILE="../rendezvous-release.zip"
    (cd "$STAGE_DIR" && zip -r "$ZIP_FILE" . >/dev/null)
    
    # Generate SHA256 Checksum
    (cd dist_release && sha256sum rendezvous-release.zip > SHA256SUMS 2>/dev/null || shasum -a 256 rendezvous-release.zip > SHA256SUMS)
    
    rm -rf "$STAGE_DIR"
    echo "✅ Release bundle created: dist_release/rendezvous-release.zip"
    echo "🔑 SHA256 Checksum generated in dist_release/SHA256SUMS"
    ;;
  deploy)
    # Deploy alias pointing to release packaging for Hostinger Shared Hosting
    "$0" release
    ;;
  help|*)
    echo "Rendezvous CLI Manager"
    echo "Usage: ./rv.sh {up|down|build|test|artisan|composer|npm|logs|release|deploy}"
    ;;
esac
