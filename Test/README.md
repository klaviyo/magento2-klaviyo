# Test Automation

## Introduction

The following frameworks and packages are used for testing

- [Playwright](https://playwright.dev/)

### Notes:
- Playwright will output screenshots and videos to help debugging test failures*

- Debugging tests is made easy using the Playwright logger. Please refer to the `logger` block within the [Playwright Config](./playwright.config.js) and the [following documentation](https://playwright.dev/docs/api/class-logger) for more details

## Prerequisites

`node v18.16.x`

`.env` file at the root of this directory with the following variables. Values can be directly copied from the Playwright Magento 2 Test Site 1password entry. See [example](env.example) file

```
KLAVIYO_PRIVATE_KEY=pk_...
KLAVIYO_V3_URL=a.klaviyo.com/api
METRIC_ID_ATC=AAAAAA
M2_BASE_URL=https://www.example.com
M2_ADMIN_USERNAME=someone@example.com
M2_ADMIN_PASSWORD=astrongpassword
```
where METRIC_ID_ATC is the metric ID for the "Added To Cart" event in Klaviyo.

## Instructions
From this directory
- `npm install`
- `npx playwright test`

## Coverage

End to End (E2E) tests focus on API requests to Klaviyo to ensure revision updates do not break functionality.

Functionally, these test cases cover:

* Lists
    * Lists are fetched when correct klaviyo credentials are supplied in admin
    * An appropriate error shows in Admin if we are unable to fetch lists
* Subscriptions
    * Extension is configured to honor DOI setting:
        * When a customer signs up to the newsletter via the Account Registration Page, the profile is subscribed to the list.
        * When a customer signs up to the newsletter via the footer form, the profile is subscribed to the list.
        * When a customer removes their subscription via the Account management page, the profile is unsubscribed from the list.
        * When a customer adds their subscription via the Account management page, the profile is subscribed to the list.
    * Extension is configured to not honor DOI setting:
        * When a customer signs up to the newsletter via the Account Registration Page, the profile is added to list.
        * When a customer signs up to the newsletter via the footer form, the profile is added to list.
        * When a customer removes their subscription via the Account management page, the profile is removed from the list.
        * When a customer removes their subscription via the Account management page, the profile is added to the list.
* Events
    * When a user enters a product page, a Viewed Product event is tracked.
    * When a user adds an item to their cart, an Added To Cart event is tracke within 5 minutes.