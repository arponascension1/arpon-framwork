<?php

namespace Arpon\Console;

// A simple, dependency-free base command class.
abstract class Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

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
    abstract public function handle();

    /**
     * Get the command name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * Format input to be an associative array of rows and columns to display as a table.
     *
     * @param  array  $headers
     * @param  array  $rows
     * @return void
     */
    public function table(array $headers, array $rows): void
    {
        // Calculate column widths
        $columnWidths = [];
        foreach ($headers as $index => $header) {
            $columnWidths[$index] = strlen($header);
        }

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
            echo str_pad('', $columnWidths[$index] + 2, '-');
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
