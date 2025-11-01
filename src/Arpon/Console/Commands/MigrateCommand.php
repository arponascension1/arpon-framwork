<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Arpon\Database\Migrator;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected string $signature = 'migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Run the database migrations';

    protected Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->migrator->run($this->getMigrationPath());
        return 0;
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath(): string
    {
        return $this->app->basePath() . '/database/migrations';
    }
}
