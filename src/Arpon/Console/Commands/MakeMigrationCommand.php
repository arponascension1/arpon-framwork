<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;

class MakeMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:migration {name} {--create=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(array $args = [], array $options = []): int
    {
        $name = $args[0] ?? null;
        $tableName = $options['create'] ?? null;

        if (is_null($tableName) && preg_match('/^create_([a-z0-9_]+)_table$/', $name, $matches)) {
            $tableName = $matches[1];
        }

        if (is_null($tableName)) {
            $tableName = $name;
        }
        $timestamp = date('Y_m_d_His');
        $fileName = $timestamp . '_' . $name . '.php';
        $filePath = BASE_PATH . '/database/migrations/' . $fileName;

        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

        $stub = <<<EOT
<?php

use Arpon\Database\Schema\Blueprint;
use Arpon\Database\Schema\Schema;

return new class
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            // Add your table columns here
            \$table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
EOT;

        file_put_contents($filePath, $stub);

        $this->info("Migration created successfully: {$fileName}");

        return 0;
    }
}
