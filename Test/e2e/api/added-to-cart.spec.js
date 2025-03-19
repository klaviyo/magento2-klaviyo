const { test, expect } = require('@playwright/test');
const { backOff } = require('exponential-backoff');
const { generateEmail } = require('../utils/email');
const { createProfileInKlaviyo, checkEvent } = require('../utils/klaviyo-api');

/**
 * Tests the Klaviyo event tracking endpoint for the "Added To Cart" event.
 * Endpoints tested:
 * - POST /profiles/ - Creates a profile for event tracking
 * - GET /events/ - Retrieves events to validate tracking
 * Tests the "Added To Cart" event tracking functionality
 * and validates that events are properly recorded in Klaviyo
 *
 * @see https://developers.klaviyo.com/en/reference/create-profile
 * @see https://developers.klaviyo.com/en/reference/get-events
 */
test.describe('Added To Cart Event Tracking', () => {

    test('should create an Added To Cart event in Klaviyo when a user adds an item to their cart', async ({ page }) => {
        test.slow(); // this test is slow, Magento 2 syncs events every 5 minutes.
        const baseUrl = process.env.M2_BASE_URL;
        const klaviyoAtcMetricId = process.env.METRIC_ID_ATC;
        const testEmail = generateEmail();

        // Create profile in Klaviyo first
        const profileId = await createProfileInKlaviyo(testEmail);

        // Navigate to a product page (using the first product from the homepage)
        await page.goto(`${baseUrl}/radiant-tee.html?utm_email=${testEmail}`);
        await page.locator('.page-title').waitFor();

        // Get product details before adding to cart
        const productName = await page.locator('.page-title').textContent();

        // Select size small and orange color
        await page.locator('#option-label-size-144-item-167').click(); // Small size
        await page.locator('#option-label-color-93-item-56').click(); // Orange color

        // Add product to cart
        await page.click('button[title="Add to Cart"]');
        await page.locator('.message-success').waitFor({ state: 'visible', timeout: 5000 });

        // Use exponential backoff to check for the Added To Cart event
        const events = await backOff(
            async () => {
                const results = await checkEvent(profileId, klaviyoAtcMetricId);
                if (results.data.length === 0) {
                    throw new Error('Added To Cart event not found yet');
                }
                return results;
            },
            {
                numOfAttempts: 13, // 6 minutes with exponential backoff
                retry: (error, attemptNumber) => {
                    console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
                    return true;
                }
            }
        );

        const metricData = events.included[0];
        const eventData = events.data[0].attributes.event_properties;

        // Validate metric data
        expect(metricData.attributes.name).toBe('Added To Cart');
        expect(metricData.attributes.integration.key).toBe('api'); // These events should be unbranded

        // Validate event data
        expect(eventData.AddedItemProductName).toBe(productName.trim());

        // Log success for debugging
        console.log(`Successfully verified Added To Cart event for ${testEmail}`);
    });
});
