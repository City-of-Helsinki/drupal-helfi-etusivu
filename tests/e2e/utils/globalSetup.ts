import { FullConfig, chromium } from '@playwright/test';

export default async function globalSetup(config: FullConfig) {
  const { baseURL, storageState } = config.projects[0].use;
  const browser = await chromium.launch();
  const page = await browser.newPage({ baseURL });

  try {
    // Navigate to the base URL first
    await page.goto(baseURL!);
  } finally {
    await browser.close();
  }
}
