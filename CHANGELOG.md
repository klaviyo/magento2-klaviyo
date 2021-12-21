# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

### [Unreleased]

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

#### Fixes
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


[Unreleased]: https://github.com/klaviyo/magento2-klaviyo/compare/3.0.11...HEAD
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


#### NOTE
- The CHANGELOG was created on 2020-11-20 and does not contain information about earlier releases
