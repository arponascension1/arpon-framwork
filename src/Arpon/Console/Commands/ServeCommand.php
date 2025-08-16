<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;

class ServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve {--port=8000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve the application on the PHP development server';

    /**
     * Execute the console command.
     *
     * @param array $options
     * @return int
     */
    public function handle(array $options = []): int
    {
        $host = 'localhost';
        $port = $options['port'] ?? 8000;
        $publicPath = getcwd() . '/public';

        echo "Starting server on http://{$host}:{$port}\n";
        passthru("php -S {$host}:{$port} -t {$publicPath} {$publicPath}/index.php");

        return 0;
    }
}
