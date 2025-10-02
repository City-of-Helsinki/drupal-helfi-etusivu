import { fetchJsonApiRequest, type JsonApiResponse } from '@helfi-platform-config/e2e/utils/fetchJsonApiRequest';
import { logger } from '@helfi-platform-config/e2e/utils/logger';
import { expect, test } from '@playwright/test';
import { extractTextSegments } from '../utils/extractTextSegments';

/**
 * Type definition for Survey data structure from Drupal's JSON:API
 * Represents the survey content type structure in Drupal
 */
type Survey = {
  attributes: {
    langcode: string;
    status: boolean;
    title: string;
    body?: { processed?: string | null; value?: string | null } | null;
    field_survey_title?: string | null;
    field_publish_externally?: boolean;
    field_survey_link: { uri: string };
  };
};

/**
 * Test to verify that surveys marked for external publishing are visible
 * and properly displayed in their respective language pages
 */
test('Externally published surveys are visible', async ({ page }) => {
  // Remove cookie helfi_no_survey.
  await page.context().clearCookies({ name: 'helfi_no_survey' });

  // Fetch survey data from Drupal's JSON:API.
  const data = await fetchJsonApiRequest<JsonApiResponse<Survey>>(
    process.env.BASE_URL ?? 'https://www.test.hel.ninja',
    '/fi/jsonapi/node/survey',
  );

  // Filter for published surveys that are marked for external publishing.
  const items = data.data.filter(
    (n) => n?.attributes?.status === true && n?.attributes?.field_publish_externally === true,
  ) as Survey[];

  // Skip test if no matching surveys found.
  if (items.length === 0) {
    logger('No externally published surveys in JSON:API; nothing to verify.');
    return;
  }

  logger(`Found ${items.length} externally published surveys in JSON:API; verifying visibility.`);

  await items.reduce(async (prev, item) => {
    await prev;

    const lang = item.attributes.langcode?.trim();
    const html = item.attributes.body?.processed || item.attributes.body?.value;

    if (!html) {
      logger('No HTML content found for survey');
      return;
    }

    const textSegments = extractTextSegments(html);
    expect(textSegments.length, 'No valid text segments found in survey').toBeGreaterThan(0);

    const path = `/${lang || ''}`.replace(/\/+$/, '') || '/';
    await test.step(`Verify survey appears on ${path}`, async () => {
      await page.goto(path, { waitUntil: 'domcontentloaded' });

      // Verify survey dialog is visible and contains expected elements.
      const surveyDialog = page.locator('.dialog--survey');
      await expect(surveyDialog).toBeVisible({ timeout: 5000 });

      // Verify survey title is visible in the dialog.
      await expect(surveyDialog.filter({ hasText: item.attributes.title })).toBeVisible();

      // Verify survey link has the correct target URL.
      const link = surveyDialog.locator('a.dialog__action-button');
      await expect(link).toHaveAttribute('href', item.attributes.field_survey_link.uri);

      // Check each text segment in the survey.
      for (const segment of textSegments) {
        try {
          await expect(surveyDialog.filter({ hasText: segment })).toBeVisible();
          logger(`Found survey on path ${path}, text: ${segment.slice(0, 50)}...`);
          return;
        } catch (_e) {
          logger(`Text segment not found: ${path}: ${segment.slice(0, 50)}...`);
        }
      }

      // If we get here, none of the segments were found.
      throw new Error(`None of the text segments were found in the survey on ${path}`);
    });
  }, Promise.resolve());
});
