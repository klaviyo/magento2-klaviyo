# Klaviyo for Magento 2

Klaviyo extension for Magento 2. Allows pushing newsletters to Klaviyo's platform and more.

## Features

- **Identifies users**
  - Go to sign in or create account
  - Sign in or create account
  - Go to another page and inspect source for identify call with email, firstname, and lastname

- **Tracks viewing an item (catalog product)**
  - Only will work if user is signed in
  - Inspect page source, find `learnq` snippet and see what PHP is echoing out
  - Should be a `Viewed Product` track call with product details

- **Saves checkout emails**
  - Add some items to your cart and go to checkout page
  - In console see: `Klaviyo_Reclaim - Binding to #customer-email`
  - Change email
  - See `Klaviyo_Reclaim - Quote updated with customer email: your.name@klaviyo.com`
  - Make sure AJAX call comes back with checkout / quoute JSON

- **Sync Newsletter (Un)Subscribes to a Klaviyo List**
  - This feature covers workflows where a Customer (un)subscribes from the following places:
    - Box at the bottom of every page
    - On account creation
    - Through their account settings
    - Through Customer Newsletter settings on the admin side

- **Abandoned Cart**
  - Given a quote ID, a URL can be crafted that will load a Customer's cart with a quote

- **OAuth Integration Configuration**
  - Automates the creation and configuration of the OAuth Integration:
    - Callback URL
    - Identity Link URL
    - Resource Access permissions

## Prerequisites

Magento 2

### Install

  -  log into the Magento 2 server and cd into the root directory of the Magento app:
    -  Execute the following commands:
        - composer require klaviyo/magento2-extension
        - php bin/magento module:enable Klaviyo_Reclaim  --clear-static-content
        - php bin/magento setup:upgrade
        - php bin/magento setup:static-content:deploy -f

### Setup
  - From admin:
    - Go to stores > configuration
    - Find Klaviyo in sidebar
    - Open General
    - Enable Klaviyo
    - Add Klaviyo public API key
    - For syncing Newsletter Subscribe/Unsubscribes: also add your Klaviyo private API key
    - To enable error logging: set "Enable Klaviyo Logger" to "Yes"
    - Save config
  - For syncing Newsletter Subscribe/Unsubscribes:
    - Open Newsletter from the sidebar
    - The page should load with your lists from Klaviyo
    - Select a list
    - Save config
  - To set up Email or SMS Consent at Checkout
    - Open Consent at Checkout from the sidebar
    - Configure for each respective section:
        - Email
            - Select if want to Subscribe contacts to email marketing at checkout
            - Select list from drop down where to add the contacts if they choose to subscribe
            - Enter text for checkbox selection to email marketing
        - SMS
            - Select if want to Subscribe contacts to SMS marketing at checkout
            - Select list from drop down where to add the contacts if they choose to subscribe
            - Enter text for checkbox selection to email marketing
            - Enter disclosure text that appears alongside the checkbox selection
  - To set up Webhooks
    - Open Webhooks from the sidebar
    - create a webhook secret and enter it into the corresponding Webhook Secret field
    - select Yes for Use Product Delete Webhook?
  - To setup OAuth integration with Klaviyo
    - Open Setup OAuth from the sidebar
    - Enter integration name
    - Save config

## Support

Contact extensions@klaviyo.com
