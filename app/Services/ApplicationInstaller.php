<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class ApplicationInstaller
{
    public function isRunningAsRoot(): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        return function_exists('posix_geteuid') && posix_geteuid() === 0;
    }

    public function install(Command $command, ?string $name, ?string $password, bool $skipSystemDependencies = false): void
    {
        if (! $skipSystemDependencies) {
            $this->installSystemDependencies($command);
        } else {
            $command->info('Skipping system dependency installation.');
        }

        $this->installProjectDependencies($command);
        $this->initializeLaravel($command);
        $this->prepareRfidReaderEnvironment($command);
        $this->prepareGpioControlEnvironment($command);

        if ($name !== null && $password !== null) {
            $this->createInitialUser($command, $name, $password);
        } else {
            $command->info('Skipping initial user creation.');
        }

        $this->runArtisanStep('db:seed', ['--force' => true]);
        $this->createSystemdServices($command);

        $this->runPostInstallChecks($command);
    }

    private function installSystemDependencies(Command $command): void
    {
        $command->info('Installing system dependencies...');

        $this->runProcessStep($command, 'Updating package index', 'DEBIAN_FRONTEND=noninteractive apt-get update');

        try {
            $this->runProcessStep(
                $command,
                'Installing required Linux packages',
                'DEBIAN_FRONTEND=noninteractive apt-get install -y git curl unzip sqlite3 composer nodejs npm php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip'
            );
        } catch (RuntimeException $exception) {
            $command->warn('Initial package installation failed. Attempting to repair package state...');

            $this->runProcessStep(
                $command,
                'Fixing broken packages',
                'DEBIAN_FRONTEND=noninteractive apt-get install -f -y && dpkg --configure -a'
            );

            $this->runProcessStep($command, 'Updating package index again', 'DEBIAN_FRONTEND=noninteractive apt-get update');

            try {
                $this->runProcessStep(
                    $command,
                    'Retrying required Linux package installation',
                    'DEBIAN_FRONTEND=noninteractive apt-get install -y git curl unzip sqlite3 composer nodejs npm php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip'
                );
            } catch (RuntimeException $retryException) {
                $heldPackages = trim(Process::path(base_path())->run('apt-mark showhold')->output());
                $message = $retryException->getMessage();

                throw new RuntimeException(sprintf(
                    "%s\nHeld packages: %s\nPlease resolve any held or broken packages before re-running app:install, or use the provided install.sh script.",
                    $message,
                    $heldPackages === '' ? 'none' : $heldPackages,
                ));
            }
        }
    }

    private function installProjectDependencies(Command $command): void
    {
        $command->info('Installing PHP and frontend dependencies...');

        $this->runProcessStep(
            $command,
            'Installing Composer dependencies',
            'composer install --no-interaction --prefer-dist --optimize-autoloader'
        );
        $this->runProcessStep($command, 'Installing NPM dependencies', 'npm install');
        $this->runProcessStep($command, 'Building frontend assets', 'npm run build');
    }

    private function initializeLaravel(Command $command): void
    {
        $command->info('Initializing Laravel...');

        $this->ensureEnvironmentFileExists();
        $this->ensureSqliteDatabaseExists();

        $this->runArtisanStep('config:clear');

        if (blank(config('app.key'))) {
            $this->runArtisanStep('key:generate', ['--force' => true]);
        }

        $this->runArtisanStep('storage:link', ['--force' => true]);
        $this->runArtisanStep('migrate', ['--force' => true]);
    }

    private function ensureEnvironmentFileExists(): void
    {
        $environmentFilePath = $this->environmentFilePath();

        if (! file_exists($environmentFilePath)) {
            File::ensureDirectoryExists(dirname($environmentFilePath));
            File::copy(base_path('.env.example'), $environmentFilePath);
        }
    }

    private function prepareRfidReaderEnvironment(Command $command): void
    {
        $command->info('Preparing RFID reader environment...');

        $readerDirectory = base_path('rfid-reader');
        $requirementsPath = $readerDirectory.DIRECTORY_SEPARATOR.'requirements.txt';
        $venvPath = $readerDirectory.DIRECTORY_SEPARATOR.'.venv';
        $venvPythonPath = $venvPath.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'python';
        $readerScriptPath = $readerDirectory.DIRECTORY_SEPARATOR.'read_rfid.py';

        if (! file_exists($requirementsPath)) {
            throw new RuntimeException('Missing RFID requirements file at rfid-reader/requirements.txt.');
        }

        if (! file_exists($readerScriptPath)) {
            throw new RuntimeException('Missing RFID reader script at rfid-reader/read_rfid.py.');
        }

        $this->runProcessStep(
            $command,
            'Creating Python virtual environment for RFID reader',
            sprintf('python3 -m venv %s', escapeshellarg($venvPath))
        );

        $this->runProcessStep(
            $command,
            'Installing RFID reader Python dependencies',
            sprintf(
                '%s -m pip install --upgrade pip && %s -m pip install -r %s',
                escapeshellarg($venvPythonPath),
                escapeshellarg($venvPythonPath),
                escapeshellarg($requirementsPath)
            )
        );

        $this->setOrAppendEnvironmentValue(
            'RFID_READER_COMMAND',
            sprintf('%s %s', $venvPythonPath, $readerScriptPath)
        );

        $this->setOrAppendEnvironmentValue(
            'RFID_READ_ONCE_COMMAND',
            sprintf('%s %s --once --timeout 10', $venvPythonPath, $readerScriptPath)
        );
    }

    private function prepareGpioControlEnvironment(Command $command): void
    {
        $command->info('Preparing GPIO control environment...');

        $readerDirectory = base_path('gpio-control');
        $requirementsPath = $readerDirectory.DIRECTORY_SEPARATOR.'requirements.txt';
        $venvPath = $readerDirectory.DIRECTORY_SEPARATOR.'.venv';
        $venvPythonPath = $venvPath.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'python';
        $readerScriptPath = $readerDirectory.DIRECTORY_SEPARATOR.'read_gpio.py';

        if (! file_exists($requirementsPath)) {
            throw new RuntimeException('Missing GPIO requirements file at gpio-control/requirements.txt.');
        }

        if (! file_exists($readerScriptPath)) {
            throw new RuntimeException('Missing GPIO reader script at gpio-control/read_gpio.py.');
        }

        $this->runProcessStep(
            $command,
            'Creating Python virtual environment for GPIO controls',
            sprintf('python3 -m venv %s', escapeshellarg($venvPath))
        );

        $this->runProcessStep(
            $command,
            'Installing GPIO control Python dependencies',
            sprintf(
                '%s -m pip install --upgrade pip && %s -m pip install -r %s',
                escapeshellarg($venvPythonPath),
                escapeshellarg($venvPythonPath),
                escapeshellarg($requirementsPath)
            )
        );

        $this->setOrAppendEnvironmentValue(
            'GPIO_CONTROL_COMMAND',
            sprintf('%s %s', $venvPythonPath, $readerScriptPath)
        );
    }

    private function ensureSqliteDatabaseExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $databasePath = (string) config('database.connections.sqlite.database');

        if ($databasePath === ':memory:' || $databasePath === '') {
            return;
        }

        File::ensureDirectoryExists(dirname($databasePath));

        if (! file_exists($databasePath)) {
            File::put($databasePath, '');
        }
    }

    private function createInitialUser(Command $command, string $name, string $password): void
    {
        $command->info('Creating the initial user...');

        User::query()->updateOrCreate(
            ['name' => $name],
            ['password' => Hash::make($password)],
        );
    }

    private function createSystemdServices(Command $command): void
    {
        $command->info('Creating systemd services...');

        $serviceDirectory = $this->systemdServiceDirectory();
        File::ensureDirectoryExists($serviceDirectory);

        $services = [
            'tonpi-player-queue.service' => $this->buildSystemdService(
                'TonPI Player Queue Worker',
                '/usr/bin/env php artisan queue:work --tries=1 --timeout=0'
            ),
            'tonpi-scheduler.service' => $this->buildSystemdService(
                'TonPI Laravel Scheduler',
                '/usr/bin/env php artisan schedule:work'
            ),
            'tonpi-rfid-listener.service' => $this->buildSystemdService(
                'TonPI RFID Listener',
                '/usr/bin/env php artisan rfid:listen'
            ),
            'tonpi-gpio-controls.service' => $this->buildSystemdService(
                'TonPI GPIO Controls',
                '/usr/bin/env php artisan gpio:listen-controls'
            ),
            'tonpi-web.service' => $this->buildSystemdService(
                'TonPI Web Server',
                '/usr/bin/env php artisan serve --host=0.0.0.0 --port=8000'
            ),
        ];

        foreach ($services as $filename => $contents) {
            File::put($serviceDirectory.DIRECTORY_SEPARATOR.$filename, $contents);
        }

        $this->runProcessStep($command, 'Reloading systemd daemon', 'systemctl daemon-reload');
        $this->runProcessStep($command, 'Enabling player queue service', 'systemctl enable --now tonpi-player-queue.service');
        $this->runProcessStep($command, 'Enabling scheduler service', 'systemctl enable --now tonpi-scheduler.service');
        $this->runProcessStep($command, 'Enabling RFID listener service', 'systemctl enable --now tonpi-rfid-listener.service');
        $this->runProcessStep($command, 'Enabling GPIO controls service', 'systemctl enable --now tonpi-gpio-controls.service');
        $this->runProcessStep($command, 'Enabling web service', 'systemctl enable --now tonpi-web.service');
    }

    private function runPostInstallChecks(Command $command): void
    {
        $command->info('Running post-install checks...');

        $this->runProcessStep($command, 'Checking mplayer installation', 'command -v mplayer >/dev/null');
        $this->runProcessStep($command, 'Checking ffprobe installation', 'command -v ffprobe >/dev/null');

        $this->runProcessStep($command, 'Checking queue service enabled', 'systemctl is-enabled tonpi-player-queue.service >/dev/null');
        $this->runProcessStep($command, 'Checking scheduler service enabled', 'systemctl is-enabled tonpi-scheduler.service >/dev/null');
        $this->runProcessStep($command, 'Checking RFID listener service enabled', 'systemctl is-enabled tonpi-rfid-listener.service >/dev/null');
        $this->runProcessStep($command, 'Checking GPIO controls service enabled', 'systemctl is-enabled tonpi-gpio-controls.service >/dev/null');
        $this->runProcessStep($command, 'Checking web service enabled', 'systemctl is-enabled tonpi-web.service >/dev/null');

        if (! file_exists('/dev/spidev0.0')) {
            $command->warn('SPI device /dev/spidev0.0 not found. Enable SPI before using RC522.');
        }

        if (app()->runningUnitTests()) {
            return;
        }

        if (trim((string) shell_exec('amixer scontrols | grep -i PCM')) === '') {
            $command->warn('No PCM control found via amixer. Check ALSA audio device configuration.');
        }
    }

    private function environmentFilePath(): string
    {
        if (app()->runningUnitTests()) {
            return storage_path('framework/testing/install/.env');
        }

        return base_path('.env');
    }

    private function setOrAppendEnvironmentValue(string $key, string $value): void
    {
        $environmentFilePath = $this->environmentFilePath();

        if (! file_exists($environmentFilePath)) {
            return;
        }

        $environmentFileContents = File::get($environmentFilePath);
        $normalizedValue = $this->normalizeEnvironmentValue($value);
        $line = sprintf('%s=%s', $key, $normalizedValue);

        if (preg_match(sprintf('/^%s=.*/m', preg_quote($key, '/')), $environmentFileContents) === 1) {
            $updatedContents = preg_replace(
                sprintf('/^%s=.*/m', preg_quote($key, '/')),
                $line,
                $environmentFileContents,
                1
            );

            if (is_string($updatedContents)) {
                File::put($environmentFilePath, $updatedContents);
            }

            return;
        }

        $suffix = str_ends_with($environmentFileContents, "\n") ? '' : "\n";
        File::put($environmentFilePath, $environmentFileContents.$suffix.$line."\n");
    }

    private function normalizeEnvironmentValue(string $value): string
    {
        if (preg_match('/\s/', $value) !== 1) {
            return $value;
        }

        return '"'.str_replace('"', '\\"', $value).'"';
    }

    private function runArtisanStep(string $command, array $arguments = []): void
    {
        $result = Artisan::call($command, $arguments);

        if ($result !== 0) {
            throw new RuntimeException(sprintf('The artisan step "%s" failed.', $command));
        }
    }

    private function runProcessStep(Command $command, string $description, string $shellCommand): void
    {
        $command->line($description);

        $result = Process::path(base_path())
            ->timeout(0)
            ->run($shellCommand);

        if ($result->failed()) {
            throw new RuntimeException(sprintf(
                "%s failed.\n%s",
                $description,
                trim($result->errorOutput() ?: $result->output()),
            ));
        }
    }

    private function systemdServiceDirectory(): string
    {
        if (app()->runningUnitTests()) {
            return storage_path('framework/testing/systemd');
        }

        return '/etc/systemd/system';
    }

    private function buildSystemdService(string $description, string $execStart): string
    {
        $user = $this->systemUser();
        $group = $this->systemGroup($user);
        $workingDirectory = base_path();

        return implode("\n", [
            '[Unit]',
            sprintf('Description=%s', $description),
            'After=network.target',
            '',
            '[Service]',
            'Type=simple',
            sprintf('User=%s', $user),
            sprintf('Group=%s', $group),
            sprintf('WorkingDirectory=%s', $workingDirectory),
            sprintf('ExecStart=%s', $execStart),
            'Restart=always',
            'RestartSec=5',
            '',
            '[Install]',
            'WantedBy=multi-user.target',
            '',
        ]);
    }

    private function systemUser(): string
    {
        $configuredServiceUser = trim((string) getenv('TONPI_SERVICE_USER'));

        if ($configuredServiceUser !== '') {
            return $configuredServiceUser;
        }

        if (app()->runningUnitTests()) {
            return 'www-data';
        }

        $sudoUser = trim((string) getenv('SUDO_USER'));

        if ($sudoUser !== '') {
            return $sudoUser;
        }

        return Str::of((string) shell_exec('id -un'))->trim()->value() ?: 'www-data';
    }

    private function systemGroup(string $fallbackUser): string
    {
        $configuredServiceGroup = trim((string) getenv('TONPI_SERVICE_GROUP'));

        if ($configuredServiceGroup !== '') {
            return $configuredServiceGroup;
        }

        if (app()->runningUnitTests()) {
            return 'www-data';
        }

        return $fallbackUser;
    }
}
