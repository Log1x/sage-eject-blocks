<?php

namespace Log1x\EjectBlocks\Console\Commands;

use Symfony\Component\Process\Process;
use Roots\Acorn\Console\Commands\Command as CommandBase;

class Command extends CommandBase
{
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
            $status = $task() !== false;
        } catch (\Exception $e) {
            $this->clearLine()->line(
                $title . ': ' . ($status ? '<info>✔</info>' : '<red>x</red>')
            );

            throw $e;
        }

        $this->clearLine()->line(
            $title . ': ' . ($status ? '<info>✔</info>' : '<red>x</red>')
        );
    }

    /**
     * Clear the current line in console.
     *
     * @return mixed
     */
    public function clearLine()
    {
        if (! $this->output->isDecorated()) {
            $this->output->writeln('');

            return $this;
        }

        $this->output->write("\x0D");
        $this->output->write("\x1B[2K");

        return $this;
    }
}
