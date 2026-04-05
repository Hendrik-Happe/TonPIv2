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
  echo "[1/8] Updating package lists..."
  apt-get update

  echo "[2/8] Installing bootstrap dependencies..."
  if ! apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 php8.4-fpm mplayer ffmpeg python3 python3-venv python3-pip apache2 ssl-cert; then
    echo "⚠️  Initial package install failed. Attempting to fix broken packages..."
    apt-get install -f -y
    apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip apache2 ssl-cert
  fi

  echo "[3/8] Installing Node.js 22 LTS..."
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

  echo "[5/8] Installing Python GPIO dependencies..."
  sudo -u "$ORIGINAL_USER" pip3 install --break-system-packages RPi.GPIO mfrc522

  echo "[6/8] Installing PHP dependencies..."
  sudo -u "$ORIGINAL_USER" composer install --no-interaction --prefer-dist --optimize-autoloader

  echo "[7/12] Starting PHP-FPM..."
  systemctl enable php8.4-fpm
  systemctl start php8.4-fpm

  echo "[8/12] Fixing file permissions..."
  chown -R "$ORIGINAL_USER:$ORIGINAL_USER" .

  echo "[9/12] Running application installer as $ORIGINAL_USER..."
  sudo -u "$ORIGINAL_USER" php artisan app:install --skip-system-deps

  echo "[10/12] Installing systemd services..."
  cp rfid-reader.service /etc/systemd/system/
  cp gpio-control.service /etc/systemd/system/
  systemctl daemon-reload
  systemctl enable rfid-reader.service
  systemctl enable gpio-control.service

  echo "[11/12] Configuring Apache direct access..."
  sed "s|{{PROJECT_DIR}}|$PROJECT_DIR|g" tonpi-apache.conf | tee /etc/apache2/sites-available/tonpi-apache.conf > /dev/null
  a2enmod proxy_fcgi
  a2ensite tonpi-apache.conf
  systemctl reload apache2

  echo "[12/12] Fixing final permissions..."
  chown -R "$ORIGINAL_USER:$ORIGINAL_USER" .
  mkdir -p database
  touch database/database.sqlite
  chown "$ORIGINAL_USER:$ORIGINAL_USER" database/database.sqlite
  chmod 664 database/database.sqlite
  mkdir -p storage
  mkdir -p bootstrap/cache
  chmod -R 775 storage bootstrap/cache

else
  echo "[1/4] Installing PHP dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader

  echo "[2/4] Installing Python GPIO dependencies..."
  pip3 install --break-system-packages RPi.GPIO mfrc522

  echo "[3/4] Fixing file permissions..."
  mkdir -p database
  touch database/database.sqlite
  chmod 664 database/database.sqlite
  mkdir -p storage
  mkdir -p bootstrap/cache
  chmod -R 775 storage bootstrap/cache

  echo "[4/4] Running application installer..."
  php artisan app:install --skip-system-deps
fi

echo "Installation finished."
