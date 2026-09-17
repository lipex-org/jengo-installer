<?php

declare(strict_types=1);

namespace Jengo\Installer\Support;

use Symfony\Component\Console\Output\OutputInterface;

class Brand
{
    public const LOGO = [
        "     ___ _____ _   _  ____  ___  ",
        "    |_  |  ___| \ | |/ ___|/ _ \ ",
        "      | | |__ |  \| | |  _| | | |",
        "  /\_/ /|  __|| |\  | |_| | |_| |",
        "  \___/ |_____|_| \_|\____|\___/ ",
    ];

    public static function renderHeader(OutputInterface $output, string $version = '1.0.0'): void
    {
        $output->writeln('');
        foreach (self::LOGO as $line) {
            $output->writeln('  <fg=cyan;options=bold>' . $line . '</>');
        }
        $output->writeln('');

        $infoLines = [
            '<fg=white;options=bold>JENGO CLI INSTALLER</> <fg=cyan>v' . $version . '</>',
            '<fg=gray>The Modern Full-Stack Ecosystem for CodeIgniter 4</>',
            '',
            '<fg=gray>Vite · TypeScript · Inertia · AI · Real-Time · Storage · Auth</>',
        ];

        self::renderBox($output, $infoLines, '', 66, 'gray');
        $output->writeln('');
    }

    public static function renderBox(
        OutputInterface $output,
        array $lines,
        string $title = '',
        int $minWidth = 66,
        string $borderColor = 'gray'
    ): void {
        $cleanTitle = self::stripFormatting($title);
        $titleLen = mb_strwidth($cleanTitle);

        $maxLineLen = 0;
        foreach ($lines as $line) {
            $maxLineLen = max($maxLineLen, mb_strwidth(self::stripFormatting($line)));
        }
        if ($titleLen > 0) {
            $maxLineLen = max($maxLineLen, $titleLen + 4);
        }

        $width = max($minWidth, $maxLineLen + 4);

        if ($titleLen > 0) {
            $rightDashes = max(0, $width - 3 - $titleLen);
            $output->writeln("  <fg={$borderColor}>┌─</> <fg=white;options=bold>{$title}</> <fg={$borderColor}>" . str_repeat('─', $rightDashes) . "┐</>");
        } else {
            $output->writeln("  <fg={$borderColor}>┌" . str_repeat('─', $width) . "┐</>");
        }

        foreach ($lines as $line) {
            $cleanLen = mb_strwidth(self::stripFormatting($line));
            $pad = max(0, $width - 2 - $cleanLen);
            $output->writeln("  <fg={$borderColor}>│</> " . $line . str_repeat(' ', $pad) . " <fg={$borderColor}>│</>");
        }

        $output->writeln("  <fg={$borderColor}>└" . str_repeat('─', $width) . "┘</>");
    }

    public static function renderSummary(OutputInterface $output, array $config): void
    {
        $lines = [
            '<fg=gray>Project Name      :</> <fg=cyan>' . ($config['name'] ?? 'jengo-app') . '</>',
            '<fg=gray>Target Directory  :</> <fg=cyan>' . ($config['directory'] ?? '') . '</>',
            '<fg=gray>Starter Kit       :</> <fg=cyan>' . ($config['kit'] ?? 'Default Blueprint') . '</>',
            '<fg=gray>Tooling & Manager :</> <fg=cyan>' . ($config['tooling'] ?? 'npm, Vite, Tailwind') . '</>',
            '<fg=gray>Authentication    :</> <fg=cyan>' . ($config['auth'] ?? 'None') . '</>',
            '<fg=gray>Ecosystem Packages:</> <fg=cyan>' . ($config['packages'] ?? 'None (Lean Core)') . '</>',
            '<fg=gray>Testing Framework :</> <fg=cyan>' . ($config['testing'] ?? 'PHPUnit') . '</>',
            '<fg=gray>Database Driver   :</> <fg=cyan>' . ($config['db'] ?? 'SQLite') . '</>',
            '<fg=gray>Git Repository    :</> <fg=cyan>' . ($config['git'] ? 'Initialized' : 'Skipped') . '</>',
        ];

        if (!empty($config['dev'])) {
            $lines[] = '<fg=gray>Dev Mode          :</> <fg=yellow>Active (Local Path Symlinks)</>';
        }

        $output->writeln('');
        self::renderBox($output, $lines, 'Project Configuration', 66, 'cyan');
        $output->writeln('');
    }

    public static function renderSuccess(
        OutputInterface $output,
        string $appName,
        string $directory,
        bool $isCurrentDir
    ): void {
        $output->writeln('');
        $successLines = [
            '<fg=green;options=bold>[OK]</> <fg=white;options=bold>Application scaffolded successfully!</>',
            '',
            '<fg=gray>Your modern CodeIgniter 4 application is ready to launch.</>',
        ];

        self::renderBox($output, $successLines, '', 66, 'green');
        $output->writeln('');

        $output->writeln('  <fg=white;options=bold>Next Steps:</>');
        if (!$isCurrentDir) {
            $output->writeln(sprintf('    1. <fg=cyan>cd %s</>', $appName));
            $output->writeln('    2. <fg=cyan>composer dev</>');
        } else {
            $output->writeln('    1. <fg=cyan>composer dev</>');
        }

        $output->writeln('');
        $output->writeln('  <fg=white;options=bold>Documentation:</> <fg=cyan>https://lipex-org.github.io/jengophp.com</>');
        $output->writeln('  <fg=gray>Happy building!</>');
        $output->writeln('');
    }

    public static function renderError(OutputInterface $output, string $title, ?string $detail = null): void
    {
        $output->writeln('');
        $lines = [
            '<fg=red;options=bold>[FAIL]</> <fg=white;options=bold>' . $title . '</>',
        ];

        if ($detail !== null) {
            $lines[] = '';
            $lines[] = '<fg=gray>' . $detail . '</>';
        }

        self::renderBox($output, $lines, '', 66, 'red');
        $output->writeln('');
    }

    public static function stripFormatting(string $text): string
    {
        // Strip Symfony console tags like <fg=cyan;options=bold> and </>
        $clean = preg_replace('/<[^>]*>/', '', $text);
        // Strip ANSI color escape sequences
        $clean = preg_replace('/\033\[[0-9;]*m/', '', $clean ?? '');

        return $clean ?? '';
    }
}
