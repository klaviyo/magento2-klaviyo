const { test, expect } = require('@playwright/test');
const { backOff } = require('exponential-backoff');
const Admin = require('../locators/admin');
const Storefront = require('../locators/storefront');
const { createProfileInKlaviyo, checkProfileInKlaviyo, checkProfileListRelationships } = require('../utils/klaviyo-api');

test.describe.configure({ mode: 'serial' }); // This is necessary to ensure that the Klaviyo newsletter config is updated before running the tests

/**
 * Tests the Klaviyo profile subscription functionality when honor Klaviyo consent is enabled
 *
 * Tests the following Klaviyo API functionality:
 * - Profile creation with SUBSCRIBED consent via footer form
 * - Profile creation with SUBSCRIBED consent via account creation
 * - Profile consent updates via newsletter management
 *
 * @see https://developers.klaviyo.com/en/reference/bulk_subscribe_profiles
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

          const admin = new Admin(page);

          await admin.navigateToKlaviyoNewsletterConfig();
          await admin.updateKlaviyoNewsletterConfig('Yes, use the Klaviyo settings for this list');

          // Log success for debugging
          console.log('Honor Klaviyo consent setting enabled');
        } catch (error) {
          console.error('Error updating Klaviyo newsletter config:', error);
          throw error;
        }
    });

  test('should successfully subscribe a user via footer form and validate via Klaviyo API', async ({ page }) => {
    const storefront = new Storefront(page);

    // Navigate to the homepage and fill out the newsletter subscription form
    await storefront.goToHomepage();
    await storefront.fillOutNewsletterFooterForm();

    // Use exponential backoff to check for profile
    const profiles = await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(storefront.email);
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
    expect(profiles[0].attributes.email).toBe(storefront.email);
    expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('SUBSCRIBED');

    // Log success for debugging
    console.log(`Successfully subscribed ${storefront.email} to newsletter`);
  });

  test('should successfully subscribe a user via account creation page and validate via Klaviyo API', async ({ page }) => {
    const storefront = new Storefront(page);
    await storefront.goToAccountCreationPage();
    await storefront.fillOutAccountCreationForm();
    await storefront.checkNewsletterSubscriptionCheckbox();
    await storefront.submitAccountCreationForm();

    // Use exponential backoff to check for profile
    const profiles = await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(storefront.email);
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
    expect(profiles[0].attributes.email).toBe(storefront.email);
    expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('SUBSCRIBED');
    expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);
    // Log success for debugging
    console.log(`Successfully subscribed ${storefront.email} to newsletter via account creation`);
  });

  test('should successfully unsubscribe a user via newsletter management page and validate via Klaviyo API', async ({ page }) => {
    const storefront = new Storefront(page);

    // First create an account with newsletter subscription
    await storefront.goToAccountCreationPage();
    await storefront.fillOutAccountCreationForm();
    await storefront.checkNewsletterSubscriptionCheckbox();
    await storefront.submitAccountCreationForm();

    // Wait for initial subscription to be processed
    await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(storefront.email);
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
    await storefront.goToAccountPageAndUnsubscribeFromNewsletter();

    // Use exponential backoff to check for updated profile
    const profiles = await backOff(
      async () => {
        const results = await checkProfileInKlaviyo(storefront.email);
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
    expect(profiles[0].attributes.email).toBe(storefront.email);
    expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('UNSUBSCRIBED');

    // Log success for debugging
    console.log(`Successfully unsubscribed ${storefront.email} from newsletter via newsletter management page`);
  });
});

/**
 * Tests the Klaviyo profile subscription functionality when honor Klaviyo consent is disabled
 *
 * Tests the following Klaviyo API functionality:
 * - Profile creation and list addition for new subscribers
 * - List addition for existing profiles
 * - List removal for unsubscribers
 *
 * @see https://developers.klaviyo.com/en/reference/get_profiles
 * @see https://developers.klaviyo.com/en/reference/create_profile
 * @see https://developers.klaviyo.com/en/reference/add_profiles_to_list
 */
