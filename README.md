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

# Non-interactive CLI with React starter kit and Jengo Auth
jengo new my-app --kit=react --auth=jengo --pm=pnpm

# Using CodeIgniter Shield instead
jengo new my-app --kit=react --shield

# Full-stack powerhouse with all ecosystem packages
jengo new my-app --kit=vue --auth=jengo --all --pest
```

## Running the Development Server

Once scaffolded, start the unified concurrent development server:

```bash
cd my-app
jengo dev
```

The `jengo dev` command proxies directly to `php spark jengo:dev`, concurrently running your backend server, Vite asset watcher, queue workers, and real-time processes with live terminal controls.

## Available Options

### Starter Kits
- `--kit=<name>`: `default` (Blade-like PHP Views + Tailwind), `react`, `vue`, or `svelte` (Inertia.js SPAs).

### Authentication & Authorization (Select One)
- `--auth` or `--auth=jengo`: Install Jengo Auth (`jengo/auth`) with Vima RBAC/ABAC authorization.
- `--shield` or `--auth=shield`: Install official CodeIgniter Shield (`codeigniter4/shield`).
- `--no-auth` or `--auth=none`: Skip authentication entirely.

### Modular Ecosystem Packages
- `--all`: Install all ecosystem packages (`api`, `schema`, `storage`, `broadcasting`, `ai`, `pdf`, `notifications`).
- `--api`: Install Jengo API Suite (`jengo/api` - The Vault REST & OpenAPI).
- `--schema`: Install Jengo Schema builder & TypeScript generator (`jengo/schema`).
- `--storage`: Install Jengo Storage filesystem abstraction & image pipeline (`jengo/storage`).
- `--broadcasting`: Install Jengo Broadcasting real-time engine (`jengo/broadcasting`).
- `--ai`: Install Jengo AI SDK and agent engine (`jengo/ai`).
- `--pdf`: Install Jengo PDF generation engine (`jengo/pdf`).
- `--notifications`: Install Jengo Notifications multi-channel delivery engine (`jengo/notifications`).

### Tooling & Testing
- `--pest`: Install Pest PHP testing framework (enabled by default).
- `--no-pest`: Do not install Pest PHP testing framework (fallback to standard PHPUnit).
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
