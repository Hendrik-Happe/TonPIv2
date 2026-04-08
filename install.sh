#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

# Speichere den ursprünglichen User für später
ORIGINAL_USER="${SUDO_USER:-$(whoami)}"
SERVICE_USER="${TONPI_SERVICE_USER:-tonpi}"
SERVICE_GROUP="${TONPI_SERVICE_GROUP:-$SERVICE_USER}"
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
  echo "[1/12] Updating package lists..."
  apt-get update

  echo "[2/12] Installing bootstrap dependencies..."
  if ! apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 php8.4-fpm mplayer ffmpeg python3 python3-venv python3-pip apache2 ssl-cert; then
    echo "⚠️  Initial package install failed. Attempting to fix broken packages..."
    apt-get install -f -y
    apt-get install -y git curl unzip sqlite3 composer php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip apache2 ssl-cert
  fi

  echo "[3/12] Installing Node.js 22 LTS..."
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

  echo "[4/12] Creating dedicated service user..."
  if ! getent group "$SERVICE_GROUP" >/dev/null 2>&1; then
    groupadd --system "$SERVICE_GROUP"
  fi
  if ! id -u "$SERVICE_USER" >/dev/null 2>&1; then
    useradd --system --gid "$SERVICE_GROUP" --create-home --home-dir /var/lib/tonpi --shell /usr/sbin/nologin "$SERVICE_USER"
  fi

  echo "[5/12] Starting PHP-FPM..."
  systemctl enable php8.4-fpm
  systemctl start php8.4-fpm

  echo "[6/12] Fixing initial file permissions..."
  chown -R "$ORIGINAL_USER:$ORIGINAL_USER" "$PROJECT_DIR"

  echo "[7/12] Running application installer as $ORIGINAL_USER..."
  sudo -u "$ORIGINAL_USER" TONPI_SERVICE_USER="$SERVICE_USER" TONPI_SERVICE_GROUP="$SERVICE_GROUP" php artisan app:install --skip-system-deps

  echo "[8/12] Configuring Apache direct access..."
  sed "s|{{PROJECT_DIR}}|$PROJECT_DIR|g" tonpi-apache.conf | tee /etc/apache2/sites-available/tonpi-apache.conf > /dev/null
  a2enmod proxy_fcgi
  a2ensite tonpi-apache.conf
  systemctl reload apache2

  echo "[9/12] Setting up user and group permissions..."
  usermod -a -G audio www-data
  usermod -a -G spi,gpio,audio "$SERVICE_USER"
  usermod -a -G "$SERVICE_GROUP" "$ORIGINAL_USER"
  usermod -a -G www-data "$ORIGINAL_USER"
  usermod -a -G www-data "$SERVICE_USER"

  echo "[10/12] Setting final file permissions..."
  chown -R "$SERVICE_USER:www-data" "$PROJECT_DIR"
  find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
  find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
  chmod -R 775 "$PROJECT_DIR"/storage "$PROJECT_DIR"/bootstrap/cache
  mkdir -p database
  chown -R "$SERVICE_USER:www-data" database
  chmod 775 database
  touch database/database.sqlite
  chown "$SERVICE_USER:www-data" database/database.sqlite
  chmod 664 database/database.sqlite

  echo "[11/12] Restarting services..."
  systemctl restart php8.4-fpm tonpi-player-queue tonpi-scheduler tonpi-rfid-listener tonpi-gpio-controls tonpi-web || true

  echo "[12/12] Showing service status..."

  echo ""
  echo "✅ Installation complete!"
  echo ""
  echo "📋 Running services:"
  echo ""
  systemctl status tonpi-player-queue.service --no-pager || true
  systemctl status tonpi-scheduler.service --no-pager || true
  systemctl status php8.4-fpm --no-pager || true
  echo ""
  echo "⚠️  To check service logs:"
  echo "   sudo journalctl -u tonpi-player-queue -f"
  echo "   sudo journalctl -u tonpi-scheduler -f"
  echo "   sudo journalctl -u php8.4-fpm -f"
  echo "   sudo journalctl -u tonpi-rfid-listener -f"
  echo "   sudo journalctl -u tonpi-gpio-controls -f"
  echo ""

else
  echo "[1/2] Fixing file permissions..."
  mkdir -p database
  touch database/database.sqlite
  chmod 664 database/database.sqlite
  mkdir -p storage
  mkdir -p bootstrap/cache
  chmod -R 775 storage bootstrap/cache

  echo "[2/2] Running application installer..."
  echo "ℹ️  Tip: Run with sudo to create a dedicated service user automatically."
  php artisan app:install --skip-system-deps
fi

echo "Installation finished."
