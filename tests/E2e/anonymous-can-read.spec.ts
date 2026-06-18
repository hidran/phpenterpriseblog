import { test, expect } from "@playwright/test";

test("anonymous visitor can read the post list and a post", async ({ page }) => {
  await page.goto("/");
  await expect(page).toHaveTitle(/.+/);
  // Scope to <main> so we match a real post link, not the navbar "New post" (/posts/create).
  const postLink = page.locator("main a[href^='/posts/']").first();
  await expect(postLink).toBeVisible();
  await postLink.click();
  await expect(page).toHaveURL(/\/posts\/\d+/);
});
