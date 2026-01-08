phpunit

./vendor/bin/phan

clear; vendor/bin/phpstan analyse autoload.php --level 5
clear; vendor/bin/phpstan analyse src/ --level 5
clear; vendor/bin/phpstan analyse tests/ --level 5

clear; vendor/bin/phpcs --standard=PSR12 autoload.php --error-severity=10 --exclude=Generic.Files.LineLength
clear; vendor/bin/phpcs --standard=PSR12 src/ --error-severity=10 --exclude=Generic.Files.LineLength
clear; vendor/bin/phpcs --standard=PSR12 --warning-severity=0 src/
clear; vendor/bin/phpcs --standard=PSR12 tests/ --error-severity=10 --exclude=Generic.Files.LineLength

./vendor/bin/phpcs -p --standard=PHPCompatibility autoload.php --runtime-set testVersion 7.0-
./vendor/bin/phpcs -p --standard=PHPCompatibility src/ --runtime-set testVersion 7.0-
./vendor/bin/phpcs -p --standard=PHPCompatibility tests/ --runtime-set testVersion 7.0-


phpcbf autoload.php
phpcbf src/
phpcbf tests/
