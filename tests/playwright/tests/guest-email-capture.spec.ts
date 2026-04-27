import { test, expect } from '@playwright/test';

test.describe('Klaviyo Guest Email Capture', () => {
    test('captures guest email and identifies with Klaviyo', async ({ page }) => {
        const productUrl = process.env.TEST_PRODUCT_URL || '/fusion-backpack.html';
        const testEmail = 'playwright-test@example.com';

        await page.goto(productUrl);
        await page.click('#product-addtocart-button');
        await page.waitForSelector('.message-success, [data-ui-id="message-success"]', { timeout: 10000 });

        await page.goto('/checkout');
        await page.waitForSelector('input[name="email_address"], input[type="email"]', { timeout: 15000 });

        const emailInput = page.locator('input[name="email_address"], input[type="email"]').first();
        await emailInput.fill(testEmail);
        await emailInput.blur();

        await page.waitForTimeout(3000);

        const isIdentified = await page.evaluate(async () => {
            if (typeof window.klaviyo !== 'undefined') {
                return window.klaviyo.isIdentified();
            }
            return false;
        });

        expect(isIdentified).toBe(true);
    });
});
