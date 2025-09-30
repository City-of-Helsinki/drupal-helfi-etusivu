/**
 * Project-specific Playwright configuration.
 *
 * This file extends the base (playwright.config.ts) configuration
 * from @helfi-platform-config/e2e. This approach ensures we maintain
 * consistency of node modules versions across all Helfi instances.
 *
 * The base configuration includes:
 * - Standard test directory structure
 * - Common test settings and timeouts
 * - Default reporters and output directories
 * - Common browser configurations
 *
 */
import { makeConfig } from '@helfi-platform-config/e2e/playwrightConfig';
require('@dotenvx/dotenvx').config({ path: ['.env'] });

/**
 * Extend the base Playwright configuration with project-specific settings.
 */
export default makeConfig({
  globalSetup: ['./utils/projectSetup.ts'],
  globalTeardown: ['./utils/projectTeardown.ts'],
  use: {
    baseURL: process.env.BASE_URL ?? 'https://localhost',
    screenshot: 'only-on-failure',
  },
});
