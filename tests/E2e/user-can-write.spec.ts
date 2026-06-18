import { test, expect } from "@playwright/test";

test("user can sign up, create a post, log out", async ({ page }) => {
  // Safety net: if the jQuery AJAX handler ever loads, it pops an alert.
  page.on("dialog", (d) => d.accept());

  const email = `e2e-${Date.now()}@example.com`;

  await page.goto("/auth/signup");
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', "secret123");
  await page.getByRole("button", { name: /sign up/i }).click();
  // Successful signup logs in and redirects home; the LOGOUT button proves the session.
  await expect(page.getByRole("button", { name: /logout/i })).toBeVisible();

  await page.goto("/posts/create");
  await page.fill('input[name="title"]', "E2E title");
  await page.fill('textarea[name="message"]', "E2E body");
  await page.getByRole("button", { name: /save/i }).click();

  await page.goto("/");
  await expect(page.locator("body")).toContainText("E2E title");

  await page.getByRole("button", { name: /logout/i }).click();
  await expect(page).toHaveURL(/\/auth\/login$/);
});
