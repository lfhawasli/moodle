The Help & Feedback form uses the ua-parser PHP library
(https://github.com/ua-parser/uap-php) to provide user
friendly browser and OS versions. More details at:

https://ucla-ccle.atlassian.net/browse/CCLE-3666

Deployment Instructions
To install the ua-parser/uap-php package:
1. Install Composer (if not already installed):
    curl -sS http://getcomposer.org/installer | php
2. Update Composer:
    php composer.phar install --no-dev (for PROD)
    For DEV environments, just run the install command
3. Update the regexes.php file:
    php vendor/bin/uaparser.php ua-parser:update
