PHPUNIT_BIN=vendor/bin/phpunit

COVERAGE_DIR=tests/coverage

default: test

cleanup:
	rm -rf $(COVERAGE_DIR)

test: cleanup
	$(PHPUNIT_BIN) --coverage-html tests/coverage
