<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\ApplicationInstaller;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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
        File::deleteDirectory(storage_path('framework/testing/systemd'));
        File::deleteDirectory(storage_path('framework/testing/install'));

        Process::fake();

        putenv('TONPI_SERVICE_USER=tonpi-svc');
        putenv('TONPI_SERVICE_GROUP=tonpi-svc-group');

        Artisan::partialMock();
        Artisan::shouldReceive('call')->once()->with('config:clear', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('key:generate', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('storage:link', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);

        $command = $this->mock(Command::class);
        $command->shouldReceive('info')->zeroOrMoreTimes();
        $command->shouldReceive('line')->zeroOrMoreTimes();
        $command->shouldReceive('warn')->zeroOrMoreTimes();

        app()->call([new ApplicationInstaller, 'install'], [
            'command' => $command,
            'name' => 'Install Admin',
            'password' => 'secret-password',
        ]);

        $user = User::query()->where('name', 'Install Admin')->first();

        $this->assertNotNull($user);
        $this->assertSame('Install Admin', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertSame(1, User::query()->count());

        $this->assertFileExists(storage_path('framework/testing/systemd/tonpi-player-queue.service'));
        $this->assertFileExists(storage_path('framework/testing/systemd/tonpi-scheduler.service'));
        $this->assertFileExists(storage_path('framework/testing/systemd/tonpi-rfid-listener.service'));
        $this->assertFileExists(storage_path('framework/testing/systemd/tonpi-gpio-controls.service'));
        $this->assertFileExists(storage_path('framework/testing/systemd/tonpi-web.service'));

        $playerQueueService = file_get_contents(storage_path('framework/testing/systemd/tonpi-player-queue.service'));
        $schedulerService = file_get_contents(storage_path('framework/testing/systemd/tonpi-scheduler.service'));
        $rfidListenerService = file_get_contents(storage_path('framework/testing/systemd/tonpi-rfid-listener.service'));
        $gpioControlsService = file_get_contents(storage_path('framework/testing/systemd/tonpi-gpio-controls.service'));
        $webService = file_get_contents(storage_path('framework/testing/systemd/tonpi-web.service'));

        $this->assertIsString($playerQueueService);
        $this->assertIsString($schedulerService);
        $this->assertIsString($rfidListenerService);
        $this->assertIsString($gpioControlsService);
        $this->assertIsString($webService);
        $this->assertStringContainsString('ExecStart=/usr/bin/env php artisan queue:work --tries=1 --timeout=0', $playerQueueService);
        $this->assertStringContainsString('User=tonpi-svc', $playerQueueService);
        $this->assertStringContainsString('Group=tonpi-svc-group', $playerQueueService);
        $this->assertStringContainsString('ExecStart=/usr/bin/env php artisan schedule:work', $schedulerService);
        $this->assertStringContainsString('User=tonpi-svc', $schedulerService);
        $this->assertStringContainsString('Group=tonpi-svc-group', $schedulerService);
        $this->assertStringContainsString('ExecStart=/usr/bin/env php artisan rfid:listen', $rfidListenerService);
        $this->assertStringContainsString('ExecStart=/usr/bin/env php artisan gpio:listen-controls', $gpioControlsService);
        $this->assertStringContainsString('ExecStart=/usr/bin/env php artisan serve --host=0.0.0.0 --port=8000', $webService);

        $installEnvPath = storage_path('framework/testing/install/.env');
        $this->assertFileExists($installEnvPath);
        $this->assertStringContainsString('RFID_READER_COMMAND="'.base_path('rfid-reader/.venv/bin/python').' '.base_path('rfid-reader/read_rfid.py').'"', (string) file_get_contents($installEnvPath));
        $this->assertStringContainsString('RFID_READ_ONCE_COMMAND="'.base_path('rfid-reader/.venv/bin/python').' '.base_path('rfid-reader/read_rfid.py').' --once --timeout 10"', (string) file_get_contents($installEnvPath));
        $this->assertStringContainsString('GPIO_CONTROL_COMMAND="'.base_path('gpio-control/.venv/bin/python').' '.base_path('gpio-control/read_gpio.py').'"', (string) file_get_contents($installEnvPath));

        Process::assertRan(function ($process) {
            return $process->command === 'DEBIAN_FRONTEND=noninteractive apt-get update';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'DEBIAN_FRONTEND=noninteractive apt-get install -y git curl unzip sqlite3 composer nodejs npm php php-cli php-curl php-mbstring php-xml php-zip php-sqlite3 mplayer ffmpeg python3 python3-venv python3-pip';
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

        Process::assertRan(function ($process) {
            return $process->command === sprintf('python3 -m venv %s', escapeshellarg(base_path('rfid-reader/.venv')));
        });

        Process::assertRan(function ($process) {
            return $process->command === sprintf(
                '%s -m pip install --upgrade pip && %s -m pip install -r %s',
                escapeshellarg(base_path('rfid-reader/.venv/bin/python')),
                escapeshellarg(base_path('rfid-reader/.venv/bin/python')),
                escapeshellarg(base_path('rfid-reader/requirements.txt'))
            );
        });

        Process::assertRan(function ($process) {
            return $process->command === sprintf('python3 -m venv %s', escapeshellarg(base_path('gpio-control/.venv')));
        });

        Process::assertRan(function ($process) {
            return $process->command === sprintf(
                '%s -m pip install --upgrade pip && %s -m pip install -r %s',
                escapeshellarg(base_path('gpio-control/.venv/bin/python')),
                escapeshellarg(base_path('gpio-control/.venv/bin/python')),
                escapeshellarg(base_path('gpio-control/requirements.txt'))
            );
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl daemon-reload';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl enable --now tonpi-player-queue.service';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl enable --now tonpi-scheduler.service';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl enable --now tonpi-rfid-listener.service';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl enable --now tonpi-gpio-controls.service';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl enable --now tonpi-web.service';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'command -v mplayer >/dev/null';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'command -v ffprobe >/dev/null';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl is-enabled tonpi-player-queue.service >/dev/null';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl is-enabled tonpi-scheduler.service >/dev/null';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl is-enabled tonpi-rfid-listener.service >/dev/null';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl is-enabled tonpi-gpio-controls.service >/dev/null';
        });

        Process::assertRan(function ($process) {
            return $process->command === 'systemctl is-enabled tonpi-web.service >/dev/null';
        });

        putenv('TONPI_SERVICE_USER');
        putenv('TONPI_SERVICE_GROUP');
    }
}
