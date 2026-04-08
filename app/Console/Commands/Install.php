<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ApplicationInstaller;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('app:install {--skip-system-deps} {--skip-user-creation}')]
#[Description('Install the application')]
class Install extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ApplicationInstaller $installer): int
    {
        $skipSystemDependencies = $this->option('skip-system-deps') === true;
        $skipUserCreation = $this->option('skip-user-creation') === true;

        if (! $skipSystemDependencies && ! $installer->isRunningAsRoot()) {
            $this->error('This command must be run as root to install system dependencies. Use --skip-system-deps to skip system package installation.');

            return self::FAILURE;
        }

        $credentials = $this->resolveInstallUserCredentials($skipUserCreation);

        if ($credentials === false) {
            return self::FAILURE;
        }

        [$name, $password] = $credentials;

        $this->info('Installing the application...');

        try {
            $installer->install($this, $name, $password, $skipSystemDependencies);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Installation complete!');

        return self::SUCCESS;
    }

    /**
     * @return array{0: ?string, 1: ?string}|false
     */
    private function resolveInstallUserCredentials(bool $skipUserCreation): array|false
    {
        $userCount = User::count();

        if ($userCount > 0) {
            if ($skipUserCreation) {
                $this->info('Skipping user creation - users already exist.');

                return [null, null];
            }

            $this->warn("Found {$userCount} existing user(s). Use --skip-user-creation to skip user creation.");

            if (! $this->confirm('Do you want to create an additional user?', false)) {
                $this->info('Skipping user creation.');

                return [null, null];
            }

            return $this->promptForUserCredentials('additional user');
        }

        return $this->promptForUserCredentials('initial user');
    }

    /**
     * @return array{0: string, 1: string}|false
     */
    private function promptForUserCredentials(string $label): array|false
    {
        $name = trim((string) $this->ask("Name for the {$label}", 'Administrator'));
        $password = (string) $this->secret("Password for the {$label}");
        $passwordConfirmation = (string) $this->secret('Confirm the password');

        if ($name === '') {
            $this->error('The user name is required.');

            return false;
        }

        if ($password === '') {
            $this->error('The password is required.');

            return false;
        }

        if ($password !== $passwordConfirmation) {
            $this->error('The passwords do not match.');

            return false;
        }

        return [$name, $password];
    }
}
