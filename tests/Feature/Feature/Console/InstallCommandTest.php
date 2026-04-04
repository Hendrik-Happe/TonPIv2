<?php

namespace Tests\Feature\Feature\Console;

use App\Console\Commands\Install;
use App\Services\ApplicationInstaller;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    public function test_install_command_prompts_for_user_data_and_runs_the_installer(): void
    {
        $installer = $this->mock(ApplicationInstaller::class);
        $installer->shouldReceive('isRunningAsRoot')->once()->andReturn(true);
        $installer->shouldReceive('install')
            ->once()
            ->withArgs(function ($command, $name, $password) {
                return $command instanceof Install
                    && $name === 'Install Admin'
                    && $password === 'secret-password';
            });

        $this->artisan('app:install')
            ->expectsQuestion('Name for the initial user', 'Install Admin')
            ->expectsQuestion('Password for the initial user', 'secret-password')
            ->expectsQuestion('Confirm the password', 'secret-password')
            ->expectsOutput('Installing the application...')
            ->expectsOutput('Installation complete!')
            ->assertSuccessful();
    }
}
