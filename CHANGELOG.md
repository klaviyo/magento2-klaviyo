# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

<!-- BEGIN RELEASE NOTES -->
### [Unreleased]

### [4.1.3] - 2024-03-29

#### Fixed
- BUGPORT-1750: unassigned $response variable from api call during list subscription

### [4.1.2] - 2024-01-31

#### Fixed
- Updated customer.js to correctly handle promise returned from isIdentified
- Updated KlaviyoV3Api to handle new response patterns returned from V3 APIs
- Fixed issue with Added to Cart events not syncing for multi-site configurations
- Fixed Added to Cart observer to check for private key instead of public key
- Fixed error handling from V3 APIs, logs out the error message instead of the stack trace on retries.

### [4.1.1] - 2023-12-12

#### Added
- Added name to initialize block in checkout
- Added ACL rules for Klaviyo extension configuration.

#### Changed
- Changed system.xml sort to stop becoming default instead of core options

#### Fixed
- Updated getKlaviyoLists() exception handling to properly print error message.
- Paginate to get all lists for account.
- Updated response handling in KlaviyoV3Sdk to not retry for falsey responses from curl_exec.

### [4.1.0] - 2023-09-29

#### Added
- New Klaviyo onsite object
- New X-Klaviyo-User-Agent to headers to collect plugin usage meta data
- Added support for Klaviyo V3 API

#### Removed
- Support for V2 APIs: /track and /identify
- Removed _learnq onsite object in favor of klaviyo object

### [4.0.12] - 2023-07-20

#### Fixed
- Fixed issue when viewed product block on product page caused slower response time
- Fixed issue for newletter module where new subscriptions weren't being sent to klaviyo via magento forms for stores using Magento version < 2.4.3

### [4.0.11] - 2023-04-07

#### Changed
- Fixed bug where historical sync wouldn't run for index of 0

#### Fixed
- Fixed bug in checkout where shipping information update caused report error if email or sms consent where activated
- Fixed PHP 8.2 incompatability with dynamic properties

### [4.0.10] - 2023-03-01

#### Added
- Continuous integration to validate pre commit, validate our versioned files, and prepare for testing
- Continuous deployment to generate a new release when PRs are merged into the 'stable/**' release branches.

### Removed
- Empty test files

#### Changed
- Added precommit to the repository and formatted all PHP files to PSR12 style.
- Set the newsletter subscription source to Magento 2

#### Fixed
- Fixed bug in NewsletterSubscribeObserver where customers with an unconfirmed site account were being unsubscribed

### [4.0.9] - 2023-01-03
### Changed
- Updated default SMS consent language
### Fixed
- Fixed bug in logging for truncated payloads in the kl_syncs table.

### [4.0.8] - 2022-11-10
### Fixed
- Fixed bug where cleanup cron wasn't referencing correct method name.

### [4.0.7] - 2022-11-01
### Added
- Add a name to the Klaviyo\Reclaim\Block\Initialize block, so it can be moved around via a layout xml
### Fixed
- Fixed bug in Observer/SalesQuoteProductAddAfter.php passing null value to stripslashes
- Fixed bug in Block/Catalog/Product/ViewedProduct.php passing null value to number_format
- Fixed issue when Controller/Checkout/Reload.php was loading backend classes on frontend
- Fixes for Added to Cart: adds error handling to Added to Cart event processing, enforces payload size to 65k characters, adjusts cleanup crons to include failed syncs

### [4.0.6] - 2022-09-19
#### Fixed
- Updated PHPDoc parameters for productinspector method in Api/ReclaimInterface.php to match signature.

### [4.0.5] - 2022-07-28
#### Fixed
- Fix 404 AJAX Request on /cart/reclaim/checkout/reload
- Fixed newsletter signup on account creation. Users will now be subscribed if they check the checkbox on account registration.

### [4.0.4] - 2022-05-24

### Deprecated
- Skipped 4.0.3 due to cancelled extension in magento marketplace

#### Fixed
- Moved webhooks url to async tier
- Removed product descriptions from Added to Cart payloads
- Add indexType to db_schema.xml
- Added style-src and forms url to csp_whitelist.xml
### [4.0.2] - 2022-03-15

#### Fixed
- Updated Added to Cart track request to use POST to accommodate large payloads
- Initialized observerAtcPayload to fix Undefined property error

### [4.0.1] - 2022-01-28

#### Fixed
- Add store scoping to Track Requests for Added to Cart

### [4.0.0] - 2022-01-20

#### Added
- Declarative schema, patch data scripts available for backward compatibility

#### Removed
- InstallData/UpgradeData and InstallSchema/UpgradeSchema scripts

#### Fixed
- Whitelisting Klaviyo onsite scripts
- Identifying logged-in users correctly

### [4.0.0-beta] - 2021-12-15

#### Added
- Added to Cart metric collection

### [3.0.11] - 2021-12-21

#### Fixed
- Error affecting customers using Magento's embedded footer forms

### [3.0.10] - 2021-11-10

#### Changed
- SMS Consent default language

### [3.0.9] - 2021-09-21

#### Fixed
- SMS Consent checkbox for logged in users with default address set
- URL construction works when store URL has subdirectories
- Remove reference to deprecated _learnq functionality

