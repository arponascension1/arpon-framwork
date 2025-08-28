<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;

class StorageLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected string $signature = 'storage:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a symbolic link from "storage/app/public" to "public/storage"';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $link = base_path('public/storage');

        if (file_exists($link)) {
            $this->error('The "public/storage" directory already exists.');
            return 1;
        }

        symlink(base_path('storage/app/public'), $link);

        $this->info('The [public/storage] directory has been linked.');

        return 0;
    }
}
