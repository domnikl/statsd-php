PHP_BIN=php
COMPOSER_BIN=composer.phar
PHPUNIT_BIN=vendor/bin/phpunit
NETCAT=nc

COVERAGE_DIR=tests/coverage

default: test

$(COMPOSER_BIN):
	wget https://raw.githubusercontent.com/composer/getcomposer.org/1b137f8bf6db3e79a38a5bc45324414a6b1f9df2/web/installer -O - -q | $(PHP_BIN) -- --quiet

cleanup:
	rm -rf $(COVERAGE_DIR) && rm -f stats.log

test: install cleanup
	$(PHPUNIT_BIN) --coverage-html tests/coverage

install: $(COMPOSER_BIN)
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
