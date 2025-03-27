const { playwright } = require('@playwright/test');

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

    // Sidebar > System > Cache Management
    this.systemSettingsSection = page.locator("#menu-magento-backend-system");
    this.cacheManagementLink = this.systemSettingsSection.locator("//span[text()='Cache Management']");

  }

  async navigateToNewsletterConfigAndHandleRefresh() {
    try {
      await this.navigateToKlaviyoNewsletterConfig();
    } catch (error) {
      await this.page.reload();
      await this.page.waitForLoadState();
      await this.navigateToKlaviyoNewsletterConfig();
    }
  }

  async navigateToKlaviyoNewsletterConfig() {
      // timeout for tests is pretty long to deal with API latency, setting a shorter timeout here
      await this.page.waitForLoadState();
      await this.storesLink.click({timeout: 15000});
      await this.page.waitForLoadState();
      await this.configurationLink.scrollIntoViewIfNeeded();
      await this.configurationLink.click({timeout: 15000});
      await this.page.waitForLoadState();
      await this.klaviyoConfigLink.scrollIntoViewIfNeeded();
      await this.klaviyoConfigLink.click({timeout: 15000});
      await Promise.all([
        this.page.waitForResponse(resp => resp.request().url().includes('/admin/admin/system_config/edit/section/klaviyo_reclaim_newsletter') && resp.status() === 200, {timeout: 15000}),
        this.klaviyoNewsletterLink.click()
      ]);
  }

  async navigateToCacheManagement() {
    await this.page.waitForLoadState();
    await this.systemSettingsSection.click();
    await this.page.waitForLoadState();
    await this.cacheManagementLink.click();
    await this.page.waitForLoadState();
  }

  async refreshConfigCache() {
    await this.page.waitForLoadState();

    // Find the row for XML configurations
    const row = await this.page.getByRole('row').filter({ hasText: 'Various XML configurations that were collected across modules and merged' });

    // Check if the row has 'Invalidated' status
    const statusCell = await row.getByRole('cell').filter({ hasText: 'Invalidated' });
    if (await statusCell.count() === 0) {
      console.log('XML configurations cache is already valid, no refresh needed');
      return;
    }

    // If we get here, the cache is invalidated, so refresh it
    await row.getByRole('checkbox').check();
    await this.page.locator('.admin__data-grid-toolbar').getByRole('button', { name: 'Submit' }).click();
    await this.page.waitForLoadState();
    console.log('XML configurations cache refreshed');
  }

  async updateKlaviyoNewsletterConfig(desiredOptionLabel, timeout = 0) {
    const honorKlaviyoConsentValue = await this.page.getByLabel(desiredOptionLabel).isChecked();

    // Enable honor Klaviyo consent setting
    if (!honorKlaviyoConsentValue) {
        // await this.page.reload();
        await this.page.getByLabel(desiredOptionLabel).check();

        // Wait for the checkbox to be fully checked
        await this.page.waitForTimeout(1000);

        // Wait for save button to be visible and clickable
        const saveButton = await this.page.getByRole('button', { name: 'Save Config' });
        await saveButton.waitFor();
        await saveButton.click();
        // Wait for the click to register and success message
        await this.page.locator('.message-success').waitFor({timeout: timeout});
        await this.page.getByLabel(desiredOptionLabel).waitFor();

    }
  }

  async navigateToKlaviyoConsentAtCheckoutConfig() {
    await this.page.waitForLoadState();
    await this.storesLink.scrollIntoViewIfNeeded();
    await this.storesLink.click();
    await this.configurationLink.scrollIntoViewIfNeeded();
    await this.configurationLink.click();
    await this.page.waitForLoadState();
    await this.klaviyoConfigLink.scrollIntoViewIfNeeded();
    await this.klaviyoConfigLink.click();
    await this.klaviyoConsentAtCheckoutLink.scrollIntoViewIfNeeded();
    await this.klaviyoConsentAtCheckoutLink.click();
    await this.page.waitForLoadState();
  }
}

module.exports = Admin;
