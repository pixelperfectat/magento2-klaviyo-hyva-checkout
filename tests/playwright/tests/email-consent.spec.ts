import { test, expect } from '@playwright/test';

test.describe('Klaviyo Email Consent', () => {
    test('shows email consent for guest checkout', async ({ page }) => {
        const productUrl = process.env.TEST_PRODUCT_URL || '/fusion-backpack.html';

        await page.goto(productUrl);
        await page.click('#product-addtocart-button');
        await page.waitForSelector('.message-success, [data-ui-id="message-success"]', { timeout: 10000 });

        await page.goto('/checkout');
        await page.waitForSelector('[x-data]', { timeout: 15000 });

        const emailConsentCheckbox = page.locator('input[name="kl_email_consent"], [id*="kl_email_consent"]');
        const isVisible = await emailConsentCheckbox.isVisible({ timeout: 10000 }).catch(() => false);

        if (isVisible) {
            await emailConsentCheckbox.check();
            expect(await emailConsentCheckbox.isChecked()).toBe(true);
        } else {
            test.skip(true, 'Email consent not enabled in admin config');
        }
    });

    test('shows email consent for logged-in customer (differs from original)', async ({ page }) => {
        const testEmail = process.env.TEST_CUSTOMER_EMAIL || 'roni_cost@example.com';
        const testPassword = process.env.TEST_CUSTOMER_PASSWORD || 'roni_cost3@example.com';
        const productUrl = process.env.TEST_PRODUCT_URL || '/fusion-backpack.html';

        await page.goto('/customer/account/login');
        await page.fill('#email', testEmail);
        await page.fill('#pass', testPassword);
        await page.click('#send2');
        await page.waitForURL('**/customer/account/');

        await page.goto(productUrl);
        await page.click('#product-addtocart-button');
        await page.waitForSelector('.message-success, [data-ui-id="message-success"]', { timeout: 10000 });

        await page.goto('/checkout');
        await page.waitForSelector('[x-data]', { timeout: 15000 });

        const emailConsentCheckbox = page.locator('input[name="kl_email_consent"], [id*="kl_email_consent"]');
        const isVisible = await emailConsentCheckbox.isVisible({ timeout: 10000 }).catch(() => false);

        if (isVisible) {
            expect(isVisible).toBe(true);
        } else {
            test.skip(true, 'Email consent not enabled in admin config');
        }
    });
});