### [3.0.8] - 2021-09-02

#### Fixed
- Fixes infinite loop issue produced by [Magento bug](https://github.com/magento/magento2/issues/33675)

### [3.0.7] - 2021-08-27

#### Fixed
- Right trim trailing slash from Custom Media Url setting from Klaviyo Extension
- Properly escape the public api for onsite tag
- Handle newsletter subscriptions in all areas
- Fixing bug with newsletter subscribes for anonymous users (not registered accounts)

### [3.0.6] - 2021-07-01

#### Added
- Add an ability to pass the Store ID during track event

#### Fixed
- Keep existing extension attributes when extending shipping payload request

### [3.0.5] - 2021-06-08

#### Added
- Updates composer requirement to use module quote >=101.1.3
- Add ability to retrieve config values for specified store id

#### Fixed
- Fix issue with newsletter subscription

### [3.0.4] - 2021-06-08

#### Fixed
- Use `Magento\Framework\Api\SearchResults` to support Magento 2 versions 2.3.0 to 2.3.3

### [3.0.3] - 2021-06-01

#### Added
- OAuth observer to create Magento2 Integration OAuth configuration

#### Removed
- Section about Setup Klaviyo User

### [3.0.2] - 2021-05-26

#### Fixed
- Missing quote in module

### [3.0.1] - 2021-05-26

#### Fixed
- Typo in cart rebuild constructor di

### [3.0.0] - 2021-05-25

#### Added
- Only support Magento 2.3.* +

#### Fixed
- Utilize masked quote ids.
- Extend cart/search getList api to contain masked Ids.

### [2.2.0] - 2021-05-17

#### Fixed
- Update checkout to not use quote for rebuilding

### [2.1.1] - 2021-05-17

#### Fixed
- Use store ids instead of website ids in the ProductDeleteBefore Observer
- Check for versions older than 2.0.0 in UpgradeSchema

### [2.1.0] - 2021-03-22

#### Added
- SMS Consent at checkout
- Email consent at checkout
- Consent at checkout admin tab

#### Fixed
- Email consent now recorded when Klaviyo list opt-in settings are used
- Escaped html for public api key

### [2.0.0] - 2021-01-11

#### Added
- Product delete observer webhook to send to Klaviyo catalog
- Webhook secret form field for webhook validation

#### Changed
- Removed csp setting
- Removed csp mode from config.xml
- Added a.fast.klaviyo.com to img-src csp whitelist


### [1.2.4] - 2020-12-01

#### Added
- Create CHANGELOG.md

#### Changed
- Update to README.md to share contribution guidelines
- Use List API V2 for fetching newsletter lists

#### Fixed
- Remove JQuery UI as a dependency since it is unused


### [1.2.3] - 2020-10-09

##### Changed
- Removes unused variable and DI from Reclaim.php
- CSP now uses report-only mode

<!-- END RELEASE NOTES -->
<!-- BEGIN LINKS -->
[Unreleased]: https://github.com/klaviyo/magento2-klaviyo/compare/4.1.3...HEAD
[4.1.3]: https://github.com/klaviyo/magento2-klaviyo/compare/4.1.2...4.1.3
[4.1.2]: https://github.com/klaviyo/magento2-klaviyo/compare/4.1.1...4.1.2
[4.1.1]: https://github.com/klaviyo/magento2-klaviyo/compare/4.1.0...4.1.1
[4.1.0]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.12...4.1.0
[4.0.12]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.11...4.0.12
[4.0.11]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.10...4.0.11
[4.0.10]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.9...4.0.10
[4.0.9]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.8...4.0.9
[4.0.8]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.7...4.0.8
[4.0.7]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.6...4.0.7
[4.0.6]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.5...4.0.6
[4.0.5]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.4...4.0.5
[4.0.4]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.2...4.0.4
[4.0.2]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.1...4.0.2
[4.0.1]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/klaviyo/magento2-klaviyo/compare/4.0.0-beta...4.0.0
[4.0.0-beta]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.11...4.0.0-beta
[3.0.11]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.10...3.0.11
[3.0.10]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.9...3.0.10
[3.0.9]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.8...3.0.9
[3.0.8]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.7...3.0.8
[3.0.7]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.6...3.0.7
[3.0.6]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.5...3.0.6
[3.0.5]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.4...3.0.5
[3.0.4]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.3...3.0.4
[3.0.3]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.2...3.0.3
[3.0.2]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/klaviyo/magento2-klaviyo/compare/2.2.0...3.0.0
[2.2.0]: https://github.com/klaviyo/magento2-klaviyo/compare/2.1.1...2.2.0
[2.1.1]: https://github.com/klaviyo/magento2-klaviyo/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/klaviyo/magento2-klaviyo/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/klaviyo/magento2-klaviyo/compare/1.2.4...2.0.0
[1.2.4]: https://github.com/klaviyo/magento2-klaviyo/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/klaviyo/magento2-klaviyo/compare/1.2.2...1.2.3
<!-- END LINKS -->

#### NOTE
- The CHANGELOG was created on 2020-11-20 and does not contain information about earlier releases
