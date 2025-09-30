import { test, expect } from '@playwright/test';
import { logger } from '@helfi-platform-config/e2e/utils/logger';
import { fetchJsonApiRequest } from '@helfi-platform-config/e2e/utils/fetchJsonApiRequest';
import { extractTextSegments } from '../utils/extractTextSegments';

/**
 * Type definition for Announcement data structure from Drupal's JSON:API
 * Matches the structure of the announcement content type in Drupal
 */
type Announcement = {
  id: string;
  attributes: {
    langcode: string;
    status: boolean;
    title?: string | null;
    body?: { processed?: string | null; value?: string | null } | null;
    field_announcement_title?: string | null;
    field_publish_externally?: boolean;
  };
};

/**
 * Extracts a meaningful text segment from announcement content for verification
 * @param announcement The announcement object
 * @returns A text segment suitable for verification, or undefined if none found
 */
const pickAssertionText = (announcement: Announcement): string | undefined => {
  // Get HTML content from either processed or raw value.
  const { body } = announcement.attributes;
  const html = body?.processed || body?.value;

  if (!html) return undefined;

  // Extract text segments and return the first meaningful one.
  const segments = extractTextSegments(html);
  return segments.find(segment => segment.length >= 10)?.slice(0, 120);
};

/**
 * Test to verify that announcements marked for external publishing are visible
 * on their respective language home pages
 */
test('Externally published announcements are visible', async ({ request, page }) => {

  // Fetch announcements from Drupal's JSON:API.
  const data = await fetchJsonApiRequest<any>(
    process.env.BASE_URL ?? 'https://www.test.hel.ninja',
    '/fi/jsonapi/node/announcement',
  );

  // Verify we received data from the API.
  expect(data).toHaveProperty('data');

  // Filter for published announcements that are marked for external publishing.
  const items: Announcement[] = (data?.data ?? []).filter(
    (n: Announcement) =>
      n?.attributes?.status === true &&
      n?.attributes?.field_publish_externally === true
  );

  // Skip test if no matching announcements found.
  if (items.length === 0) {
    logger('No externally published announcements in JSON:API; nothing to verify.')
    return;
  }

  logger(`Found ${items.length} externally published announcements in JSON:API; verifying visibility.`)

  await items.reduce(async (prev, item) => {
    await prev;

    // Get language and content.
    const lang = item.attributes.langcode?.trim();
    const html = item.attributes.body?.processed || item.attributes.body?.value;

    // Skip if no content to verify.
    if (!html) {
      logger('No HTML content found for announcement');
      return;
    }

    // Extract and validate text segments for verification.
    const textSegments = extractTextSegments(html);
    expect(textSegments.length, `Found ${textSegments.length} text segments in announcement: ${item.attributes.title}`).toBeGreaterThan(0);

    // Construct path based on language.
    const path = `/${lang || ''}`.replace(/\/+$/, '') || '/';

    await test.step(`Verify announcement appears on ${path}`, async () => {
      // Navigate to the page and retrieve the announcement content.
      await page.goto(path, { waitUntil: 'domcontentloaded' });
      const announcementContent = page.locator(`[data-uuid="${item.id}"]`);

      // Try to find each text segment in the announcement and verify
      // it is visible.
      for (const segment of textSegments) {
        await expect(
          announcementContent.filter({ hasText: segment })
        ).toBeVisible({
          timeout: 3_000,
        });
        logger(`Found announcement on path ${path}, text: ${segment.slice(0, 50)}...`);
        return;
      }

      // If no segments were found, fail the test.
      throw new Error(`None of the text segments were found in the announcement on ${path}`);
    });
  }, Promise.resolve());
});
