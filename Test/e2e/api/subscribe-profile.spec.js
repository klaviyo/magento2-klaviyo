const { test, expect } = require('@playwright/test');
const dotenv = require('dotenv');
const path = require('path');
const axios = require('axios');
const { backOff } = require('exponential-backoff');
const { generateEmail } = require('../utils/email');
const Admin = require('../locators/admin');
const { createProfileInKlaviyo, checkEvent, getProfileIdByEmail, checkProfileInKlaviyo, checkProfileListRelationships } = require('../utils/klaviyo-api');

// Load environment variables
dotenv.config({ path: path.resolve(__dirname, '../../.env') });

test.describe.configure({ mode: 'serial' });

/**
 * Tests the Klaviyo profile subscription endpoints when honor Klaviyo consent is enabled
 * Endpoints tested:
 * - POST /profiles/ - Creates a profile with SUBSCRIBED consent
 * - GET /profiles/ - Retrieves profile data to validate consent state
 * Tests various subscription methods (footer form, account creation) and unsubscription
 * to ensure proper consent state management when honor Klaviyo consent is enabled
 *
 * @see https://developers.klaviyo.com/en/reference/create-profile
 * @see https://developers.klaviyo.com/en/reference/get-profiles
 */
test.describe('Profile Subscription - honor Klaviyo consent', () => {
    test.describe.configure({ mode: 'default' });
    test.beforeEach(async ({ page }) => {
        const baseUrl = process.env.M2_BASE_URL;
        if (!baseUrl) {
        throw new Error('M2_BASE_URL environment variable is not set');
        }

        try {
        // Navigate to admin dashboard
        await page.goto(`${baseUrl}/admin/admin/dashboard`);

        // Wait for the admin dashboard to be ready
        await page.locator('.admin__menu').waitFor();
        console.log('Page loaded');

        // Initialize Admin class
        const admin = new Admin(page);

        // Navigate to Klaviyo Newsletter configuration using Admin class method
        await admin.navigateToKlaviyoNewsletterConfig(admin.page);

        const honorKlaviyoConsentValue = await page.getByLabel('Yes, use the Klaviyo settings for this list').isChecked();
        console.log('Honor Klaviyo consent value:', honorKlaviyoConsentValue);
        // Enable honor Klaviyo consent setting
        if (!honorKlaviyoConsentValue) {
            await page.getByLabel('Yes, use the Klaviyo settings for this list').check();

            // Wait for the checkbox to be fully checked
            await page.waitForTimeout(1000);

            // Wait for save button to be visible and clickable
            const saveButton = page.getByRole('button', { name: 'Save Config' });
            await saveButton.waitFor({ state: 'visible', timeout: 5000 });

            // Try to click with a longer timeout
            await saveButton.click({ timeout: 5000 });

            // Wait for the click to register and success message
            await page.locator('.message-success').waitFor();
        }

        // Log success for debugging
        console.log('Honor Klaviyo consent setting enabled');
        } catch (error) {
        console.error('Error in beforeEach:', error);
        throw error;
        }
    });

  test('should successfully subscribe a user via footer form and validate via Klaviyo API', async ({ page }) => {
    const baseUrl = process.env.M2_BASE_URL;
    const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
    const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
    const testEmail = generateEmail();

    if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
      throw new Error('Required environment variables are not set');
    }

    // Navigate to the homepage
    await page.goto(baseUrl);

    // Fill in the newsletter subscription form in the footer
    await page.fill('#newsletter', testEmail);

    // Click the subscribe button
    await page.click('button[title="Subscribe"]');

    // Wait for success message
    await page.locator('.message-success').waitFor();

    // Use exponential backoff to check for profile
    const profiles = await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
        if (results.length === 0) {
          throw new Error('Profile not found yet');
        }
        return results;
      },
      {
        numOfAttempts: 10,
        retry: (error, attemptNumber) => {
          console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
          return true;
        }
      }
    );

    // Assert that the profile exists and is subscribed
    expect(profiles).toBeDefined();
    expect(profiles.length).toBe(1);
    expect(profiles[0].attributes.email).toBe(testEmail);
    expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('SUBSCRIBED');

    // Log success for debugging
    console.log(`Successfully subscribed ${testEmail} to newsletter`);
  });

  test('should successfully subscribe a user via account creation page and validate via Klaviyo API', async ({ page }) => {
    const baseUrl = process.env.M2_BASE_URL;
    const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
    const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
    const testEmail = generateEmail();
    const testPassword = 'Test123!@#';

    if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
      throw new Error('Required environment variables are not set');
    }

    // Navigate to the account creation page
    await page.goto(`${baseUrl}/customer/account/create/`);

    // Fill in the registration form
    await page.fill('#firstname', 'Test');
    await page.fill('#lastname', 'User');
    await page.fill('#email_address', testEmail);
    await page.fill('#password', testPassword);
    await page.fill('#password-confirmation', testPassword);

    // Check the newsletter subscription checkbox
    await page.check('#is_subscribed');

    // Submit the form
    await page.click('button[title="Create an Account"]');

    // Wait for successful registration
    await page.locator('.message-success').waitFor();

    // Use exponential backoff to check for profile
    const profiles = await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
        if (results.length === 0) {
          throw new Error('Profile not found yet');
        }
        return results;
      },
      {
        numOfAttempts: 10,
        retry: (error, attemptNumber)  => {
          console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
          return true;
        }
      }
    );

    // Assert that the profile exists and is subscribed
    expect(profiles).toBeDefined();
    expect(profiles.length).toBe(1);
    expect(profiles[0].attributes.email).toBe(testEmail);
    expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('SUBSCRIBED');
    expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);
    // Log success for debugging
    console.log(`Successfully subscribed ${testEmail} to newsletter via account creation`);
  });

  test('should successfully unsubscribe a user via newsletter management page and validate via Klaviyo API', async ({ page }) => {
    const baseUrl = process.env.M2_BASE_URL;
    const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
    const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
    const testEmail = generateEmail();
    const testPassword = 'Test123!@#';

    if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
      throw new Error('Required environment variables are not set');
    }

    // First create an account with newsletter subscription
    await page.goto(`${baseUrl}/customer/account/create/`);
    await page.fill('#firstname', 'Test');
    await page.fill('#lastname', 'User');
    await page.fill('#email_address', testEmail);
    await page.fill('#password', testPassword);
    await page.fill('#password-confirmation', testPassword);
    await page.check('#is_subscribed');
    await page.click('button[title="Create an Account"]');
    await page.locator('.message-success').waitFor();

    // Wait for initial subscription to be processed
    await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
        if (results.length === 0) {
          throw new Error('Profile not found yet');
        }
      },
      {
        numOfAttempts: 10,
        retry: (error, attemptNumber) => {
          console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
          return true;
        }
      }
    );

    // Navigate to newsletter management page
    await page.goto(`${baseUrl}/newsletter/manage/`);

    // Uncheck the newsletter subscription checkbox
    await page.uncheck('#subscription');

    // Save the changes
    await page.click('button[title="Save"]');

    // Wait for success message
    await page.locator('.message-success').waitFor();

    // Use exponential backoff to check for updated profile
    const profiles = await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
        if (results.length === 0) {
          throw new Error('Profile not found yet');
        }
        const profile = results[0];
        // Check if email marketing subscription is unsubscribed
        if (profile.attributes.subscriptions?.email?.marketing?.consent !== 'UNSUBSCRIBED') {
          throw new Error('Profile still has email marketing consent');
        }
        return results;
      },
      {
        numOfAttempts: 10,
        retry: (error, attemptNumber) => {
          console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
          return true;
        }
      }
    );

    // Assert that the profile exists and is unsubscribed
    expect(profiles).toBeDefined();
    expect(profiles.length).toBe(1);
    expect(profiles[0].attributes.email).toBe(testEmail);
    expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('UNSUBSCRIBED');

    // Log success for debugging
    console.log(`Successfully unsubscribed ${testEmail} from newsletter via newsletter management page`);
  });
});

