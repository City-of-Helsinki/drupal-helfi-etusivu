# End-to-End (E2E) Testing with Playwright

This project uses [Playwright](https://playwright.dev/) for end-to-end testing, providing reliable testing for modern web applications.

## Table of Contents
- [Project Structure](#project-structure)
- [Environment Setup](#environment-setup)
- [Shared E2E Package](#shared-e2e-package)
- [Running Tests](#running-tests)
- [Test Lifecycle](#test-lifecycle)
- [Updating Dependencies](#updating-dependencies)

## Project Structure

```
e2e/
├── .env                    # Environment variables
├── playwright.config.ts    # Playwright configuration
├── tests/                  # Test files
├── utils/                  # Test utilities and helpers
│   ├── globalSetup.ts      # Global setup script
│   └── globalTeardown.ts   # Global teardown script
└── scripts/                # Utility scripts
    ├── install-shared-e2e.sh  # Installs shared E2E package
    └── update-shared-e2e.sh   # Updates shared E2E package
```

## Environment Setup

### Required Environment Variables

Create a `.env` file in the `/e2e` directory with the following variables:

```env
# Required
BASE_URL=https://your-drupal-site.docker.so

# Optional
STORAGE_STATE_DIR="./"
APP_DEBUG=true
```

### Available Variables

- **`BASE_URL`**: Base URL of the Drupal installation (required)
- **`STORAGE_STATE_DIR`**: Directory where Playwright stores browser state (like login sessions). The browser state will be saved as `storageState.json` in the chosen directory. The system checks in this order and uses the first one that's available:
  1. The directory you specify in `STORAGE_STATE_DIR`
  2. The directory in `XDG_RUNTIME_DIR` environment variable
  3. The directory in `TMPDIR` environment variable
  4. The system's default temporary directory (via `os.tmpdir()`)
- **`APP_DEBUG`**: Enable debug logging when running tests

## Shared E2E Package

This project uses a shared E2E testing package located at `public/modules/contrib/helfi_platform_config/tests/e2e`. This package provides:
- Base Playwright configuration
- Common test utilities
- Shared test helpers
- Standardized test structure

### Installation

The shared package is automatically installed as a local dependency during `npm install` via the `preinstall` script in `package.json`. The installation process:
1. Builds the shared package
2. Packs it into a `.tgz` file
3. Installs it as a local dependency

## Running Tests

### Install Dependencies

```bash
# Install Node.js dependencies
nvm use
npm install
```

### Run All Tests

```bash
npm test
```

### Run Tests in Headed Mode

```bash
npm run test:headed
```

### Run Tests with Slow Motion 

```bash
npm run test:headed:slow
```

### Run Specific Test File

```bash
npx playwright test tests/your-test-file.spec.ts
```

## Test Lifecycle

### Global Setup (`globalSetup.ts`)

The shared E2E package provides a base global setup that handles:
1. Browser session initialization
2. Cookie consent handling
3. Dialog handling
4. Storage state management

#### Extending Global Setup

To add project-specific setup while preserving the base setup, create a `globalSetup.ts` in your project's `utils` directory. 
The shared configuration will automatically merge both setups by appending your custom setup after the base setup.

### Global Teardown (`globalTeardown.ts`)

The shared E2E package provides a base teardown that cleans up the browser storage state.

#### Extending Global Teardown

To add custom teardown logic, create a `globalTeardown.ts` in your project's `utils` directory:

### Storage State

Playwright's storage state is automatically managed and stored in the directory specified by `STORAGE_STATE_DIR`. This allows for maintaining login state between tests. The storage state file (`storageState.json`) is automatically created during the global setup and cleaned up during teardown.

## Updating Dependencies

### Update Shared E2E Package

Running `npm install` automatically ensures the shared E2E package matches the version specified in the shared E2E `package.json`. 
When developing shared E2E package, you can use `update:e2e` script to update the shared E2E package. 

```bash
npm run update:e2e
```

This will:
1. Bump the package version
2. Rebuild the package
3. Update the local `.tgz` file
4. Update the dependency in `package.json`

### Manual Update

If needed, you can manually update the version of the shared package:

```bash
cd public/modules/contrib/helfi_platform_config/tests/e2e
npm version patch --no-git-tag-version
npm ci
npm run build
```

Then update the reference in `tests/e2e/package.json` to point to the new `.tgz` file. But it's recommended to use `update:e2e` script instead.

## Writing Tests

- Place test files in the `tests/` directory with `test.ts` extension
- Use the shared utilities from `@helfi-platform-config/e2e` when possible

## Debugging

- Set `APP_DEBUG=true` in `.env` for detailed logging
- Check `test-results/` for screenshots and traces on test failures
