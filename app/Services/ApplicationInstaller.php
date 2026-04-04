<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
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

    public function install(Command $command, string $name, string $password): void
    {
        $this->installSystemDependencies($command);
        $this->installProjectDependencies($command);
        $this->initializeLaravel($command);
        $this->prepareRfidReaderEnvironment($command);
        $this->createInitialUser($command, $name, $password);
        $this->createSystemdServices($command);
    }

    private function installSystemDependencies(Command $command): void
    {
        $command->info('Installing system dependencies...');

        $this->runProcessStep($command, 'Updating package index', 'apt-get update');
        $this->runProcessStep(
            $command,
            'Installing required Linux packages',
            'apt-get install -y git curl unzip sqlite3 composer nodejs npm php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer python3 python3-venv python3-pip'
        );
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
        $this->runArtisanStep('db:seed', ['--force' => true]);
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
            'tonpi-rfid-listener.service' => $this->buildSystemdService(
                'TonPI RFID Listener',
                '/usr/bin/env php artisan rfid:listen'
            ),
        ];

        foreach ($services as $filename => $contents) {
            File::put($serviceDirectory.DIRECTORY_SEPARATOR.$filename, $contents);
        }

        $this->runProcessStep($command, 'Reloading systemd daemon', 'systemctl daemon-reload');
        $this->runProcessStep($command, 'Enabling player queue service', 'systemctl enable --now tonpi-player-queue.service');
        $this->runProcessStep($command, 'Enabling RFID listener service', 'systemctl enable --now tonpi-rfid-listener.service');
    }

    private function buildSystemdService(string $description, string $artisanCommand): string
    {
        $serviceUser = $this->serviceUser();
        $serviceGroup = $this->serviceGroup();

        return implode("\n", [
            '[Unit]',
            sprintf('Description=%s', $description),
            'After=network.target',
            '',
            '[Service]',
            'Type=simple',
            sprintf('User=%s', $serviceUser),
            sprintf('Group=%s', $serviceGroup),
            sprintf('WorkingDirectory=%s', base_path()),
            sprintf('ExecStart=%s', $artisanCommand),
            'Restart=always',
            'RestartSec=5',
            '',
            '[Install]',
            'WantedBy=multi-user.target',
            '',
        ]);
    }

    private function systemdServiceDirectory(): string
    {
        if (app()->runningUnitTests()) {
            return storage_path('framework/testing/systemd');
        }

        return '/etc/systemd/system';
    }

    private function serviceUser(): string
    {
        if (! function_exists('fileowner') || ! function_exists('posix_getpwuid')) {
            return 'root';
        }

        $ownerId = @fileowner(base_path());

        if (! is_int($ownerId)) {
            return 'root';
        }

        $owner = @posix_getpwuid($ownerId);

        return is_array($owner) && isset($owner['name']) ? (string) $owner['name'] : 'root';
    }

    private function serviceGroup(): string
    {
        if (! function_exists('filegroup') || ! function_exists('posix_getgrgid')) {
            return 'root';
        }

        $groupId = @filegroup(base_path());

        if (! is_int($groupId)) {
            return 'root';
        }

        $group = @posix_getgrgid($groupId);

        return is_array($group) && isset($group['name']) ? (string) $group['name'] : 'root';
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
}
