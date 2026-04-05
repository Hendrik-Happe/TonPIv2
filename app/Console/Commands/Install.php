<?php

namespace App\Console\Commands;

use App\Services\ApplicationInstaller;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('app:install {--skip-system-deps}')] 
#[Description('Install the application')]
class Install extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ApplicationInstaller $installer): int
    {
        $skipSystemDependencies = $this->option('skip-system-deps') === true;

        if (! $skipSystemDependencies && ! $installer->isRunningAsRoot()) {
            $this->error('This command must be run as root to install system dependencies. Use --skip-system-deps to skip system package installation.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Name for the initial user', 'Administrator'));
        $password = (string) $this->secret('Password for the initial user');
        $passwordConfirmation = (string) $this->secret('Confirm the password');

        if ($name === '') {
            $this->error('The user name is required.');

            return self::FAILURE;
        }

        if ($password === '') {
            $this->error('The password is required.');

            return self::FAILURE;
        }

        if ($password !== $passwordConfirmation) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }

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
}
