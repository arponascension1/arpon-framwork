<?php

namespace Arpon\Database;

use Arpon\Database\Schema\Blueprint;
use Arpon\Database\Schema\Schema;
use Arpon\Support\Facades\DB;

class Migrator
{
    protected DatabaseManager $db;
    protected string $migrationPath;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
        $this->migrationPath = BASE_PATH . '/database/migrations';
        Schema::setDatabaseManager($db);
    }

    public function run(): void
    {
        $this->ensureMigrationTableExists();

        $runMigrations = $this->getRunMigrations();
        $files = $this->getMigrationFiles();

        $batch = $this->getNextBatchNumber();

        foreach ($files as $file) {
            if (!in_array($file, $runMigrations)) {
                $this->runMigration($file, $batch);
            }
        }

        echo "Migrations complete.\n";
    }

    protected function ensureMigrationTableExists(): void
    {
        if (!$this->db->schema()->hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
            echo "'migrations' table created.\n";
        }
    }

    protected function getRunMigrations(): array
    {
        return DB::table('migrations')->pluck('migration')->toArray();
    }

    protected function getMigrationFiles(): array
    {
        $files = scandir($this->migrationPath);
        sort($files);
        return array_filter($files, fn($file) => str_ends_with($file, '.php'));
    }

    protected function getNextBatchNumber(): int
    {
        return DB::table('migrations')->max('batch') + 1;
    }

    protected function runMigration(string $file, int $batch): void
    {
        $migration = require $this->migrationPath . '/' . $file;
        $migration->up();
        $this->logMigration($file, $batch);
        echo "Migrated: " . $file . "\n";
    }

    protected function logMigration(string $file, int $batch): void
    {
        DB::table('migrations')->insert([
            'migration' => $file,
            'batch' => $batch,
        ]);
    }
}
