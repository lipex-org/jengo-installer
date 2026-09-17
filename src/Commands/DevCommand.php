<?php

declare(strict_types=1);

namespace Jengo\Installer\Commands;

use Jengo\Installer\Support\Brand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DevCommand extends Command
{
    protected static $defaultName = 'dev';

    protected function configure(): void
    {
        $this
            ->setName('dev')
            ->setDescription('Start the Jengo development environment server')
            ->ignoreValidationErrors(); // Allow passing options down to php spark jengo:dev
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = getcwd();
        $sparkPath = $cwd . DIRECTORY_SEPARATOR . 'spark';

        if (!file_exists($sparkPath)) {
            Brand::renderError(
                $output,
                'Not a Jengo Application',
                'Could not locate the "spark" executable in the current directory. Please run "jengo dev" inside a Jengo application directory.'
            );
            return Command::FAILURE;
        }

        // Forward raw arguments following 'dev' to spark jengo:dev
        $argv = $_SERVER['argv'] ?? [];
        $forwardArgs = [];
        $afterDev = false;

        foreach ($argv as $arg) {
            if ($afterDev) {
                $forwardArgs[] = $arg;
            } elseif ($arg === 'dev') {
                $afterDev = true;
            }
        }

        $command = array_merge([PHP_BINARY, 'spark', 'jengo:dev'], $forwardArgs);

        $process = new Process($command, $cwd);
        $process->setTimeout(null);

        if (Process::isTtySupported()) {
            try {
                $process->setTty(true);
            } catch (\Throwable $e) {
                // Fall back to standard pipe if TTY is not available
            }
        }

        return $process->run(function ($type, $buffer) {
            echo $buffer;
        });
    }
}
