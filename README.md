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

## Prerequisites

Magento 2

### Setup
  - From admin:
    - Go to stores > configuration
    - Find Klaviyo in sidebar
    - Open General
    - Enable Klaviyo
    - Add 6 digit Klaviyo public API key
    - For syncing Newsletter Subscribe/Unsubscribes: also add your Klaviyo private API key
    - To enable error logging: set "Enable Klaviyo Logger" to "Yes"
    - Save config
  - For syncing Newsletter Subscribe/Unsubscribes:
    - Open Newsletter from the sidebar
    - The page should load with your lists from Klaviyo
    - Select a list
    - Save config
  - To setup API credentials for the integration
    - Open Setup Klaviyo User from the sidebar
    - Enter the username, password, and email for the credentials
    - Save config
## Support

Contact support@klaviyo.com
