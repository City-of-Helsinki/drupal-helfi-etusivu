import { test } from '@playwright/test';
import { cookieHandler } from '@helfi-platform-config/e2e/utils/handlers';

test('Smoke test', async ({ page }) => {
  await page.goto('/fi/');
  await cookieHandler(page);
});
