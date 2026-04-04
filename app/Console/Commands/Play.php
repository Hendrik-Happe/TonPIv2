<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:play')]
#[Description('Play sound files')]
class Play extends Command
{
    /**
     * Execute the console command.
     */
    public function handle() {}
}
