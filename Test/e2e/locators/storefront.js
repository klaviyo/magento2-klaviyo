const { test } = require('@playwright/test');
const { generateEmail } = require('../utils/email');

class Storefront {
    constructor(page) {
        this.page = page;
        this.email = generateEmail();
    }

    async goToHomepage() {
        await this.page.goto(process.env.M2_BASE_URL);
    }

    async goToAccountCreationPage() {
        await this.page.goto(`${process.env.M2_BASE_URL}/customer/account/create/`);
    }

    async goToProductPageAndGetProductName() {
        return await test.step('Navigate to product page and wait for Klaviyo identify call', async () => {
            // Navigate to a product page (using the first product from the homepage)
            const klaviyoIdentifyUrl = `/client/profiles/?company_id=`;

            let onRequestFailed;
            const failedRequest = new Promise((_, reject) => {
                onRequestFailed = request => {
                    const url = request.url();
                    // Match any klaviyo.com request — klaviyo.js loads from static.klaviyo.com
                    // and the identify POST goes to a.klaviyo.com. VPN can block either.
                    if (url.includes('klaviyo.com')) {
                        reject(new Error(
                            `Klaviyo request failed: ${request.failure()?.errorText ?? 'unknown error'}\n` +
                            `URL: ${url}\n` +
                            `This is often caused by a VPN or proxy blocking requests to klaviyo.com.\n` +
                            `Try disconnecting from your VPN and running the tests again.`
                        ));
                    }
                };
                this.page.on('requestfailed', onRequestFailed);
            });

            const successfulResponse = Promise.all([
                this.page.waitForResponse(resp => resp.url().includes(klaviyoIdentifyUrl) && resp.status() === 202 && resp.request().method() === 'POST'),
                this.page.goto(`${process.env.M2_BASE_URL}/radiant-tee.html?utm_email=${this.email}`),
            ]);

            try {
                await Promise.race([successfulResponse, failedRequest]);
            } finally {
                this.page.off('requestfailed', onRequestFailed);
            }

            await this.page.locator('.page-title').waitFor();
            // Get product details before adding to cart
            const productName = await this.page.locator('.page-title').textContent();
            return productName;
        });
    }

    async fillOutNewsletterFooterForm() {
        await this.page.fill('#newsletter', this.email);

        // Wait for the subscribe POST rather than a Magento flash message —
        // when "Honor Klaviyo consent" is enabled the module's code path
        // doesn't reliably render the `.messages` flash on the redirect
        // target. The Klaviyo API assertion downstream is the real check.
        const postResponsePromise = this.page.waitForResponse(
            resp => resp.url().includes('/newsletter/subscriber/new') &&
                    resp.request().method() === 'POST',
            { timeout: 30000 },
        );
        await this.page.click('button[title="Subscribe"]');
        const postResponse = await postResponsePromise;
        if (postResponse.status() >= 400) {
            throw new Error(`Newsletter POST failed: ${postResponse.status()} ${postResponse.url()}`);
        }
    }

    async fillOutAccountCreationForm() {
        await this.page.fill('#firstname', 'Test');
        await this.page.fill('#lastname', 'User');
        await this.page.fill('#email_address', this.email);
        await this.page.fill('#password', 'Test1234!');
        await this.page.fill('#password-confirmation', 'Test1234!');
    }

    async checkNewsletterSubscriptionCheckbox() {
        await this.page.check('#is_subscribed');
    }

    async submitAccountCreationForm() {
        await this.page.click('button[title="Create an Account"]');
        // Wait for successful registration
        await this.page.locator('.message-success').waitFor();
    }

    async goToAccountPageAndUnsubscribeFromNewsletter() {
        await this.page.goto(`${process.env.M2_BASE_URL}/newsletter/manage/`);
        // Uncheck the newsletter subscription checkbox
        await this.page.uncheck('#subscription');

        // Save changes
        await this.page.click('button[title="Save"]');

        // Wait for success message
        await this.page.locator('.message-success').waitFor();
    }

    async addProductToCart() {
        await test.step('Select product options and add to cart', async () => {
            // Select size small and orange color
            await this.page.locator('#option-label-size-144-item-167').click(); // Small size
            await this.page.locator('#option-label-color-93-item-56').click(); // Orange color

            // Add product to cart
            await this.page.click('button[title="Add to Cart"]');
            await this.page.locator('.message-success').waitFor({ state: 'visible', timeout: 5000 });
        });
    }

    /**
     * Navigates to the Magento 2 checkout page and waits for it to be ready.
     */
    async goToCheckout() {
        await this.page.goto(`${process.env.M2_BASE_URL}/checkout/`);
        await this.page.locator('#checkout').waitFor({ timeout: 30000 });
    }

    /**
     * Fills the guest email field shown at the top of the checkout shipping step.
     */
    async fillGuestCheckoutEmail() {
        const emailField = this.page.locator('[name="username"]');
        await emailField.waitFor({ timeout: 15000 });
        await emailField.fill(this.email);
    }

    /**
     * Fills the shipping address form in the checkout shipping step.
     * @param {{ firstName: string, lastName: string, phone: string, street: string, city: string, regionCode: string, zip: string, countryId?: string }} info
     */
    async fillGuestShippingInfo(info) {
        const { firstName, lastName, phone, street, city, regionCode, zip, countryId = 'US' } = info;
        await this.page.fill('[name="firstname"]', firstName);
        await this.page.fill('[name="lastname"]', lastName);
        await this.page.fill('[name="street[0]"]', street);
        await this.page.fill('[name="city"]', city);
        await this.page.selectOption('[name="country_id"]', countryId);
        // State/region may be a select or text input depending on country
        const regionSelect = this.page.locator('[name="region_id"]');
        if (await regionSelect.count() > 0) {
            await regionSelect.selectOption({ label: regionCode });
        } else {
            await this.page.fill('[name="region"]', regionCode);
        }
        await this.page.fill('[name="postcode"]', zip);
        await this.page.fill('[name="telephone"]', phone);
    }

    /**
     * Selects the flat rate shipping method and waits for it to be checked.
     */
    async selectFlatRateShipping() {
        const shippingMethod = this.page.locator('[value="flatrate_flatrate"]');
        await shippingMethod.waitFor({ timeout: 15000 });
        await shippingMethod.check();
    }

    /**
     * Checks the mobile consent checkbox in the shipping form.
     */
    async checkMobileConsentAtCheckout() {
        const checkbox = this.page.locator('[name="custom_attributes[kl_sms_consent]"]');
        await checkbox.waitFor({ timeout: 10000 });
        await checkbox.check();
    }

    /**
     * Checks the email consent checkbox in the shipping form.
     */
    async checkEmailConsentAtCheckout() {
        const checkbox = this.page.locator('[name="custom_attributes[kl_email_consent]"]');
        await checkbox.waitFor({ timeout: 10000 });
        await checkbox.check();
    }

    /**
     * Clicks "Next" to proceed from the shipping step to the payment step.
     */
    async proceedToPayment() {
        await this.page.locator('button.continue').click();
        await this.page.locator('.payment-method').waitFor({ timeout: 20000 });
    }

    /**
     * Clicks "Place Order" and waits for the order success page.
     */
    async placeOrder() {
        const placeOrderBtn = this.page.locator('button.action.primary.checkout');
        await placeOrderBtn.waitFor({ timeout: 15000 });
        await placeOrderBtn.click();
        await this.page.locator('.checkout-success').waitFor({ timeout: 30000 });
    }
}

module.exports = Storefront;
