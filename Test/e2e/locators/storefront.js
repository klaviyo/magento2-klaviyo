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
        // Navigate to a product page (using the first product from the homepage)
        await Promise.all([
            this.page.waitForResponse(resp => resp.url().includes(`/client/profiles/?company_id=`) && resp.status() === 202 && resp.request().method() === 'POST'),
            this.page.goto(`${process.env.M2_BASE_URL}/radiant-tee.html?utm_email=${this.email}`),
        ])
        await this.page.locator('.page-title').waitFor();
        // Get product details before adding to cart
        const productName = await this.page.locator('.page-title').textContent();
        return productName;
    }

    async fillOutNewsletterFooterForm() {
        // Fill in the newsletter subscription form in the footer
        await this.page.fill('#newsletter', this.email);

        // Click the subscribe button
        await this.page.click('button[title="Subscribe"]');

        // Wait for success message
        await this.page.locator('.message-success').waitFor();
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
        // Select size small and orange color
        await this.page.locator('#option-label-size-144-item-167').click(); // Small size
        await this.page.locator('#option-label-color-93-item-56').click(); // Orange color

        // Add product to cart
        await this.page.click('button[title="Add to Cart"]');
        await this.page.locator('.message-success').waitFor({ state: 'visible', timeout: 5000 });
    }
}

module.exports = Storefront;
