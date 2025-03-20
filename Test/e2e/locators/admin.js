class Admin {
  constructor(page) {
    this.page = page;

    // Sidebar
    this.storesLink = page.locator("#menu-magento-backend-stores");

    // Sidebar > Stores > Configuration
    this.storesSettingsSection = page.locator(".item-stores-settings");
    this.configurationLink = this.storesSettingsSection.locator("//span[text()='Configuration']");

    // Configuration Panel
    this.configurationMenuWrapper = page.locator("#system_config_tabs");

    // Klaviyo Configuration Tab
    this.klaviyoConfigLink = this.configurationMenuWrapper.locator("//strong[text()='Klaviyo']");

    // Configuration Panel > Klaviyo > Newsletter
    this.klaviyoConfigMenu = this.klaviyoConfigLink.locator("..").locator("..");
    this.klaviyoNewsletterLink = this.klaviyoConfigMenu.locator("//span[text()='Newsletter']");

    // Configuration Panel > Klaviyo > Consent at Checkout
    this.klaviyoConsentAtCheckoutLink = this.klaviyoConfigMenu.locator("//span[text()='Consent at Checkout']");
  }

  async navigateToKlaviyoNewsletterConfig() {
    await this.page.waitForLoadState();
    await this.storesLink.click();
    await this.page.waitForLoadState();
    await this.configurationLink.click();
    await this.page.waitForLoadState();
    await this.klaviyoConfigLink.click();
    await this.klaviyoNewsletterLink.click();
    await this.page.waitForLoadState();
  }

  async updateKlaviyoNewsletterConfig(desiredOptionLabel) {
    const honorKlaviyoConsentValue = await this.page.getByLabel(desiredOptionLabel).isChecked();

    // Enable honor Klaviyo consent setting
    if (!honorKlaviyoConsentValue) {
        await this.page.getByLabel(desiredOptionLabel).check();

        // Wait for the checkbox to be fully checked
        await this.page.waitForTimeout(1000);

        // Wait for save button to be visible and clickable
        const saveButton = this.page.getByRole('button', { name: 'Save Config' });
        await saveButton.waitFor({ state: 'visible', timeout: 10000 });

        // Try to click with a longer timeout
        await saveButton.click();

        // Wait for the click to register and success message
        await this.page.locator('.message-success').waitFor();
        await this.page.getByLabel(desiredOptionLabel).waitFor();
    }
  }

  async navigateToKlaviyoConsentAtCheckoutConfig() {
    await this.page.waitForLoadState();
    await this.storesLink.click();
    await this.configurationLink.click();
    await this.page.waitForLoadState();
    await this.klaviyoConfigLink.click();
    await this.klaviyoConsentAtCheckoutLink.click();
    await this.page.waitForLoadState();
  }
}

module.exports = Admin;
