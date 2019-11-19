<?php

namespace Log1x\EjectBlocks\Console\Commands;

use Symfony\Component\Process\Process;
use Roots\Acorn\Console\Commands\Command as CommandBase;

class Command extends CommandBase
{
    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return false
     */
    public function error($string, $verbosity = null)
    {
        $this->line('');
        $this->line($string, 'error', $verbosity);
        $this->line('');
        return false;
    }

    /**
     * Execute a process and return the status to console.
     *
     * @param  string|array $commands
     * @param  boolean      $output
     * @return mixed
     */
    protected function exec($commands, $output = false)
    {
        if (! is_array($commands)) {
            $commands = explode(' ', $commands);
        }

        $process = new Process($commands);
        $process->run();

        if ($output) {
            return $process->getOutput();
        }

        return true;
    }

    /**
     * Run a task in the console.
     *
     * @param  string        $title
     * @param  callable|null $task
     * @param  string        $status
     * @return mixed
     */
    protected function task($title, $task = null, $status = '...')
    {
        if (! $task) {
            return $this->output->write("$title: '<info>✔</info>'");
        }

        $this->output->write("$title: <comment>{$status}</comment>");

        try {
            $status = $task() === false ? false : true;
        } catch (\Exception $e) {
            $status = false;
        }

        if ($this->output->isDecorated()) {
            $this->output->write("\x0D");
            $this->output->write("\x1B[2K");
        } else {
            $this->output->writeln('');
        }

        $this->output->writeln(
            $title . ': ' . ($status ? '<info>✔</info>' : '<red>x</red>')
        );

        if (! $status) {
            exit;
        }

        return $status;
    }
}
