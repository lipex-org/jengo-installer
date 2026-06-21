<?php

namespace Jengo\Installer\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class NewCommand extends Command
{
    protected static $defaultName = 'new';

    protected function configure(): void
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Jengo CodeIgniter 4 application')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the application')
            ->addOption('kit', null, InputOption::VALUE_OPTIONAL, 'The starter kit to use (react, vue, svelte)', 'default')
            ->addOption('auth', null, InputOption::VALUE_NONE, 'Install CodeIgniter Shield with Jengo styling')
            ->addOption('api', null, InputOption::VALUE_NONE, 'Install Jengo API suite (The Vault)')
            ->addOption('ts', null, InputOption::VALUE_NONE, 'Install TypeScript support')
            ->addOption('no-tailwind', null, InputOption::VALUE_NONE, 'Do not include Tailwind CSS')
            ->addOption('pm', null, InputOption::VALUE_OPTIONAL, 'The package manager to use (pnpm, npm, yarn)', 'npm')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force install even if the directory already exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $this->renderHeader($output);

        if (!$name) {
            $name = $io->ask('  <fg=cyan;options=bold>Where should we create your application?</>', './jengo-app');
        }

        $isCurrentDir = $name === '.';
        $directory = $isCurrentDir ? getcwd() : getcwd() . DIRECTORY_SEPARATOR . $name;
        $appName = $isCurrentDir ? basename($directory) : $name;

        if (is_dir($directory) && !$isCurrentDir) {
            if ($input->getOption('force')) {
                $this->removeDirectory($directory);
            } else {
                $output->writeln("\n  <bg=red;fg=white;options=bold> ERROR </> The directory <comment>{$name}</comment> already exists.\n");
                return Command::FAILURE;
            }
        }

        if ($isCurrentDir && !$input->getOption('force') && count(scandir($directory)) > 2) {
            $output->writeln("\n  <bg=red;fg=white;options=bold> ERROR </> The current directory is not empty. Use <comment>--force</comment> to install anyway.\n");
            return Command::FAILURE;
        }

        // --- Interactive Prompts ---

        $kit = $input->getOption('kit');
        if ($kit === 'default' && !$input->hasParameterOption('--kit')) {
            $kit = $io->choice('  <fg=cyan;options=bold>Which starter kit would you like to use?</>', [
                'default' => 'Default (Basic CI4 + Vite + Jengo Blueprint)',
                'react' => 'React (Inertia.js)',
                'vue' => 'Vue (Inertia.js)',
                'svelte' => 'Svelte (Inertia.js)',
            ], 'default');
        }

        $pm = $input->getOption('pm');
        if ($pm === 'npm' && !$input->hasParameterOption('--pm')) {
            $pm = $io->choice('  <fg=cyan;options=bold>Which package manager do you prefer?</>', [
                'npm'  => 'npm',
                'pnpm' => 'pnpm',
                'yarn' => 'yarn',
            ], 'npm');
        }

        $isInteractive = !$input->hasParameterOption('--api') && 
                         !$input->hasParameterOption('--auth') && 
                         !$input->hasParameterOption('--ts') && 
                         !$input->hasParameterOption('--no-tailwind');

        if ($isInteractive) {
            $defaultFeatures = ['tailwind'];
            if ($kit !== 'default') {
                $defaultFeatures[] = 'ts';
            }

            $selectedFeatures = $io->choice('  <fg=cyan;options=bold>Which features would you like to include?</>', [
                'auth'     => 'Authentication (The Gatekeeper)',
                'api'      => 'API Suite (The Vault) + Auth',
                'ts'       => 'TypeScript Support',
                'tailwind' => 'Tailwind CSS',
            ], implode(',', $defaultFeatures), true);

            $withApi      = in_array('api', $selectedFeatures);
            $withAuth     = $withApi || in_array('auth', $selectedFeatures);
            $withTs       = in_array('ts', $selectedFeatures);
            $withTailwind = in_array('tailwind', $selectedFeatures);
        } else {
            $withApi      = $input->getOption('api');
            $withAuth     = $withApi || $input->getOption('auth');
            $withTs       = $input->getOption('ts') ?: ($kit !== 'default' && !$input->hasParameterOption('--ts'));
            $withTailwind = !$input->getOption('no-tailwind');
        }

        // --- End of Prompts ---

        $output->writeln("\n  <fg=white;options=bold>Preparing your new Jengo application...</>");
        $output->writeln("  " . str_repeat('─', 50));
        $output->writeln(sprintf('  <fg=gray>Project:</>    <fg=cyan>%s</>', $appName));
        $output->writeln(sprintf('  <fg=gray>Directory:</>  <fg=cyan>%s</>', $directory));
        $output->writeln(sprintf('  <fg=gray>Kit:</>        <fg=cyan>%s</>', $kit));
        $output->writeln(sprintf(
            '  <fg=gray>Tools:</>      <fg=cyan>%s, %s%s</>',
            $pm,
            $withTailwind ? 'Tailwind' : 'No Tailwind',
            $withAuth ? ', Auth' : ''
        ));
        $output->writeln("  " . str_repeat('─', 50) . "\n");

        // 1. Initializing CodeIgniter 4
        if (!$isCurrentDir) {
            mkdir($directory, 0777, true);
            chdir($directory);
        }

        if (!$this->runProcess(['composer', 'create-project', 'codeigniter4/appstarter', '.'], $output, 'Initializing CodeIgniter 4 framework')) {
            return Command::FAILURE;
        }

        // 2. Installing Jengo Base
        if (!$this->runProcess(['composer', 'require', 'jengo/base'], $output, 'Installing jengo/base core package')) {
            return Command::FAILURE;
        }
        // 3. Core Setup
        if (!$this->runProcess(['php', 'spark', 'jengo:setup', 'core', '--yes'], $output, 'Configuring Jengo core helpers')) {
            $io->warning('Core setup failed.');
        }

        // 4. Optional Integrations
        if ($withAuth) {
            if (!$this->runProcess(['composer', 'require', 'codeigniter4/shield'], $output, 'Installing CodeIgniter Shield for authentication')) {
                return Command::FAILURE;
            }

            $this->runProcess(['php', 'spark', 'jengo:setup', 'auth', $kit !== 'default' ? '--inertia' : ''], $output, 'Configuring Shield with Jengo styling and routes');
        }

        if ($withApi) {
            $this->runProcess(['php', 'spark', 'jengo:setup', 'api', '--yes'], $output, 'Establishing The Vault (API Suite)');
        }

        if ($withTs) {
            $this->runProcess(['php', 'spark', 'jengo:install', 'typescript', '--pm', $pm, '--yes'], $output, 'Configuring TypeScript support');
        }

        // 6. Handle Starter Kits
        if ($kit !== 'default') {
            if (!$this->runProcess(['composer', 'require', 'jengo/inertia'], $output, 'Installing jengo/inertia adapter')) {
                return Command::FAILURE;
            }

            $tailwindFlag = $withTailwind ? ['--tailwind', 'y'] : ['--tailwind', 'n'];
            if (!$this->runProcess(['php', 'spark', 'jengo:install', 'vite', ...$tailwindFlag, "--pm", $pm, '--yes'], $output, 'Configuring Vite build system')) {
                return Command::FAILURE;
            }

            if (!$this->runProcess(['php', 'spark', 'jengo:install', 'inertia', "--framework", $kit, '--yes', '--client-dir', 'resources/js'], $output, "Scaffolding {$kit} client assets")) {
                return Command::FAILURE;
            }
        } else {
            if (!$this->runProcess(['php', 'spark', 'jengo:install', 'blueprint', '--yes'], $output, 'Setting up Jengo Blueprint UI')) {
                $io->warning('Blueprint installation failed.');
            }

            $tailwindFlag = $withTailwind ? ['--tailwind', 'y'] : ['--tailwind', 'n'];
            if (!$this->runProcess(['php', 'spark', 'jengo:install', 'vite', ...$tailwindFlag, "--pm", $pm, '--yes'], $output, 'Configuring Vite build system')) {
                $io->warning('Vite installation failed.');
            }
        }

        // 7. Development Environment Setup
        if (
            !$this->runProcess(['php', 'spark', 'jengo:install', 'dev', '--yes'], $output, 'Finalizing development environment')
        ) {
            $io->warning('Dev setup failed.');
        }

        // 8. Database Setup
        if (!$this->runProcess(['php', 'spark', 'jengo:install', 'db', '--yes'], $output, 'Configuring SQLite database and running migrations')) {
            $io->warning('Database configuration failed.');
        }

        $output->writeln("\n  " . str_repeat('─', 50));
        $output->writeln("  <fg=green;options=bold>SUCCESS!</> Your Jengo application is ready.");
        $output->writeln("  " . str_repeat('─', 50));

        $output->writeln("\n  <fg=white;options=bold>Next Steps:</>");
        if (!$isCurrentDir) {
            $output->writeln(sprintf('  1. <fg=cyan>cd %s</>', $name));
            $output->writeln('  2. <fg=cyan>composer dev</>');
        } else {
            $output->writeln('  1. <fg=cyan>composer dev</>');
        }

        $output->writeln("\n  <fg=gray>Thank you for choosing Jengo. Happy building!</>\n");

        return Command::SUCCESS;
    }

    private function renderHeader(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('  <fg=cyan;options=bold>      _                      </>');
        $output->writeln('  <fg=cyan;options=bold>     | | ___ _ __   __ _  ___  </>');
        $output->writeln('  <fg=cyan;options=bold>  _  | |/ _ \ \'_ \ / _` |/ _ \ </>');
        $output->writeln('  <fg=cyan;options=bold> | |_| |  __/ | | | (_| | (_) |</>');
        $output->writeln('  <fg=cyan;options=bold>  \___/ \___|_| |_|\__, |\___/ </>');
        $output->writeln('  <fg=cyan;options=bold>                   |___/       </>');
        $output->writeln('  <fg=gray>  The CodeIgniter 4 Powerhouse</>');
        $output->writeln('');
    }

    private function runProcess(array $command, OutputInterface $output, string $loadingMessage): bool
    {
        $process = new Process($command);
        $process->setTimeout(null);

        // We use a section to manage the specific line for the loader
        $section = $output instanceof \Symfony\Component\Console\Output\ConsoleOutputInterface
            ? $output->section()
            : $output;

        $spinner = ['⠏', '⠹', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $i = 0;

        $process->start();

        while ($process->isRunning()) {
            $frame = $spinner[$i % count($spinner)];
            $section->overwrite(sprintf('  <info>%s</info> %s...', $frame, $loadingMessage));
            $i++;
            usleep(100000); // 100ms
        }

        if ($process->isSuccessful()) {
            $section->overwrite(sprintf('  <info>✔</info> %s <comment>(Done)</comment>', $loadingMessage));
            return true;
        }

        $section->overwrite(sprintf('  <error>✘</error> %s <error>(Failed)</error>', $loadingMessage));

        // On failure, show the error output to help the user
        $output->writeln('<bg=red;fg=white;options=bold> ERROR OUTPUT: </>');
        $output->writeln($process->getErrorOutput());
        $output->writeln($process->getOutput());

        return false;
    }

    private function removeDirectory(string $directory): void
    {
        if (PHP_OS === 'WINNT') {
            exec("rd /s /q \"$directory\"");
        } else {
            exec("rm -rf \"$directory\"");
        }
    }
}
