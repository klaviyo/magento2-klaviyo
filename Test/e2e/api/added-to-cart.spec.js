const { test, expect } = require('@playwright/test');
const { backOff } = require('exponential-backoff');
const { createProfileInKlaviyo, checkEvent } = require('../utils/klaviyo-api');
const Storefront = require('../locators/storefront');

/**
 * Tests the Klaviyo event tracking functionality
 *
 * Tests the following Klaviyo API functionality:
 * - Event tracking when products are added to cart
 * - Event properties including product details and cart information
 * - Event association with customer profiles
 *
 * @see https://developers.klaviyo.com/en/reference/create_event
 */
test.describe('Added To Cart Event Tracking', () => {
    test('should create an Added To Cart event in Klaviyo when a user adds an item to their cart', async ({ page }) => {
        test.slow(); // this test is slow, Magento 2 syncs events every 5 minutes.
        const klaviyoAtcMetricId = process.env.METRIC_ID_ATC;

        // Navigate to a product page (using the first product from the homepage)
        const storefront = new Storefront(page);

        // Create profile in Klaviyo first
        const profileId = await createProfileInKlaviyo(storefront.email);

        const productName = await storefront.goToProductPageAndGetProductName();
        await storefront.addProductToCart();

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
        console.log(`Successfully verified Added To Cart event for ${storefront.email}`);
    });
});
