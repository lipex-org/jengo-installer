# Jengo Installer

The interactive CLI installer for scaffolding fresh Jengo-powered CodeIgniter 4 applications.

Documentation: https://lipex-org.github.io/jengophp.com/guide/installer

## Installation

```bash
composer global require jengo/installer
```

Ensure your global Composer `bin` directory is in your system's `PATH`.

## Quick Start

```bash
# Interactive scaffolding wizard
jengo new my-app

# Non-interactive CLI with starter kit and authentication
jengo new my-app --kit=react --auth --pm=pnpm

# Full-stack powerhouse with all ecosystem packages
jengo new my-app --kit=vue --auth --all --pest
```

## Available Options

### Starter Kits
- `--kit=<name>`: `default` (Blade-like PHP Views + Tailwind), `react`, `vue`, or `svelte` (Inertia.js SPAs).

### Authentication & Authorization
- `--auth`: Include authentication and authorization (`jengo/auth` + CodeIgniter Shield).
- `--no-auth`: Skip authentication.

### Modular Ecosystem Packages
- `--all`: Install all ecosystem packages (`api`, `schema`, `storage`, `broadcasting`, `ai`, `pdf`).
- `--api`: Install Jengo API Suite (`jengo/api` - The Vault REST & OpenAPI).
- `--schema`: Install Jengo Schema builder & TypeScript generator (`jengo/schema`).
- `--storage`: Install Jengo Storage filesystem abstraction & image pipeline (`jengo/storage`).
- `--broadcasting`: Install Jengo Broadcasting real-time engine (`jengo/broadcasting`).
- `--ai`: Install Jengo AI SDK and agent engine (`jengo/ai`).
- `--pdf`: Install Jengo PDF generation engine (`jengo/pdf`).

### Tooling & Testing
- `--pest`: Install Pest PHP testing framework instead of default PHPUnit.
- `--maizzle`: Install Maizzle HTML email template compiler.
- `--ts`: Include TypeScript compiler configuration.
- `--no-ts`: Skip TypeScript configuration.
- `--no-tailwind`: Skip Tailwind CSS installation.
- `--pm=<manager>`: Node package manager (`npm`, `pnpm`, `yarn`, `bun`).
- `--db=<driver>`: Database driver (`sqlite`, `mysql`, `postgres`). Default: `sqlite`.
- `--git` / `--no-git`: Initialize a Git repository with initial commit.

### Development & Overwrite
- `--force` (`-f`): Overwrite target directory if it already exists.
- `--dev`: Link local Jengo packages via Composer path repositories for monorepo development.
- `--dev-path=<path>`: Custom root directory for local packages.

## Documentation

For guides on interactive mode, CI/CD automation flags, and starter kit options, visit https://lipex-org.github.io/jengophp.com/guide/installer.

## License

Released under the MIT License.
