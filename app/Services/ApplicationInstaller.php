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
        $this->createInitialUser($command, $name, $password);
    }

    private function installSystemDependencies(Command $command): void
    {
        $command->info('Installing system dependencies...');

        $this->runProcessStep($command, 'Updating package index', 'apt-get update');
        $this->runProcessStep(
            $command,
            'Installing required Linux packages',
            'apt-get install -y git curl unzip sqlite3 composer nodejs npm php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer'
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
        if (! file_exists(base_path('.env'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
        }
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
