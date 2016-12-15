.PHONY: help tests update bootstrap
.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-12s\033[0m %s\n", $$1, $$2}'

tests: ## Execute test suite and create code coverage report
	docker run -v $(shell pwd):/app --rm phpunit/phpunit:5.1.0 tests/

update: ## Update composer packages
	docker run --rm -v $(shell pwd):/app composer/composer update

bootstrap: ## Install composer
	docker run --rm -v $(shell pwd):/app composer/composer install
