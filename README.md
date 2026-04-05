# TonPI

TonPI is an RFID-based music player system with GPIO integration, built using Laravel 11 and Livewire 3. It allows users to associate RFID tags with playlists, control playback via GPIO buttons, and manage everything through a web interface.

## Features

- **RFID Playlist Player**: Scan RFID tags to play associated playlists
- **GPIO Control**: Hardware buttons for play/pause, next/previous, volume control
- **Web Remote**: Mobile-friendly web interface for remote control
- **Playlist Management**: Create and edit playlists with volume profiles
- **Event History**: Audit trail for all playback events
- **Backup/Restore**: Full system backup and restore functionality
- **Hardware Dashboard**: Monitor RFID and GPIO status

## Requirements

- PHP 8.3+
- Composer
- Node.js 20.19+ or 22.12+
- npm
- SQLite or PostgreSQL
- mplayer/ffmpeg for audio playback
- RFID reader and GPIO hardware (optional for development)

## Installation

### Automated Installation (Recommended)

For a quick setup including system dependencies, run the installation script as root:

```bash
sudo ./install.sh
```

This script will:
- Install required system packages (PHP, Node.js 22 LTS, Composer, SQLite, mplayer, ffmpeg, etc.)
- Install PHP and Node.js dependencies
- Run the Laravel application installer as the original user (not root) to avoid file permission issues

For development environments where you don't have root access, run the script as your regular user:

```bash
./install.sh
```

This will:
- Skip system package installation (you must install them manually)
- Install PHP and Node.js dependencies
- Run the Laravel application installer with `--skip-system-deps` with `--skip-system-deps`

> Running `php artisan app:install` directly is still supported, but only if you want the command to install system packages itself. When using `sudo ./install.sh`, the script already installs system packages and then runs `php artisan app:install --skip-system-deps` to avoid duplicate package installation.

### Manual Installation

If you prefer to install dependencies manually or are on a different system:

1. Clone the repository:
   ```bash
   git clone https://github.com/Hendrik-Happe/TonPIv2.git
   cd TonPI
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Copy environment file and configure:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Run database migrations:
   ```bash
   php artisan migrate
   ```

6. Build assets:
   ```bash
   npm run build
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## Hardware Integration

TonPI supports RFID readers and GPIO controls for a complete embedded music player experience.

### RFID Reader Setup

The system uses MFRC522 RFID readers connected via SPI. The RFID reader service runs continuously and updates the database when tags are detected.

**Service Management:**
```bash
# Start RFID reader service
sudo systemctl start rfid-reader.service

# Check status
sudo systemctl status rfid-reader.service

# View logs
sudo journalctl -u rfid-reader.service -f
```

**Manual Testing:**
```bash
# Test RFID reader once
python3 rfid-reader/read_rfid.py --once --timeout 10

# Start continuous monitoring
python3 rfid-reader/read_rfid.py
```

### GPIO Control Setup

GPIO buttons and LEDs provide hardware control for the player. The system uses the following default pin mappings (BCM numbering):

- Previous Track: GPIO 17
- Next Track: GPIO 27
- Volume Down: GPIO 22
- Volume Up: GPIO 23
- Ready LED: GPIO 24
- Playing LED: GPIO 25

**Service Management:**
```bash
# Start GPIO control service
sudo systemctl start gpio-control.service

# Check status
sudo systemctl status gpio-control.service

# View logs
sudo journalctl -u gpio-control.service -f
```

**Configuration:**
Edit `.env` file to customize GPIO pins:
```bash
GPIO_BTN_PREVIOUS_PIN=17
GPIO_BTN_NEXT_PIN=27
GPIO_BTN_VOL_DOWN_PIN=22
GPIO_BTN_VOL_UP_PIN=23
GPIO_LED_READY_PIN=24
GPIO_LED_PLAYING_PIN=25
GPIO_BUTTON_DEBOUNCE_MS=180
GPIO_LED_POLL_INTERVAL_MS=500
```

### Apache Reverse Proxy (Production)

For production deployment, configure Apache as a reverse proxy:

1. The installation script automatically configures Apache with SSL
2. Access the application at `https://tonpi.local` (add to `/etc/hosts` if needed)
3. SSL certificates are self-signed by default - replace with proper certificates for production

**Apache Configuration:**
- Located at `/etc/apache2/sites-available/tonpi-apache.conf`
- Includes WebSocket proxy for Livewire
- Redirects HTTP to HTTPS

### Service Management Script

Use the included script to manage all services:

```bash
# Start all services
./services.sh start

# Stop all services
./services.sh stop

# Restart all services
./services.sh restart

# Check status
./services.sh status
```

### Hardware Troubleshooting

**RFID Reader Issues:**
- Check SPI interface is enabled: `raspi-config`
- Verify MFRC522 connections (SDA, SCK, MOSI, MISO, IRQ, GND, RST, 3.3V)
- Test with: `python3 -c "import RPi.GPIO as GPIO; GPIO.setmode(GPIO.BCM); print('GPIO OK')"`

**GPIO Issues:**
- Verify pin numbering (BCM vs BOARD)
- Check button wiring (normally open, pull-up resistors)
- Test LEDs with: `python3 -c "import RPi.GPIO as GPIO; GPIO.setmode(GPIO.BCM); GPIO.setup(24, GPIO.OUT); GPIO.output(24, GPIO.HIGH)"`

**Database Issues:**
- Ensure proper permissions: `chown tonpi:tonpi database/database.sqlite`
- Check SQLite concurrent access: services use read-only access where possible

## Configuration

Configure hardware settings in `config/player.php`:
- RFID reader settings
- GPIO pin mappings
- Audio player commands

## Testing

Run the test suite:
```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and ensure they pass
5. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.