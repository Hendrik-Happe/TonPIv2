#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "This script must be run as root (use sudo)." >&2
  exit 1
fi

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

echo "[1/4] Updating package lists..."
apt-get update

echo "[2/4] Installing bootstrap dependencies..."
if ! apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip; then
  echo "⚠️  Initial package install failed. Attempting to fix broken packages..."
  apt-get install -f -y
  apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip
fi

echo "[3/4] Installing Node.js 22 LTS..."
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

echo "[4/4] Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "Running application installer..."
php artisan app:install --skip-system-deps

echo "Installation finished."
