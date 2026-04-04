#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "This script must be run as root (use sudo)." >&2
  exit 1
fi

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

echo "[1/3] Installing bootstrap dependencies..."
apt-get update
apt-get install -y git curl unzip sqlite3 composer nodejs npm php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip

echo "[2/3] Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "[3/3] Running application installer..."
php artisan app:install

echo "Installation finished."
