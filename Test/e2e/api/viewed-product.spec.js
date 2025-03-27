const { test, expect } = require('@playwright/test');
const { backOff } = require('exponential-backoff');
const { createProfileInKlaviyo, checkEvent } = require('../utils/klaviyo-api');
const Storefront = require('../locators/storefront');

/**
 * Tests the Klaviyo client-side tracking functionality
 *
 * Tests the following Klaviyo API functionality:
 * - Event tracking when products are viewed
 * - Event properties including product details and external catalog id
 * - Event association with customer profiles
 *
 * @see https://developers.klaviyo.com/en/docs/javascript_api#track-events-and-actions
 */
test.describe('Viewed Product Event Tracking', () => {
    test('should create an Viewed Product event in Klaviyo when a user views a product', async ({ page }) => {
        const klaviyoViewedProductMetricId = process.env.METRIC_ID_VIEWED_PRODUCT;

        // Navigate to a product page (using the first product from the homepage)
        const storefront = new Storefront(page);

        // Create profile in Klaviyo first
        const profileId = await createProfileInKlaviyo(storefront.email);

        const productName = await storefront.goToProductPageAndGetProductName();
        await storefront.page.reload();

        // Use exponential backoff to check for the Viewed Product event
        const events = await backOff(
            async () => {
                const results = await checkEvent(profileId, klaviyoViewedProductMetricId);
                if (results.data.length === 0) {
                    throw new Error('Viewed Product event not found yet');
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
        expect(metricData.attributes.name).toBe('Viewed Product');
        expect(metricData.attributes.integration.key).toBe('api'); // These events should be unbranded

        // Validate event data
        expect(eventData.Name).toBe(productName.trim());
        expect(eventData.ProductID).toBe("1556");
        expect(eventData.external_catalog_id).toBe('1-1');
        expect(eventData.integration_key).toBe('magento_two');

        // Log success for debugging
        console.log(`Successfully verified Viewed Product event for ${storefront.email}`);
    });
});
