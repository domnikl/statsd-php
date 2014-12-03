PHP_BIN=php
COMPOSER_BIN=composer.phar
PHPUNIT_BIN=vendor/bin/phpunit

COVERAGE_DIR=tests/coverage

default: test

cleanup:
	rm -rf $(COVERAGE_DIR)

test: install cleanup
	$(PHPUNIT_BIN) --coverage-html tests/coverage

install:
	$(PHP_BIN) $(COMPOSER_BIN) install

tcp-testserver:
	nc -tl localhost 8126

udp-testserver:
	nc -ul localhost 8125

tcp-integration:
	$(PHP_BIN) tests/integration/tcp-test.php

udp-integration:
	$(PHP_BIN) tests/integration/udp-test.php
