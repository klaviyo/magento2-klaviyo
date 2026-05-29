const { test, expect } = require('@playwright/test');
const { backOff } = require('exponential-backoff');
const Admin = require('../locators/admin');
const Storefront = require('../locators/storefront');
const { checkProfileSubscriptions } = require('../utils/klaviyo-api');

test.describe.configure({ mode: 'serial' });

const DEFAULT_SHIPPING = {
    firstName: 'Test',
    lastName: 'Buyer',
    phone: '5555550100',
    street: '123 Main St',
    city: 'Austin',
    regionCode: 'Texas',
    zip: '78701',
    countryId: 'US',
};

/**
 * Configures the admin mobile consent settings and saves.
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Browser} browser
 * @param {boolean} isActive
 * @param {string[]} channels - e.g. ['sms'], ['whatsapp'], or ['sms', 'whatsapp']
 */
async function configureMobileConsent(page, browser, isActive, channels) {
    const baseUrl = process.env.M2_BASE_URL;
    if (!baseUrl) throw new Error('M2_BASE_URL environment variable is not set');

    const admin = new Admin(page);
    await admin.page.goto(`${baseUrl}/admin/admin/dashboard`);
    await admin.page.locator('.admin__menu').waitFor();

    await admin.navigateToKlaviyoConsentAtCheckoutConfig();
    await admin.setMobileConsentIsActive(isActive);
    if (isActive) {
        await admin.setMobileConsentChannels(channels);
    }
    await admin.saveConsentAtCheckoutConfig();
    console.log(`Mobile consent configured: isActive=${isActive}, channels=${JSON.stringify(channels)}`);
}

/**
 * Performs a guest checkout with the given consent options and returns the buyer email.
 * Asserts no requests to the legacy webhook endpoint during checkout.
 * @param {import('@playwright/test').Page} page
 * @param {{ checkMobile?: boolean, checkEmail?: boolean }} consentOptions
 * @returns {Promise<string>} The email used for checkout
 */
async function doGuestCheckoutWithConsent(page, consentOptions = {}) {
    const { checkMobile = false, checkEmail = false } = consentOptions;
    const storefront = new Storefront(page);

    // Track any calls to the old webhook endpoint — there must be zero
    const legacyWebhookCalls = [];
    page.on('request', req => {
        if (req.url().includes('/api/webhook/integration/magento_two') && req.method() === 'POST') {
            legacyWebhookCalls.push(req.url());
        }
    });

    // Add a product to cart
    await storefront.goToProductPageAndGetProductName();
    await storefront.addProductToCart();

    // Navigate to checkout
    await storefront.goToCheckout();
    await storefront.fillGuestCheckoutEmail();
    await storefront.fillGuestShippingInfo(DEFAULT_SHIPPING);
    await storefront.selectFlatRateShipping();

    if (checkMobile) {
        await storefront.checkMobileConsentAtCheckout();
    }
    if (checkEmail) {
        await storefront.checkEmailConsentAtCheckout();
    }

    await storefront.proceedToPayment();
    await storefront.placeOrder();

    // Assert no legacy webhook calls were made during checkout
    expect(legacyWebhookCalls).toHaveLength(0);

    return storefront.email;
}

/**
 * Polls the Klaviyo API until the profile's subscriptions object is populated.
 * @param {string} email
 * @returns {Promise<object>} The subscriptions object
 */
async function waitForProfileSubscriptions(email) {
    return backOff(
        async () => {
            const subscriptions = await checkProfileSubscriptions(email);
            if (!subscriptions) {
                throw new Error('Profile not found yet');
            }
            return subscriptions;
        },
        {
            numOfAttempts: 11,
            retry: (error, attemptNumber) => {
                console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
                return true;
            }
        }
    );
}

/**
 * Tests the WhatsApp / mobile consent channel-enable matrix at checkout.
 *
 * Each test configures the Magento admin with a specific set of enabled channels,
 * performs a guest checkout with the mobile consent checkbox checked, then verifies
 * via the Klaviyo V3 API that only the expected subscription channels are present.
 *
 * Also asserts that zero outbound POST requests are sent to the legacy consent webhook
 * (/api/webhook/integration/magento_two) during checkout.
 *
 * @see Observer/SaveOrderMarketingConsent.php
 * @see Plugin/CheckoutLayoutPlugin.php
 */
test.describe('WhatsApp / Mobile Consent Channel Matrix', () => {
    test('sms_only_mobile_consent: SMS-only channel config → only sms.marketing subscription', async ({ page, browser }) => {
        await configureMobileConsent(page, browser, true, ['sms']);

        const email = await doGuestCheckoutWithConsent(page, { checkMobile: true });

        const subscriptions = await waitForProfileSubscriptions(email);

        // SMS subscription must be present and SUBSCRIBED
        expect(subscriptions?.sms?.marketing?.consent).toBe('SUBSCRIBED');
        // WhatsApp subscription must NOT be present
        expect(subscriptions?.whatsapp).toBeUndefined();

        console.log(`sms_only_mobile_consent: verified sms.marketing=SUBSCRIBED for ${email}`);
    });

    test('whatsapp_only_mobile_consent: WhatsApp-only channel config → only whatsapp.marketing subscription', async ({ page, browser }) => {
        await configureMobileConsent(page, browser, true, ['whatsapp']);

        const email = await doGuestCheckoutWithConsent(page, { checkMobile: true });

        const subscriptions = await waitForProfileSubscriptions(email);

        // WhatsApp subscription must be present and SUBSCRIBED
        expect(subscriptions?.whatsapp?.marketing?.consent).toBe('SUBSCRIBED');
        // SMS subscription must NOT be present
        expect(subscriptions?.sms).toBeUndefined();

        console.log(`whatsapp_only_mobile_consent: verified whatsapp.marketing=SUBSCRIBED for ${email}`);
    });

    test('both_channels_mobile_consent: SMS+WhatsApp config → both sms and whatsapp subscriptions', async ({ page, browser }) => {
        await configureMobileConsent(page, browser, true, ['sms', 'whatsapp']);

        const email = await doGuestCheckoutWithConsent(page, { checkMobile: true });

        const subscriptions = await waitForProfileSubscriptions(email);

        // Both channels must be SUBSCRIBED
        expect(subscriptions?.sms?.marketing?.consent).toBe('SUBSCRIBED');
        expect(subscriptions?.whatsapp?.marketing?.consent).toBe('SUBSCRIBED');

        console.log(`both_channels_mobile_consent: verified sms+whatsapp for ${email}`);
    });

    test('email_and_both_channels: email + mobile consent → email, sms, and whatsapp all SUBSCRIBED', async ({ page, browser }) => {
        await configureMobileConsent(page, browser, true, ['sms', 'whatsapp']);

        const email = await doGuestCheckoutWithConsent(page, { checkMobile: true, checkEmail: true });

        const subscriptions = await waitForProfileSubscriptions(email);

        // All three channels must be SUBSCRIBED
        expect(subscriptions?.email?.marketing?.consent).toBe('SUBSCRIBED');
        expect(subscriptions?.sms?.marketing?.consent).toBe('SUBSCRIBED');
        expect(subscriptions?.whatsapp?.marketing?.consent).toBe('SUBSCRIBED');

        console.log(`email_and_both_channels: verified email+sms+whatsapp for ${email}`);
    });
});
