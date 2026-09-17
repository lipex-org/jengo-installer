<?php

declare(strict_types=1);

namespace Jengo\Installer\Commands;

use Jengo\Installer\Support\Brand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
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
            ->addArgument('name', InputArgument::OPTIONAL, 'The name or directory of the application')
            // Starter Kit
            ->addOption('kit', null, InputOption::VALUE_OPTIONAL, 'The starter kit to use (default, react, vue, svelte)', 'default')
            // Authentication
            ->addOption('auth', null, InputOption::VALUE_OPTIONAL, 'Authentication provider: "jengo" or "shield"', false)
            ->addOption('shield', null, InputOption::VALUE_NONE, 'Use CodeIgniter Shield for authentication (shortcut for --auth=shield)')
            ->addOption('no-auth', null, InputOption::VALUE_NONE, 'Do not include authentication')
            // Ecosystem Packages
            ->addOption('all', null, InputOption::VALUE_NONE, 'Install all ecosystem packages (api, schema, storage, broadcasting, ai, pdf)')
            ->addOption('api', null, InputOption::VALUE_NONE, 'Install Jengo API Suite (The Vault REST & OpenAPI)')
            ->addOption('schema', null, InputOption::VALUE_NONE, 'Install Jengo Schema builder & type generator')
            ->addOption('storage', null, InputOption::VALUE_NONE, 'Install Jengo Storage filesystem abstraction')
            ->addOption('broadcasting', null, InputOption::VALUE_NONE, 'Install Jengo Broadcasting real-time engine')
            ->addOption('ai', null, InputOption::VALUE_NONE, 'Install Jengo AI SDK and agent engine')
            ->addOption('pdf', null, InputOption::VALUE_NONE, 'Install Jengo PDF generation engine')
            // Testing & Tools
            ->addOption('pest', null, InputOption::VALUE_NONE, 'Install Pest PHP testing framework')
            ->addOption('maizzle', null, InputOption::VALUE_NONE, 'Install Maizzle email template compiler')
            // Frontend & Build
            ->addOption('ts', null, InputOption::VALUE_NONE, 'Install TypeScript support')
            ->addOption('no-ts', null, InputOption::VALUE_NONE, 'Do not install TypeScript support')
            ->addOption('tailwind', null, InputOption::VALUE_NONE, 'Include Tailwind CSS')
            ->addOption('no-tailwind', null, InputOption::VALUE_NONE, 'Do not include Tailwind CSS')
            ->addOption('pm', null, InputOption::VALUE_OPTIONAL, 'Node package manager to use (npm, pnpm, yarn, bun)', 'npm')
            // Database & VCS
            ->addOption('db', null, InputOption::VALUE_OPTIONAL, 'Database driver to configure (sqlite, mysql, postgres)', 'sqlite')
            ->addOption('git', null, InputOption::VALUE_NONE, 'Initialize a Git repository')
            ->addOption('no-git', null, InputOption::VALUE_NONE, 'Do not initialize a Git repository')
            // Overwrite & Dev Mode
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force install even if the directory already exists')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Link local Jengo packages via Composer path repositories')
            ->addOption('dev-path', null, InputOption::VALUE_OPTIONAL, 'Custom root path for local Jengo packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        Brand::renderHeader($output);

        // 1. Resolve Application Name and Path
        $name = $input->getArgument('name');
        if (!$name) {
            if ($input->isInteractive()) {
                $question = new Question('  <fg=cyan;options=bold>?</> <fg=white;options=bold>Where should we create your application?</> <fg=gray>[./jengo-app]</>: ', './jengo-app');
                $name = $helper->ask($input, $output, $question);
            } else {
                $name = './jengo-app';
            }
        }

        $name = trim((string) $name);
        $isCurrentDir = $name === '.' || $name === './';
        $directory = $isCurrentDir ? getcwd() : getcwd() . DIRECTORY_SEPARATOR . ltrim($name, './');
        $appName = $isCurrentDir ? basename((string) getcwd()) : basename($directory);

        // Directory checks
        if (is_dir($directory) && !$isCurrentDir) {
            if ($input->getOption('force')) {
                $this->removeDirectory($directory);
            } else {
                Brand::renderError(
                    $output,
                    'Directory already exists',
                    "The directory '{$name}' already exists. Use --force to replace it."
                );
                return Command::FAILURE;
            }
        }

        if ($isCurrentDir && !$input->getOption('force') && count(scandir($directory)) > 2) {
            Brand::renderError(
                $output,
                'Current directory is not empty',
                'The current directory contains existing files. Use --force to install anyway.'
            );
            return Command::FAILURE;
        }

        // 2. Resolve Starter Kit
        $kit = $input->getOption('kit');
        if ($kit === 'default' && !$input->hasParameterOption('--kit') && $input->isInteractive()) {
            $question = new ChoiceQuestion(
                '  <fg=cyan;options=bold>?</> <fg=white;options=bold>Which starter kit would you like to use?</>',
                [
                    'default' => 'Default Blueprint (CI4 Blade-like Views + Tailwind + Vite)',
                    'react'   => 'React (Inertia.js + Tailwind + TypeScript + Vite)',
                    'vue'     => 'Vue 3 (Inertia.js + Tailwind + TypeScript + Vite)',
                    'svelte'  => 'Svelte (Inertia.js + Tailwind + TypeScript + Vite)',
                ],
                'default'
            );
            $kit = $helper->ask($input, $output, $question);
        }

        // 3. Resolve Node Package Manager
        $pm = $input->getOption('pm');
        if ($pm === 'npm' && !$input->hasParameterOption('--pm') && $input->isInteractive()) {
            $question = new ChoiceQuestion(
                '  <fg=cyan;options=bold>?</> <fg=white;options=bold>Which Node package manager do you prefer?</>',
                [
                    'npm'  => 'npm',
                    'pnpm' => 'pnpm',
                    'yarn' => 'yarn',
                    'bun'  => 'bun',
                ],
                'npm'
            );
            $pm = $helper->ask($input, $output, $question);
        }

        // 4. Resolve TypeScript & Tailwind
        $withTailwind = !$input->getOption('no-tailwind');
        $withTs = $kit !== 'default';
        if ($input->getOption('ts')) {
            $withTs = true;
        } elseif ($input->getOption('no-ts')) {
            $withTs = false;
        }

        // 5. Resolve Authentication (either jengo/auth OR codeigniter4/shield, never both)
        $authOption = $input->getOption('auth');
        $shieldOption = (bool) $input->getOption('shield');
        $noAuthOption = (bool) $input->getOption('no-auth');

        $authDriver = null;

        if ($noAuthOption) {
            $authDriver = 'none';
        } elseif ($shieldOption) {
            $authDriver = 'shield';
        } elseif ($authOption !== false) {
            if ($authOption === null || $authOption === '' || $authOption === 'true' || $authOption === 'jengo') {
                $authDriver = 'jengo';
            } elseif ($authOption === 'shield') {
                $authDriver = 'shield';
            } elseif ($authOption === 'none' || $authOption === 'false') {
                $authDriver = 'none';
            } else {
                $authDriver = 'jengo';
            }
        } elseif ($input->isInteractive()) {
            $question = new ChoiceQuestion(
                '  <fg=cyan;options=bold>?</> <fg=white;options=bold>Which authentication provider would you like to use?</>',
                [
                    'jengo'  => 'Jengo Auth (Unified Auth & Vima RBAC/ABAC Authorization)',
                    'shield' => 'CodeIgniter Shield (Official CI4 Authentication)',
                    'none'   => 'None (No Authentication)',
                ],
                'jengo'
            );
            $authDriver = $helper->ask($input, $output, $question);
        } else {
            $authDriver = 'none';
        }

        // 6. Resolve Ecosystem Packages
        $allPackages = ['api', 'schema', 'storage', 'broadcasting', 'ai', 'pdf'];
        $selectedPackages = [];

        if ($input->getOption('all')) {
            $selectedPackages = $allPackages;
        } else {
            foreach ($allPackages as $pkg) {
                if ($input->getOption($pkg)) {
                    $selectedPackages[] = $pkg;
                }
            }

            if (empty($selectedPackages) && !$this->hasAnyEcosystemOption($input) && $input->isInteractive()) {
                $packageChoices = [
                    'all'          => 'All Ecosystem Packages (api, schema, storage, broadcasting, ai, pdf)',
                    'api'          => 'jengo/api (The Vault REST Suite & OpenAPI)',
                    'schema'       => 'jengo/schema (Fluent Schema & Types)',
                    'storage'      => 'jengo/storage (Flysystem Storage & Image Pipeline)',
                    'broadcasting' => 'jengo/broadcasting (Real-Time SSE & WebSockets)',
                    'ai'           => 'jengo/ai (Multi-Provider AI SDK & Agent Engine)',
                    'pdf'          => 'jengo/pdf (Dual-Driver PDF Reporting Engine)',
                    'none'         => 'None (Lean Core)',
                ];

                $question = new ChoiceQuestion(
                    '  <fg=cyan;options=bold>?</> <fg=white;options=bold>Select ecosystem packages to include (comma-separated)</> <fg=gray>[none]</>:',
                    $packageChoices,
                    'none'
                );
                $question->setMultiselect(true);
                $chosen = (array) $helper->ask($input, $output, $question);

                if (in_array('all', $chosen, true)) {
                    $selectedPackages = $allPackages;
                } elseif (!in_array('none', $chosen, true)) {
                    $selectedPackages = array_values(array_intersect($chosen, $allPackages));
                }
            }
        }

        // 7. Resolve Testing Suite
        $withPest = (bool) $input->getOption('pest');
        if (!$withPest && !$input->hasParameterOption('--pest') && $input->isInteractive()) {
            $question = new ConfirmationQuestion('  <fg=cyan;options=bold>?</> <fg=white;options=bold>Install Pest PHP testing framework?</> <fg=gray>[no]</>: ', false);
            $withPest = $helper->ask($input, $output, $question);
        }

        // 8. Resolve Maizzle
        $withMaizzle = (bool) $input->getOption('maizzle');

        // 9. Resolve Database
        $dbDriver = $input->getOption('db') ?: 'sqlite';

        // 10. Resolve Git
        $withGit = true;
        if ($input->getOption('no-git')) {
            $withGit = false;
        } elseif ($input->getOption('git')) {
            $withGit = true;
        } elseif ($input->isInteractive()) {
            $question = new ConfirmationQuestion('  <fg=cyan;options=bold>?</> <fg=white;options=bold>Initialize a Git repository?</> <fg=gray>[yes]</>: ', true);
            $withGit = $helper->ask($input, $output, $question);
        }

        // 11. Resolve Dev Mode
        $isDev = (bool) $input->getOption('dev') || $input->hasParameterOption('--dev-path');
        $devPath = $this->resolveDevPath($input);

        // Display Configuration Summary Card
        $kitTitles = [
            'default' => 'Default Blueprint (PHP + Tailwind + Vite)',
            'react'   => 'React 19 (Inertia.js)',
            'vue'     => 'Vue 3 (Inertia.js)',
            'svelte'  => 'Svelte 5 (Inertia.js)',
        ];

        $authLabels = [
            'jengo'  => 'Jengo Auth (jengo/auth + Vima)',
            'shield' => 'CodeIgniter Shield (codeigniter4/shield)',
            'none'   => 'None',
        ];

        $summaryConfig = [
            'name'      => $appName,
            'directory' => $directory,
            'kit'       => $kitTitles[$kit] ?? $kit,
            'tooling'   => sprintf('%s, Vite, %s%s', $pm, $withTailwind ? 'Tailwind CSS' : 'No Tailwind', $withTs ? ', TypeScript' : ''),
            'auth'      => $authLabels[$authDriver] ?? 'None',
            'packages'  => !empty($selectedPackages) ? implode(', ', $selectedPackages) : 'None (Lean Core)',
            'testing'   => $withPest ? 'Pest PHP' : 'PHPUnit',
            'db'        => strtoupper((string) $dbDriver),
            'git'       => $withGit,
            'dev'       => $isDev,
        ];

        Brand::renderSummary($output, $summaryConfig);

        // Calculate Plan Steps
        $totalSteps = 3; // 1: CI4 skeleton, 2: Core, 3: Starter kit & frontend
        if ($authDriver !== 'none') {
            $totalSteps++;
        }
        if (!empty($selectedPackages)) {
            $totalSteps++;
        }
        $totalSteps++; // Dev tooling & database
        if ($withGit) {
            $totalSteps++;
        }

        $currentStep = 1;

        // Step 1: Initializing CodeIgniter 4
        if (!$isCurrentDir) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            chdir($directory);
        }

        $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);
        if (!$this->runProcess(
            ['composer', 'create-project', 'codeigniter4/appstarter', '.', '--no-interaction', '--prefer-dist'],
            $output,
            'Initializing CodeIgniter 4 framework',
            $stepLabel
        )) {
            return Command::FAILURE;
        }

        // Configure Dev Repositories if dev mode is enabled
        if ($isDev && $devPath !== null) {
            $this->configureDevRepositories($directory, $devPath, $output);
        }

        // Step 2: Installing Jengo Base & Core Modules
        $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);
        if (!$this->runProcess(
            ['composer', 'require', 'jengo/base', '--no-interaction'],
            $output,
            'Installing jengo/base core engine',
            $stepLabel
        )) {
            return Command::FAILURE;
        }

        $this->runProcess(
            ['php', 'spark', 'jengo:setup', 'core', '--yes'],
            $output,
            'Configuring Jengo core helpers & providers',
            $stepLabel
        );

        // Step 3: Frontend & Starter Kit Scaffolding
        $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);
        $tailwindFlag = $withTailwind ? ['--tailwind', 'y'] : ['--tailwind', 'n'];

        if ($kit !== 'default') {
            if (!$this->runProcess(
                ['composer', 'require', 'jengo/inertia', '--no-interaction'],
                $output,
                'Installing jengo/inertia adapter',
                $stepLabel
            )) {
                return Command::FAILURE;
            }

            $this->runProcess(
                ['php', 'spark', 'jengo:install', 'vite', ...$tailwindFlag, '--pm', $pm, '--yes'],
                $output,
                'Configuring Vite build system',
                $stepLabel
            );

            $inertiaAuthFlag = $authDriver !== 'none' ? ['--auth', 'y'] : ['--auth', 'n'];
            $this->runProcess(
                ['php', 'spark', 'jengo:install', 'inertia', '--framework', $kit, '--yes', ...$inertiaAuthFlag],
                $output,
                "Scaffolding {$kit} client application",
                $stepLabel
            );

            if ($withTs) {
                $this->runProcess(
                    ['php', 'spark', 'jengo:install', 'typescript', '--pm', $pm, '--yes'],
                    $output,
                    'Configuring TypeScript compiler and types',
                    $stepLabel
                );
            }
        } else {
            $this->runProcess(
                ['php', 'spark', 'jengo:install', 'blueprint', '--yes'],
                $output,
                'Setting up Jengo Blueprint UI',
                $stepLabel
            );

            $this->runProcess(
                ['php', 'spark', 'jengo:install', 'vite', ...$tailwindFlag, '--pm', $pm, '--yes'],
                $output,
                'Configuring Vite build system',
                $stepLabel
            );

            if ($withTs) {
                $this->runProcess(
                    ['php', 'spark', 'jengo:install', 'typescript', '--pm', $pm, '--yes'],
                    $output,
                    'Configuring TypeScript support',
                    $stepLabel
                );
            }
        }

        // Step 4: Authentication & Authorization (either jengo/auth OR codeigniter4/shield, never both)
        if ($authDriver === 'jengo') {
            $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);
            if (!$this->runProcess(
                ['composer', 'require', 'jengo/auth', '--no-interaction'],
                $output,
                'Installing jengo/auth package',
                $stepLabel
            )) {
                return Command::FAILURE;
            }

            $this->runProcess(
                ['php', 'spark', 'jengo:auth', 'setup', '--overwrite'],
                $output,
                'Configuring Jengo Auth & Vima authorization policies',
                $stepLabel
            );
        } elseif ($authDriver === 'shield') {
            $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);
            if (!$this->runProcess(
                ['composer', 'require', 'codeigniter4/shield', '--no-interaction'],
                $output,
                'Installing CodeIgniter Shield package',
                $stepLabel
            )) {
                return Command::FAILURE;
            }

            $authInertiaFlag = $kit !== 'default' ? ['--inertia'] : [];
            $this->runProcess(
                ['php', 'spark', 'jengo:setup', 'auth', ...$authInertiaFlag],
                $output,
                'Configuring CodeIgniter Shield authentication & routes',
                $stepLabel
            );
        }

        // Step 5: Ecosystem Packages
        if (!empty($selectedPackages)) {
            $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);
            $composerPackages = [];

            foreach ($selectedPackages as $pkg) {
                $composerPackages[] = "jengo/{$pkg}";
            }

            if (!$this->runProcess(
                ['composer', 'require', ...$composerPackages, '--no-interaction'],
                $output,
                'Installing ecosystem packages: ' . implode(', ', $composerPackages),
                $stepLabel
            )) {
                return Command::FAILURE;
            }

            // Run respective setup/install commands
            foreach ($selectedPackages as $pkg) {
                switch ($pkg) {
                    case 'api':
                        $this->runProcess(
                            ['php', 'spark', 'jengo:api', 'setup'],
                            $output,
                            'Publishing The Vault API configurations',
                            $stepLabel
                        );
                        break;

                    case 'schema':
                        $this->runProcess(
                            ['php', 'spark', 'jengo:schema', 'setup'],
                            $output,
                            'Publishing Jengo Schema configurations',
                            $stepLabel
                        );
                        break;

                    case 'storage':
                        $this->runProcess(
                            ['php', 'spark', 'jengo:install', 'storage', '--yes'],
                            $output,
                            'Configuring Jengo Storage & assets',
                            $stepLabel
                        );
                        $this->runProcess(
                            ['php', 'spark', 'storage:link'],
                            $output,
                            'Creating public storage symlink',
                            $stepLabel
                        );
                        break;

                    case 'broadcasting':
                        $this->runProcess(
                            ['php', 'spark', 'jengo:install', 'broadcasting', '--yes'],
                            $output,
                            'Configuring Jengo Broadcasting real-time engine',
                            $stepLabel
                        );
                        break;

                    case 'ai':
                        $this->runProcess(
                            ['php', 'spark', 'jengo:install', 'ai', '--yes'],
                            $output,
                            'Publishing Jengo AI SDK configurations',
                            $stepLabel
                        );
                        break;

                    case 'pdf':
                        $this->runProcess(
                            ['php', 'spark', 'jengo:install', 'pdf', '--yes'],
                            $output,
                            'Configuring Jengo PDF generation engine',
                            $stepLabel
                        );
                        break;
                }
            }
        }

        // Step 6: Tooling, Testing & Database
        $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);

        if ($withPest) {
            $this->runProcess(
                ['php', 'spark', 'jengo:install', 'pest', '--yes'],
                $output,
                'Configuring Pest PHP test framework',
                $stepLabel
            );
        }

        if ($withMaizzle) {
            $this->runProcess(
                ['php', 'spark', 'jengo:install', 'maizzle', '--yes'],
                $output,
                'Setting up Maizzle email template compiler',
                $stepLabel
            );
        }

        $this->runProcess(
            ['php', 'spark', 'jengo:install', 'dev', '--yes'],
            $output,
            'Finalizing development environment scripts',
            $stepLabel
        );

        $this->runProcess(
            ['php', 'spark', 'jengo:install', 'db', '--yes'],
            $output,
            'Configuring SQLite database & initial migrations',
            $stepLabel
        );

        // Step 7: Git Repository Initialization
        if ($withGit) {
            $stepLabel = sprintf('[%d/%d]', $currentStep++, $totalSteps);
            $this->initializeGit($directory, $output, $stepLabel);
        }

        Brand::renderSuccess($output, $appName, $directory, $isCurrentDir);

        return Command::SUCCESS;
    }

    private function runProcess(
        array $command,
        OutputInterface $output,
        string $loadingMessage,
        string $stepLabel = ''
    ): bool {
        $process = new Process($command);
        $process->setTimeout(null);

        $section = $output instanceof ConsoleOutputInterface
            ? $output->section()
            : $output;

        $frames = ['[-]', '[\\]', '[|]', '[/]'];
        $i = 0;

        $process->start();

        $prefix = $stepLabel !== '' ? "<fg=white;options=bold>{$stepLabel}</> " : '';

        while ($process->isRunning()) {
            $frame = $frames[$i % count($frames)];
            $section->overwrite(sprintf('  <fg=cyan;options=bold>%s</> %s%s...', $frame, $prefix, $loadingMessage));
            $i++;
            usleep(80000); // 80ms
        }

        if ($process->isSuccessful()) {
            $section->overwrite(sprintf('  <fg=green;options=bold>[OK]</> %s%s', $prefix, $loadingMessage));
            return true;
        }

        $section->overwrite(sprintf('  <fg=red;options=bold>[FAIL]</> %s%s', $prefix, $loadingMessage));

        // On failure, display error output
        $output->writeln('');
        $output->writeln('  <bg=red;fg=white;options=bold> ERROR OUTPUT </>');
        if ($errorOutput = trim($process->getErrorOutput())) {
            $output->writeln('  ' . str_replace("\n", "\n  ", $errorOutput));
        }
        if ($stdOutput = trim($process->getOutput())) {
            $output->writeln('  ' . str_replace("\n", "\n  ", $stdOutput));
        }
        $output->writeln('');

        return false;
    }

    private function initializeGit(string $directory, OutputInterface $output, string $stepLabel): void
    {
        if (is_dir($directory . DIRECTORY_SEPARATOR . '.git')) {
            return;
        }

        $this->runProcess(['git', 'init', '-q'], $output, 'Initializing Git repository', $stepLabel);
        $this->runProcess(['git', 'add', '.'], $output, 'Staging project files', $stepLabel);
        $this->runProcess(['git', 'commit', '-q', '-m', 'chore: initial Jengo scaffold'], $output, 'Creating initial commit', $stepLabel);
    }

    private function resolveDevPath(InputInterface $input): ?string
    {
        $customPath = $input->getOption('dev-path');
        if ($customPath && is_dir($customPath)) {
            return realpath($customPath);
        }

        $envPath = getenv('JENGO_DEV_PATH');
        if ($envPath && is_dir($envPath)) {
            return realpath($envPath);
        }

        // Check if running from inside local packages monorepo
        $parent = dirname(__DIR__, 3);
        if (is_dir($parent . DIRECTORY_SEPARATOR . 'base') && is_dir($parent . DIRECTORY_SEPARATOR . 'auth')) {
            return realpath($parent);
        }

        return null;
    }

    private function configureDevRepositories(string $targetDirectory, string $devPath, OutputInterface $output): void
    {
        $composerJsonPath = $targetDirectory . DIRECTORY_SEPARATOR . 'composer.json';
        if (!file_exists($composerJsonPath)) {
            return;
        }

        $composerData = json_decode((string) file_get_contents($composerJsonPath), true);
        if (!is_array($composerData)) {
            return;
        }

        $composerData['minimum-stability'] = 'dev';
        $composerData['prefer-stable'] = true;

        $repositories = $composerData['repositories'] ?? [];

        // Add path to Jengo packages
        $repositories[] = [
            'type' => 'path',
            'url' => rtrim($devPath, DIRECTORY_SEPARATOR) . '/*',
            'options' => [
                'symlink' => true,
            ],
        ];

        // Add path to Vima packages
        $vimaPath = rtrim($devPath, DIRECTORY_SEPARATOR) . '/deps/vima/*';
        if (is_dir(rtrim($devPath, DIRECTORY_SEPARATOR) . '/deps/vima')) {
            $repositories[] = [
                'type' => 'path',
                'url' => $vimaPath,
                'options' => [
                    'symlink' => true,
                ],
            ];
        }

        $composerData['repositories'] = $repositories;

        file_put_contents(
            $composerJsonPath,
            json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $output->writeln('  <fg=cyan;options=bold>[INFO]</> Linked local development packages from: <fg=white>' . $devPath . '</>');
    }

    private function hasAnyEcosystemOption(InputInterface $input): bool
    {
        foreach (['api', 'schema', 'storage', 'broadcasting', 'ai', 'pdf'] as $opt) {
            if ($input->hasParameterOption('--' . $opt)) {
                return true;
            }
        }
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
