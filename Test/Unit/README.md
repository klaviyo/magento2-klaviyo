# Prerequisites/Dependencies
- xDebug https://xdebug.org/docs/install
- php-xml https://stackoverflow.com/a/35722779
- The extension needs to be installed in a Magento 2 app

# Running the tests
- cd into the root of the M2 app
- cd into vendor/klaviyo/magento2-extension
- run composer install
- cd back into M2 root
- run vendor/phpunit/phpunit/phpunit -c vendor/klaviyo/magento2-extension/phpunit.xml vendor/klaviyo/magento2-extension/Test/Unit/

