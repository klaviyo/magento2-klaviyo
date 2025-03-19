const { test, expect } = require('@playwright/test');
const dotenv = require('dotenv');
const path = require('path');
const Admin = require('../locators/admin');

// Load environment variables
dotenv.config({ path: path.resolve(__dirname, '../../.env') });

test.use({ storageState: 'playwright/.auth/admin.json' });

/**
 * Tests the Klaviyo list population in admin dropdowns. Ensures more than 10
 * lists exist to validate pagination works as expected.
 * Endpoints tested:
 * - GET /lists/ - Retrieves all lists
 * - GET /list-relationships/ - Retrieves list relationships
 * Tests the configuration of newsletter lists in the Magento admin panel
 * and validates that the lists are properly synced with Klaviyo
 *
 * @see https://developers.klaviyo.com/en/reference/get-lists
 * @see https://developers.klaviyo.com/en/reference/get-list-relationships
 */
test.describe('Newsletter Lists Configuration', () => {
  test('should display more than 10 lists in the newsletter configuration dropdown', async ({ page }) => {
    const baseUrl = process.env.M2_BASE_URL;
    if (!baseUrl) {
      throw new Error('M2_BASE_URL environment variable is not set');
    }

    await page.goto(`${baseUrl}/admin/admin/dashboard`);

    // Initialize Admin class
    const admin = new Admin(page);

    // Navigate to Stores > Configuration > Klaviyo > Newsletter
    await admin.navigateToKlaviyoNewsletterConfig(page);

    // Wait for the page to load and the lists dropdown to be visible
    await page.locator('#klaviyo_reclaim_newsletter_newsletter_newsletter').waitFor({ state: 'visible', timeout: 5000 });

    // Get all options from the dropdown
    const options = await page.locator('#klaviyo_reclaim_newsletter_newsletter_newsletter option').all();

    // Assert that there are more than 10 lists
    expect(options.length).toBeGreaterThan(10);

    // Log the number of lists found for debugging
    console.log(`Found ${options.length} newsletter lists`);
  });

  test('should display more than 10 lists in the consent at checkout configuration dropdown', async ({ page }) => {
    const baseUrl = process.env.M2_BASE_URL;
    if (!baseUrl) {
      throw new Error('M2_BASE_URL environment variable is not set');
    }

    await page.goto(`${baseUrl}/admin/admin/dashboard`);
    // Initialize Admin class
    const admin = new Admin(page);

    // Navigate to Stores > Configuration > Klaviyo > Consent at Checkout
    await admin.navigateToKlaviyoConsentAtCheckoutConfig(page);

    // Wait for the page to load and the lists dropdowns to be visible
    await page.locator('#klaviyo_reclaim_consent_at_checkout_email_consent_list_id').waitFor({ state: 'visible', timeout: 5000 });
    await page.locator('#klaviyo_reclaim_consent_at_checkout_sms_consent_list_id').waitFor({ state: 'visible', timeout: 5000 });

    // Get all options from the dropdowns
    const emailOptions = await page.locator('#klaviyo_reclaim_consent_at_checkout_email_consent_list_id option').all();
    const smsOptions = await page.locator('#klaviyo_reclaim_consent_at_checkout_sms_consent_list_id option').all();

    // Assert that there are more than 10 lists in each dropdown
    expect(emailOptions.length).toBeGreaterThan(10);
    expect(smsOptions.length).toBeGreaterThan(10);

    // Log the number of lists found for debugging
    console.log(`Found ${emailOptions.length} consent at checkout lists`);
  });
});