test.describe('Profile Subscription - do not honor Klaviyo consent', () => {
    test.beforeEach(async ({ page }) => {
      const baseUrl = process.env.M2_BASE_URL;

      try {
        // Navigate to admin dashboard
        await page.goto(`${baseUrl}/admin/admin/dashboard`);

        // Wait for the admin dashboard to be ready
        await page.locator('.admin__menu').waitFor();
        console.log('Page loaded');

        // Initialize Admin class
        const admin = new Admin(page);

        // Navigate to Klaviyo Newsletter configuration using Admin class method
        await admin.navigateToKlaviyoNewsletterConfig();
        await admin.updateKlaviyoNewsletterConfig('No, do not send opt-in emails from Klaviyo');

        // Log success for debugging
        console.log('Honor Klaviyo consent setting disabled');
      } catch (error) {
        console.error('Error updating Klaviyo newsletter config:', error);
        throw error;
      }
    });

    test('Add a profile to a list via footer form and validate via Klaviyo API', async ({ page }) => {
        const storefront = new Storefront(page);

        // Navigate to the homepage and fill out the newsletter subscription form
        await storefront.goToHomepage();
        await storefront.fillOutNewsletterFooterForm();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(storefront.email);
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
        expect(profiles[0].attributes.email).toBe(storefront.email);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added profile ${storefront.email} to list via footer form`);
    });

    test('Add a profile to a list via account creation and validate via Klaviyo API', async ({ page }) => {
        const storefront = new Storefront(page);
        await storefront.goToAccountCreationPage();
        await storefront.fillOutAccountCreationForm();
        await storefront.checkNewsletterSubscriptionCheckbox();
        await storefront.submitAccountCreationForm();


        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(storefront.email);
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
        expect(profiles[0].attributes.email).toBe(storefront.email);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added profile ${storefront.email} to list via account creation`);
    });

    test('should successfully unsubscribe a user via newsletter management page and validate via Klaviyo API', async ({ page }) => {
        const storefront = new Storefront(page);

        // First create an account with newsletter subscription
        await storefront.goToAccountCreationPage();
        await storefront.fillOutAccountCreationForm();
        await storefront.checkNewsletterSubscriptionCheckbox();
        await storefront.submitAccountCreationForm();

        // Wait for initial subscription to be processed
        await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(storefront.email);
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

        // Navigate to account page and unsubscribe from newsletter
        await storefront.goToAccountPageAndUnsubscribeFromNewsletter();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(storefront.email);
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
        expect(profiles[0].attributes.email).toBe(storefront.email);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('UNSUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(false);

        // Log success for debugging
        console.log(`Successfully unsubscribed ${storefront.email} from newsletter via newsletter management page`);
    });

    test('Add an existing profile to a list via footer form and validate via Klaviyo API', async ({ page }) => {
        const storefront = new Storefront(page);

        // First create the profile in Klaviyo without any subscription info
        await createProfileInKlaviyo(storefront.email);

        // Navigate to the homepage and fill out the newsletter subscription form
        await storefront.goToHomepage();
        await storefront.fillOutNewsletterFooterForm();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(storefront.email);
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
        expect(profiles[0].attributes.email).toBe(storefront.email);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added existing profile ${storefront.email} to list via footer form`);
    });

    test('Add an existing profile to a list via account creation and validate via Klaviyo API', async ({ page }) => {
        const storefront = new Storefront(page);

        // First create the profile in Klaviyo without any subscription info
        await createProfileInKlaviyo(storefront.email);

        // fill out account creation form and submit
        await storefront.goToAccountCreationPage();
        await storefront.fillOutAccountCreationForm();
        await storefront.checkNewsletterSubscriptionCheckbox();
        await storefront.submitAccountCreationForm();

        // Use exponential backoff to check for profile
        const profiles = await backOff(
          async () => {
            const results = await checkProfileInKlaviyo(storefront.email);
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
        expect(profiles[0].attributes.email).toBe(storefront.email);
        expect(profiles[0].attributes.subscriptions.email.marketing.consent).toBe('NEVER_SUBSCRIBED');
        expect(profiles[0].attributes.subscriptions.email.marketing.can_receive_email_marketing).toBe(true);

        // Check list relationships
        const listRelationships = await checkProfileListRelationships(profiles[0].id);
        expect(listRelationships).toBeDefined();
        expect(listRelationships.length).toBeGreaterThan(0);
        expect(listRelationships[0].type).toBe('list');

        // Log success for debugging
        console.log(`Successfully added existing profile ${storefront.email} to list via account creation`);
    });
});