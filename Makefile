.PHONY: all
all: clean test coverage

.PHONY: clean
clean:
	rm -rf build/artifacts/*

.PHONY: lint
lint:
	find src -name "*.php" -print0 | xargs -0 -n1 php -l

.PHONY: test
test: lint
	vendor/bin/phpunit --testsuite=php-lib-amqp-service $(TEST)

.PHONY: coverage
coverage:
	vendor/bin/phpunit --testsuite=php-lib-amqp-service --coverage-html=build/artifacts/coverage $(TEST)

.PHONY: coverage-show
coverage-show:
	open build/artifacts/coverage/index.html
