<?php

namespace Arpon\Log;

class LogManager
{
    /**
     * The application instance.
     *
     * @var \Arpon\Application
     */
    protected $app;

    /**
     * Create a new log manager instance.
     *
     * @param  \Arpon\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Log a debug message.
     *
     * @param  string  $message
     * @return void
     */
    public function debug($message)
    {
        $this->write('debug', $message);
    }

    /**
     * Write a message to the log file.
     *
     * @param  string  $level
     * @param  string  $message
     * @return void
     */
    protected function write($level, $message)
    {
        $config = $this->app['config']->get('logging');
        $path = $config['channels']['single']['path'];

        file_put_contents($path, "[{$level}] {$message}\n", FILE_APPEND);
    }
}
