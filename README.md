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
- Node.js & npm
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
- Install required system packages (PHP, Node.js, Composer, SQLite, mplayer, ffmpeg, etc.)
- Install PHP and Node.js dependencies
- Run the Laravel application installer

### Manual Installation

If you prefer to install dependencies manually or are on a different system:

1. Clone the repository:
   ```bash
   git clone <repository-url>
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

## Usage

### RFID Learning Mode
To associate RFID tags with playlists:
```bash
php artisan rfid:learn
```

### GPIO Control
Configure GPIO pins and start the GPIO listener:
```bash
php artisan gpio:start
```

### Backup and Restore
Create a backup:
```bash
php artisan backup:create
```

Restore from backup:
```bash
php artisan backup:restore /path/to/backup.zip
```

### Web Interface
Access the web interface at `http://localhost:8000` to manage playlists, view event history, and use the remote control.

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