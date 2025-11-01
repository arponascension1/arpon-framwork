<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Arpon\Support\Facades\DB;

class WipeCommand extends Command
{
    protected string $signature = 'db:wipe';
    protected string $description = 'Drop all tables from the database';

    public function handle(): int
    {
        $colname = 'Tables_in_' . env('DB_DATABASE');
        $tables = DB::select('SHOW TABLES');
        $droplist = [];
        foreach ($tables as $table) {
            $droplist[] = $table[$colname];
        }
        if (!empty($droplist)) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($droplist as $table) {
                DB::statement('DROP TABLE IF EXISTS ' . $table);
            }
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            $this->info('All tables have been dropped successfully!');
        } else {
            $this->info('No tables found in the database.');
        }
        return 0;
    }
}
