# Local Development

How to run a branch of this module in a local Magento 2 store and check the events it records.

## Local Magento 2 store

These steps use [Mark Shust's docker-magento](https://github.com/markshust/docker-magento). Any Magento 2.4.x store works, but the commands below assume docker-magento's `bin/` helpers.

### 1. Install Magento

Follow docker-magento's one-step setup, which requires Magento Marketplace access keys. Before you start, check that nothing else is listening on the ports the stack publishes:

```
for p in 80 443 3306 5672 15672 6379 9200 9300 1080 8080; do lsof -nP -iTCP:$p -sTCP:LISTEN; done
```

If another local stack needs those ports, either stop it or change the host side of the port mappings in docker-magento's `compose.yaml`. Only the host ports need to change. Magento's `app/etc/env.php` keeps the service hostnames and ports it uses inside the Docker network.

### 2. Mount this repository as the module

Point a checkout of this repository at `app/code/Klaviyo/Reclaim`, so the store runs whichever branch you have checked out. Add a bind mount to the shared `&appvolumes` list in docker-magento's `compose.dev.yaml`, right after the `./src/app/code` entry:

```yaml
      - ./src/app/code:/var/www/html/app/code:cached
      - /path/to/magento2-klaviyo:/var/www/html/app/code/Klaviyo/Reclaim:cached
```

A [git worktree](https://git-scm.com/docs/git-worktree) works well as the mounted checkout. It lets you test one branch while working on another, and it doesn't contain this repository's own `vendor/` directory.

Recreate the containers and confirm the mount:

```
bin/docker-compose up -d --remove-orphans
bin/cli ls app/code/Klaviyo/Reclaim
```

### 3. Install the module's dependencies and upgrade

If the store already has the module installed from Composer, remove that copy first. Otherwise Magento has two copies of the same module. Modules in `app/code` don't have their `composer.json` resolved, so require the module's dependencies from `composer.json` (currently `giggsey/libphonenumber-for-php`) in the Magento project:

```
bin/composer remove klaviyo/magento2-extension
bin/composer require "giggsey/libphonenumber-for-php:^9.0"
bin/magento setup:upgrade
bin/magento cache:flush
```

Check that Magento sees the version on your branch:

```
bin/mysql -e "SELECT module, schema_version, data_version FROM setup_module WHERE module = 'Klaviyo_Reclaim';"
```

To go back to the Composer package, remove the bind mount and run `bin/composer require klaviyo/magento2-extension`.

### 4. Admin access

docker-magento creates the admin user from `env/magento.env` (`MAGENTO_ADMIN_USER` / `MAGENTO_ADMIN_PASSWORD`). If the admin asks for two-factor authentication, turn it off on the local store:

```
bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth Magento_TwoFactorAuth
bin/magento cache:flush
```

The same credentials return an admin REST token, which is useful for creating test products or config by script:

```
curl -sk -X POST https://magento.test/rest/V1/integration/admin/token \
  -H 'Content-Type: application/json' -d '{"username":"<user>","password":"<password>"}'
```

## Checking recorded events

The module records storefront events such as `Added To Cart` in the `kl_events` table. A cron job copies them to `kl_sync`, and another sends them to Klaviyo. You can test the payloads without sending anything to Klaviyo:

- **Keep Magento cron off** unless you want events delivered. With docker-magento, `bin/cli test -f var/.cron-enabled` shows whether cron was turned on, and `bin/cron stop` turns it off.
- **Configure a private API key.** `Added To Cart` is only recorded when the store has a Klaviyo private API key set.
- **Identify the shopper.** The shopper must have a `__kla_id` cookie whose value is base64-encoded JSON containing `$exchange_id` or `$email`. Klaviyo's onsite JavaScript normally sets this cookie. You can also set it yourself, e.g. `base64('{"$exchange_id":"local-test"}')`.

After an add to cart, read the newest event and compare it with the cart:

```
bin/mysql -e "SELECT payload FROM kl_events WHERE event = 'Added To Cart' ORDER BY id DESC LIMIT 1;"
bin/mysql -e "SELECT base_grand_total, grand_total, quote_currency_code FROM quote WHERE entity_id = <QuoteId>;"
```

For guests, the payload's `QuoteId` is the numeric `quote.entity_id`. For logged-in customers it's `kx_identifier_<base64 customer id>`.

### Adding to the cart without a browser

Requests can be scripted, which keeps runs repeatable:

- **Storefront:** `POST /checkout/cart/add/` with `product`, `qty` and `form_key`. Magento accepts a `form_key` field that matches the `form_key` cookie, so you can set both to the same value.
  - Configurable products also need `super_attribute[<attribute id>]=<option id>`.
  - Bundles need `bundle_option[<option id>]=<selection id>`.
- **Customer login:** `POST /customer/account/loginPost/` with `login[username]`, `login[password]` and `form_key`.
- **Shipping in the cart total:** call `POST /rest/default/V1/carts/mine/shipping-information` with a customer token to set a shipping method on that customer's cart.
- **Display currency:** `POST /directory/currency/switch/` with `currency=<code>` (the currency must be allowed and have a rate).
- **GraphQL:** `createEmptyCart`, then `addProductsToCart`. Send the `__kla_id` cookie as a `Cookie` header on the request.

## Unit tests

Unit tests run from this repository, not from inside Magento:

```
make composer-dev install
vendor/bin/phpunit
```

`Test/Unit/_bootstrap.php` loads small stand-ins for the Magento framework classes the tests need, from `Test/Unit/_stubs/`. A passing run shows the code is internally consistent, not that it behaves correctly in a real Magento store, so also check framework-dependent changes in a local store.
