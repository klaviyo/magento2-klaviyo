# Running Unit Tests in a Magento 2 Store


# Running Unit Tests Outside of a Magento 2 Store
- Comment out all of the PHP inside of registration.php
 - This file is responsible for registering the module with the M2 component registrar, so outside of a store it will error out
- Run composer install --dev
- Run vendor/bin/phpunit 