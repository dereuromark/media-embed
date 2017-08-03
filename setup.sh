#!/bin/bash
# Warning: This is NOT a productive script, but for local dev envs only!

echo "### INSTALL/UPDATE ###";
[ ! -f composer.phar ] && curl -sS https://getcomposer.org/installer | php
php composer.phar selfupdate

git pull

rm -f composer.lock
php composer.phar update --prefer-dist --no-dev --optimize-autoloader --no-interaction

wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar

echo "### DONE ###";
