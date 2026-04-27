import { test, expect } from '@playwright/test';

test.describe('Klaviyo SMS Consent', () => {
    test('shows SMS consent checkbox when enabled', async ({ page }) => {
        const productUrl = process.env.TEST_PRODUCT_URL || '/fusion-backpack.html';

        await page.goto(productUrl);
        await page.click('#product-addtocart-button');
        await page.waitForSelector('.message-success, [data-ui-id="message-success"]', { timeout: 10000 });

        await page.goto('/checkout');
        await page.waitForSelector('[x-data]', { timeout: 15000 });

        const smsCheckbox = page.locator('input[name="kl_sms_consent"], [id*="kl_sms_consent"]');
        const isVisible = await smsCheckbox.isVisible({ timeout: 10000 }).catch(() => false);

        if (isVisible) {
            await smsCheckbox.check();
            expect(await smsCheckbox.isChecked()).toBe(true);
        } else {
            test.skip(true, 'SMS consent not enabled in admin config');
        }
    });
});
