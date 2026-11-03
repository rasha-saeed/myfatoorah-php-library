#!/bin/bash

# Without `set -e` bash keeps going after a command fails, and the exit code of
# every command but the last one is silently discarded, so a failing check can
# scroll past unnoticed. Uncomment it to stop at the first failure -- that is
# what `composer check` already does, which is why it caught problems this file
# had been hiding.
#set -e

./vendor/bin/phpunit

./vendor/bin/phan

./vendor/bin/phpstan analyse autoload.php --level 9
./vendor/bin/phpstan analyse src/ --level 5
./vendor/bin/phpstan analyse tests/ --level 5

./vendor/bin/phpcs --standard=PSR12 autoload.php --exclude=Generic.Files.LineLength,PSR1.Files.SideEffects
./vendor/bin/phpcs --standard=PSR12 src/ --exclude=Generic.Files.LineLength
./vendor/bin/phpcs --standard=PSR12 src/ --warning-severity=0 --error-severity=10 --exclude=Generic.Files.LineLength
./vendor/bin/phpcs --standard=PSR12 tests/ --exclude=Generic.Files.LineLength

./vendor/bin/phpcs -p --standard=PHPCompatibility autoload.php --runtime-set testVersion 7.0-
./vendor/bin/phpcs -p --standard=PHPCompatibility src/ --runtime-set testVersion 7.0-
./vendor/bin/phpcs -p --standard=PHPCompatibility tests/ --runtime-set testVersion 8.4-


./vendor/bin/phpcbf --standard=PSR12 autoload.php --exclude=Generic.Files.LineLength,PSR1.Files.SideEffects
./vendor/bin/phpcbf --standard=PSR12 src/ --exclude=Generic.Files.LineLength
./vendor/bin/phpcbf --standard=PSR12 tests/ --exclude=Generic.Files.LineLength
