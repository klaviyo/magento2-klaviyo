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

  async navigateToKlaviyoNewsletterConfig(page) {
    await this.page.waitForLoadState();
    await this.storesLink.click();
    await this.page.waitForLoadState();
    await this.configurationLink.click();
    await this.page.waitForLoadState();
    await this.klaviyoConfigLink.click();
    await this.klaviyoNewsletterLink.click();
    await this.page.waitForLoadState();
  }

  async navigateToKlaviyoConsentAtCheckoutConfig(page) {
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
