#!/bin/bash

phpunit

./vendor/bin/phan

vendor/bin/phpstan analyse autoload.php --level 9
vendor/bin/phpstan analyse src/ --level 5
vendor/bin/phpstan analyse tests/ --level 5

vendor/bin/phpcs --standard=PSR12 autoload.php --exclude=Generic.Files.LineLength
vendor/bin/phpcs --standard=PSR12 src/  --exclude=Generic.Files.LineLength
vendor/bin/phpcs --standard=PSR12 src/ --warning-severity=0 --error-severity=10 --exclude=Generic.Files.LineLength
vendor/bin/phpcs --standard=PSR12 tests/ --exclude=Generic.Files.LineLength

./vendor/bin/phpcs -p --standard=PHPCompatibility autoload.php --runtime-set testVersion 7.0-
./vendor/bin/phpcs -p --standard=PHPCompatibility src/ --runtime-set testVersion 7.0-
./vendor/bin/phpcs -p --standard=PHPCompatibility tests/ --runtime-set testVersion 7.0-


phpcbf autoload.php
phpcbf src/
phpcbf tests/
