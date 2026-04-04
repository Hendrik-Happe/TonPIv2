<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:install')]
#[Description('Install the application')]
class Install extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
            $this->info('Installing the application...');
    
            
            // Perform installation tasks here, such as:
            // - Running migrations
            // - Seeding the database
            // - Publishing assets
            // - Setting up configuration files
    
            $this->info('Installation complete!');
        }
    }
}
