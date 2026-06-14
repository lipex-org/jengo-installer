# Jengo Installer

The Jengo CLI installer is a tool for quickly bootstrapping new Jengo-powered CodeIgniter 4 applications.

## Installation

You can install the Jengo Installer as a global Composer dependency:

```bash
composer global require jengo/installer
```

Make sure your global Composer `bin` directory is in your system's PATH.

## Usage

### Create a new application

To create a new Jengo application, run:

```bash
jengo new my-app
```

### Choose a starter kit

You can specify a starter kit using the `--kit` option. Available kits: `react`, `vue`, `svelte`.

```bash
jengo new my-app --kit=react
```

### Force installation

If the directory already exists, you can force the installation using the `--force` or `-f` flag:

```bash
jengo new my-app --force
```

## How it works

The installer performs the following steps:
1. Runs `composer create-project codeigniter4/appstarter` to initialize a fresh CI4 project.
2. Installs `jengo/base` core package.
3. If a kit is specified:
   - Installs `jengo/inertia`.
   - Runs `php spark jengo:install vite`.
   - Runs `php spark jengo:install inertia`.

## License

MIT
