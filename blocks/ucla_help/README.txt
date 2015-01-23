The Help & Feedback form uses the ua-parser PHP library
(https://github.com/ua-parser/uap-php) to provide user
friendly browser and OS versions. More details at:

https://jira.ats.ucla.edu/browse/CCLE-3666

Deployment Instructions
To install the ua-parser/uap-php package:
1. Install Composer (if not already installed):
    curl -sS http://getcomposer.org/installer | php
2. Update Composer:
    php composer.phar update --no-dev (for PROD)
    For DEV environments, just run the update command
3. Update the regexes.php file:
    php vendor/bin/uaparser.php ua-parser:update
