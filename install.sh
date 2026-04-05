#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

# Speichere den ursprünglichen User für später
ORIGINAL_USER="${SUDO_USER:-$(whoami)}"
IS_ROOT=false
if [[ "${EUID}" -eq 0 ]]; then
  IS_ROOT=true
fi

if [[ "$IS_ROOT" == true ]]; then
  echo "🔧 Running as root - will install system packages and application"
else
  echo "🔧 Running as user - will only install application (system packages must be installed manually)"
fi

if [[ "$IS_ROOT" == true ]]; then
  echo "[1/5] Updating package lists..."
  apt-get update

  echo "[2/5] Installing bootstrap dependencies..."
  if ! apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip; then
    echo "⚠️  Initial package install failed. Attempting to fix broken packages..."
    apt-get install -f -y
    apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip
  fi

  echo "[3/5] Installing Node.js 22 LTS..."
  curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
  apt-get update
  if ! apt-get install -y nodejs; then
    echo "⚠️  Node.js install failed. Attempting to fix broken packages..."
    apt-get install -f -y
    apt-get install -y nodejs
  fi

  NODE_VERSION="$(node -v | tr -d 'v')"
  if ! node -v | grep -qE '^v(20\.|22\.)' ; then
    echo "❌ Installed Node.js version ${NODE_VERSION} is not supported. Require Node.js 20.19+ or 22.12+."
    exit 1
  fi

  echo "[4/5] Installing PHP dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader

  echo "[5/5] Running application installer as $ORIGINAL_USER..."
  sudo -u "$ORIGINAL_USER" php artisan app:install
else
  echo "[1/2] Installing PHP dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader

  echo "[2/2] Running application installer..."
  php artisan app:install --skip-system-deps
fi

echo "Installation finished."
