<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\ApplicationInstaller;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ApplicationInstallerTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_runs_all_steps_and_creates_the_initial_user(): void
    {
        config([
            'app.key' => null,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => storage_path('framework/testing/install-command.sqlite'),
        ]);

        @unlink(storage_path('framework/testing/install-command.sqlite'));

        Process::fake();

        Artisan::partialMock();
        Artisan::shouldReceive('call')->once()->with('config:clear', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('key:generate', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('storage:link', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('db:seed', ['--force' => true])->andReturn(0);

        $command = $this->mock(Command::class);
        $command->shouldReceive('info')->times(4);
        $command->shouldReceive('line')->times(5);

        app()->call([new ApplicationInstaller, 'install'], [
            'command' => $command,
            'name' => 'Install Admin',
            'email' => 'install@example.com',
            'password' => 'secret-password',
        ]);

        $user = User::query()->where('email', 'install@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Install Admin', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));

        Process::assertRan(function ($process) {
            return $process->command === 'apt-get update';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'apt-get install -y git curl unzip sqlite3 composer nodejs npm php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'composer install --no-interaction --prefer-dist --optimize-autoloader';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'npm install';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'npm run build';
        });
    }
}
