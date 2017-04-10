PHP_BIN=php
COMPOSER_BIN=composer.phar
PHPUNIT_BIN=vendor/bin/phpunit
NETCAT=nc

COVERAGE_DIR=tests/coverage

default: test

cleanup:
	rm -rf $(COVERAGE_DIR) && rm -f stats.log

test: install cleanup
	$(PHPUNIT_BIN) --coverage-html tests/coverage

install:
	$(PHP_BIN) $(COMPOSER_BIN) install

tcp-testserver:
	$(NETCAT) -tlnp 8126

udp-testserver:
	$(NETCAT) -ulnp  8125

tcp-integration:
	$(PHP_BIN) tests/integration/tcp-test.php

udp-integration:
	$(PHP_BIN) tests/integration/udp-test.php

file-integration:
	$(PHP_BIN) tests/integration/file-test.php
