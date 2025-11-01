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
    protected string $signature = 'serve {--port=8000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Serve the application on the PHP development server';

    /**
     * Execute the console command.
     *
     * @param array $options
     * @return int
     */
    public function handle(array $args = [], array $options = []): int
    {
        $host = 'localhost';
        $port = $options['port'] ?? 8000;
        $publicPath = getcwd() . '/public';

        echo "Starting server on http://{$host}:{$port}\n";
        passthru("php -S {$host}:{$port} -t {$publicPath}");

        return 0;
    }
}