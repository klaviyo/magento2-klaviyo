const { chromium } = require('@playwright/test');
const dotenv = require('dotenv');
const path = require('path');
const Admin = require('./e2e/locators/admin');
// Load environment variables from .env file
dotenv.config({ path: path.resolve(__dirname, '.env') });

async function globalSetup(config) {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  // Get credentials from environment variables
  const username = process.env.M2_ADMIN_USERNAME;
  const password = process.env.M2_ADMIN_PASSWORD;
  const baseUrl = process.env.M2_BASE_URL;

  if (!username || !password || !baseUrl) {
    throw new Error('Required environment variables are not set. Please check your .env file.');
  }

  // Navigate to admin login page
  await page.goto(`${baseUrl}/admin`);

  // Fill in login form
  await page.fill('#username', username);
  await page.fill('#login', password);

  // Click login button
  await page.getByRole('button', { name: 'Sign in' }).click();

  // Wait for successful login
  await page.waitForSelector('.admin-user-account-text');

  // Save the authenticated state
  await page.context().storageState({ path: path.join(__dirname, 'playwright/.auth/admin.json') });

  console.log("CACHE MANAGEMENT");
  const admin = new Admin(page);
  await admin.navigateToCacheManagement();
  await admin.refreshConfigCache();


  await browser.close();
}

module.exports = globalSetup;