/**
 * Tests the Klaviyo profile subscription and list relationship endpoints when the option to honor Klaviyo consent is disabled
 * Endpoints tested:
 * - POST /profiles/ - Creates a profile with NEVER_SUBSCRIBED consent
 * - GET /profiles/ - Retrieves profile data to validate consent state
 * - GET /profile-list-relationships/ - Validates profile is added to lists
 * Tests various subscription methods (footer form, account creation) and unsubscription
 * to ensure proper consent state management and list relationships when the option to honor Klaviyo consent is disabled
 *
 * @see https://developers.klaviyo.com/en/reference/create-profile
 * @see https://developers.klaviyo.com/en/reference/get-profiles
 * @see https://developers.klaviyo.com/en/reference/get-profile-list-relationships
 */
test.describe('Profile Subscription - do not honor Klaviyo consent', () => {
    test.beforeEach(async ({ page }) => {
      const baseUrl = process.env.M2_BASE_URL;
      if (!baseUrl) {
        throw new Error('M2_BASE_URL environment variable is not set');
      }

      try {
        // Navigate to admin dashboard
        await page.goto(`${baseUrl}/admin/admin/dashboard`);

        // Wait for the admin dashboard to be ready
        await page.locator('.admin__menu').waitFor();
        console.log('Page loaded');

        // Initialize Admin class
        const admin = new Admin(page);

        // Navigate to Klaviyo Newsletter configuration using Admin class method
        await admin.navigateToKlaviyoNewsletterConfig(admin.page);

        const honorKlaviyoConsentValue = await page.getByLabel('Yes, use the Klaviyo settings for this list').isChecked();
        console.log('Honor Klaviyo consent value:', honorKlaviyoConsentValue);
        // Enable honor Klaviyo consent setting
        if (honorKlaviyoConsentValue) {
          await page.getByLabel('No, do not send opt-in emails from Klaviyo').check();

          // Wait for the checkbox to be fully checked
          await page.waitForTimeout(1000);

          // Wait for save button to be visible and clickable
          const saveButton = page.getByRole('button', { name: 'Save Config' });
          await saveButton.waitFor({ state: 'visible', timeout: 5000 });

          // Try to click with a longer timeout
          await saveButton.click({ timeout: 5000 });

          // Wait for the click to register and success message
          await page.locator('.message-success').waitFor();
          await page.getByLabel('Yes, use the Klaviyo settings for this list').waitFor();
        }

        // Log success for debugging
        console.log('Honor Klaviyo consent setting disabled');
      } catch (error) {
        console.error('Error in beforeEach:', error);
        throw error;
      }
    });

    test('Add a profile to a list via footer form and validate via Klaviyo API', async ({ page }) => {
        const baseUrl = process.env.M2_BASE_URL;
        const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
        const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
        const testEmail = generateEmail();

        if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
          throw new Error('Required environment variables are not set');
        }

        // Navigate to the homepage
        await page.goto(baseUrl);

        // Fill in the newsletter subscription form in the footer
        await page.fill('#newsletter', testEmail);

        // Click the subscribe button
        await page.click('button[title="Subscribe"]');

        // Wait for success message
        await page.locator('.message-success').waitFor();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
            if (results.length === 0) {
              throw new Error('Profile not found yet');
            }
            return results;
          },
          {
            numOfAttempts: 10,
            retry: (error, attemptNumber) => {
              console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
              return true;
            }
          }
        );

        // Assert that the profile exists and is subscribed
        expect(profiles).toBeDefined();
        expect(profiles.length).toBe(1);
        expect(profiles[0].attributes.email).toBe(testEmail);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(klaviyoPrivateKey, klaviyoV3Url, profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added profile ${testEmail} to list via footer form`);
    });

    test('Add a profile to a list via account creation and validate via Klaviyo API', async ({ page }) => {
        const baseUrl = process.env.M2_BASE_URL;
        const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
        const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
        const testEmail = generateEmail();
        const testPassword = 'Test123!@#';

        if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
          throw new Error('Required environment variables are not set');
        }

        // Navigate to the account creation page
        await page.goto(`${baseUrl}/customer/account/create/`);

        // Fill in the registration form
        await page.fill('#firstname', 'Test');
        await page.fill('#lastname', 'User');
        await page.fill('#email_address', testEmail);
        await page.fill('#password', testPassword);
        await page.fill('#password-confirmation', testPassword);

        // Check the newsletter subscription checkbox
        await page.check('#is_subscribed');

        // Submit the form
        await page.click('button[title="Create an Account"]');

        // Wait for successful registration
        await page.locator('.message-success').waitFor();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
            if (results.length === 0) {
              throw new Error('Profile not found yet');
            }
            return results;
          },
          {
            numOfAttempts: 10,
            retry: (error, attemptNumber) => {
              console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
              return true;
            }
          }
        );

        // Assert that the profile exists and is never subscribed
        expect(profiles).toBeDefined();
        expect(profiles.length).toBe(1);
        expect(profiles[0].attributes.email).toBe(testEmail);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(klaviyoPrivateKey, klaviyoV3Url, profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added profile ${testEmail} to list via account creation`);
    });

    test('should successfully unsubscribe a user via newsletter management page and validate via Klaviyo API', async ({ page }) => {
        const baseUrl = process.env.M2_BASE_URL;
        const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
        const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
        const testEmail = generateEmail();
        const testPassword = 'Test123!@#';

        if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
          throw new Error('Required environment variables are not set');
        }

        // First create an account with newsletter subscription
        await page.goto(`${baseUrl}/customer/account/create/`);
        await page.fill('#firstname', 'Test');
        await page.fill('#lastname', 'User');
        await page.fill('#email_address', testEmail);
        await page.fill('#password', testPassword);
        await page.fill('#password-confirmation', testPassword);
        await page.check('#is_subscribed');
        await page.click('button[title="Create an Account"]');
        await page.locator('.message-success').waitFor();

        // Wait for initial subscription to be processed
        await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
            if (results.length === 0) {
              throw new Error('Profile not found yet');
            }
          },
          {
            numOfAttempts: 10,
            retry: (error, attemptNumber) => {
              console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
              return true;
            }
          }
        );

        // Navigate to newsletter management page
        await page.goto(`${baseUrl}/newsletter/manage/`);

        // Uncheck the newsletter subscription checkbox
        await page.uncheck('#subscription');

        // Save changes
        await page.click('button[title="Save"]');

        // Wait for success message
        await page.locator('.message-success').waitFor();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
            if (results.length === 0) {
              throw new Error('Profile not found yet');
            }
            const profile = results[0];
            // Check if email marketing subscription is unsubscribed
            if (profile.attributes.subscriptions?.email?.marketing?.consent !== 'UNSUBSCRIBED') {
              throw new Error('Profile still has email marketing consent');
            }
            return results;
          },
          {
            numOfAttempts: 10,
            retry: (error, attemptNumber) => {
              console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
              return true;
            }
          }
        );

        // Assert that the profile exists and is unsubscribed
        expect(profiles).toBeDefined();
        expect(profiles.length).toBe(1);
        expect(profiles[0].attributes.email).toBe(testEmail);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('UNSUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(false);

        // Log success for debugging
        console.log(`Successfully unsubscribed ${testEmail} from newsletter via newsletter management page`);
    });

    test('Add an existing profile to a list via footer form and validate via Klaviyo API', async ({ page }) => {
        const baseUrl = process.env.M2_BASE_URL;
        const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
        const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
        const testEmail = generateEmail();

        if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
          throw new Error('Required environment variables are not set');
        }

        // First create the profile in Klaviyo without any subscription info
        await createProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);

        // Navigate to the homepage
        await page.goto(baseUrl);

        // Fill in the newsletter subscription form in the footer
        await page.fill('#newsletter', testEmail);

        // Click the subscribe button
        await page.click('button[title="Subscribe"]');

        // Wait for success message
        await page.locator('.message-success').waitFor();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
            if (results.length === 0) {
              throw new Error('Profile not found yet');
            }
            return results;
          },
          {
            numOfAttempts: 10,
            retry: (error, attemptNumber) => {
              console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
              return true;
            }
          }
        );

        // Assert that the profile exists and is subscribed
        expect(profiles).toBeDefined();
        expect(profiles.length).toBe(1);
        expect(profiles[0].attributes.email).toBe(testEmail);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(klaviyoPrivateKey, klaviyoV3Url, profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added existing profile ${testEmail} to list via footer form`);
    });

    test('Add an existing profile to a list via account creation and validate via Klaviyo API', async ({ page }) => {
        const baseUrl = process.env.M2_BASE_URL;
        const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
        const klaviyoV3Url = process.env.KLAVIYO_V3_URL;
        const testEmail = generateEmail();
        const testPassword = 'Test123!@#';

        if (!baseUrl || !klaviyoPrivateKey || !klaviyoV3Url) {
          throw new Error('Required environment variables are not set');
        }

        // First create the profile in Klaviyo without any subscription info
        await createProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);

        // Navigate to the account creation page
        await page.goto(`${baseUrl}/customer/account/create/`);

        // Fill in the registration form
        await page.fill('#firstname', 'Test');
        await page.fill('#lastname', 'User');
        await page.fill('#email_address', testEmail);
        await page.fill('#password', testPassword);
        await page.fill('#password-confirmation', testPassword);

        // Check the newsletter subscription checkbox
        await page.check('#is_subscribed');

        // Submit the form
        await page.click('button[title="Create an Account"]');

        // Wait for successful registration
        await page.locator('.message-success').waitFor();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(klaviyoPrivateKey, klaviyoV3Url, testEmail);
            if (results.length === 0) {
              throw new Error('Profile not found yet');
            }
            return results;
          },
          {
            numOfAttempts: 10,
            retry: (error, attemptNumber) => {
              console.log(`Attempt ${attemptNumber} failed. Error: ${error.message}`);
              return true;
            }
          }
        );

        // Assert that the profile exists and is subscribed
        expect(profiles).toBeDefined();
        expect(profiles.length).toBe(1);
        expect(profiles[0].attributes.email).toBe(testEmail);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(klaviyoPrivateKey, klaviyoV3Url, profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added existing profile ${testEmail} to list via account creation`);
    });
});