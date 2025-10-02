import { logger } from '@helfi-platform-config/e2e/utils/logger';
import { expect, test } from '@playwright/test';

/**
 * Test to verify that the language switcher is functional.
 */
test('Verify language switcher links', async ({ page }) => {
  // Navigate to the initial page.
  const initialPath = '/fi';
  await page.goto(initialPath, { waitUntil: 'domcontentloaded' });
  const initialUrl = page.url();

  // Wait for language links to be visible.
  await page.waitForSelector('.language-link:not(.is-disabled)');

  // Get all non-disabled language links.
  const languageLinks = await page.locator('.language-link:not(.is-disabled)').all();

  // Click through each language link.
  for (const link of languageLinks) {
    // Get the language code and expected URL.
    const lang = await link.getAttribute('lang');
    const expectedUrl = await link.getAttribute('href');

    // Skip if no href or lang attribute.
    if (!expectedUrl || !lang) continue;

    await test.step(`Verify ${lang} language link`, async () => {
      // Click the language link and verify
      // the URL has changed to the expected path.
      await link.click();
      await page.waitForURL((url) => url.toString() !== initialUrl, { timeout: 5000 });
      const currentUrl = page.url();
      expect(currentUrl).toMatch(new RegExp(`/${lang.toLowerCase()}(/|$)`));

      // Verify the language attribute is set correctly.
      const htmlLang = await page.getAttribute('html', 'lang');
      expect(htmlLang).toBe(lang.toLowerCase());
      logger(`Language link ${lang} verified.`);
    });
  }

  // Test clicking back to the initial language.
  await test.step('Return to initial language', async () => {
    // Find and click the initial language link.
    const initialLangLink = page.locator(`.language-link[lang="${initialPath.substring(1, 3)}"]`);
    await expect(initialLangLink).toBeVisible();
    await initialLangLink.click();

    // Wait for navigation and verify we're back to the initial path.
    await page.waitForURL((url) => url.toString().includes(initialPath), { timeout: 5000 });

    // Verify the URL and language attribute.
    const finalUrl = page.url();
    expect(finalUrl).toContain(initialPath);
    const htmlLang = await page.getAttribute('html', 'lang');
    expect(htmlLang).toBe(initialPath.substring(1, 3));
    logger(`Returned to initial language successfully: ${initialPath}.`);
  });
});
