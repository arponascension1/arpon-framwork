<?php

namespace Arpon\Console;

use Arpon\Foundation\Application;

// A simple, dependency-free base command class.
abstract class Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected string $signature;

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    abstract public function handle(): int;

    /**
     * Get the command signature.
     *
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the application instance.
     *
     * @param Application $app
     * @return void
     */
    public function setApp(Application $app): void
    {
        $this->app = $app;
    }

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @return void
     */
    public function info(string $string): void
    {
        echo "\033[32m{$string}\033[0m\n"; // Green color
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @return void
     */
    public function error(string $string): void
    {
        echo "\033[31m{$string}\033[0m\n"; // Red color
    }

    /**
     * Format input to be an associative array of rows and columns to display as a table.
     *
     * @param  array  $headers
     * @param  array  $rows
     * @return void
     */
    public function table(array $headers, array $rows): void
    {
        // Calculate column widths
        $columnWidths = array_map(function ($header) {
            return strlen($header);
        }, $headers);

        foreach ($rows as $row) {
            foreach ($row as $index => $column) {
                $columnWidths[$index] = max($columnWidths[$index], strlen($column));
            }
        }

        // Print header
        foreach ($headers as $index => $header) {
            echo str_pad($header, $columnWidths[$index] + 2); // +2 for padding
        }
        echo "\n";

        // Print separator
        foreach ($headers as $index => $header) {
            echo str_pad(' ', $columnWidths[$index] + 2, '-');
        }
        echo "\n";

        // Print rows
        foreach ($rows as $row) {
            foreach ($row as $index => $column) {
                echo str_pad($column, $columnWidths[$index] + 2);
            }
            echo "\n";
        }
    }
}