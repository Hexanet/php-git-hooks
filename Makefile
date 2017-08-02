.PHONY: help install security-check checks lint

default: help
.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install --no-interaction --optimize-autoloader --prefer-dist --ansi --no-suggest

security-check: ## Looks for security issues in the project dependencies
	security-checker security:check --ansi

lint: ## Execute php lint
	parallel-lint --exclude app --exclude vendor .

checks: lint security-check ## [CI] Run all the checks

pre-commit: ## Execute pre-commit
	bin/php-git-hooks pre-commit