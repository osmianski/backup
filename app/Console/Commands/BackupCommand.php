<?php

namespace App\Console\Commands;

use App\Backup;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'backup';

    protected $description = 'Backs up local files to the cloud';

    /**
     * Execute the console command.
     */
    public function handle(Backup $backup): void
    {
        $backup->run($this->output);
    }
}
