.PHONY: up down build ssh composer test test-coverage test-phpunit install update help

# Project variables
DOCKER_COMPOSE = docker compose
DOCKER_COMPOSE_FILE = docker-compose.yml
DOCKER_SERVICE = app
COMPOSER = $(DOCKER_COMPOSE) run --rm $(DOCKER_SERVICE) composer

## —— Docker Compose ————————————————————————————————————————————————————————————
up: ## Start all containers in the background
	@$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up -d

up-verbose: ## Start all containers in the foreground
	@$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up

down: ## Stop and remove all containers
	@$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) down

down-v: ## Stop and remove all containers and volumes
	@$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) down -v

build: ## Rebuild the Docker containers
	@$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) build --no-cache

ssh: up ## Get shell access to the container
	@$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) exec $(DOCKER_SERVICE) bash

## —— Composer ——————————————————————————————————————————————————————————————————
composer: ## Run composer commands
	@$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) run --rm $(DOCKER_SERVICE) composer $(filter-out $@,$(MAKECMDGOALS))

install: ## Install dependencies
	@$(COMPOSER) install

update: ## Update dependencies
	@$(COMPOSER) update

## —— Testing ———————————————————————————————————————————————————————————————————
test: ## Run all tests with coverage report
	$(DOCKER_COMPOSE) run --rm $(DOCKER_SERVICE) bash -c "cd /var/www && XDEBUG_MODE=coverage ./vendor/bin/phpunit --testdox --coverage-text"

test-coverage: ## Generate HTML test coverage report
	$(DOCKER_COMPOSE) run --rm $(DOCKER_SERVICE) bash -c "cd /var/www && XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html=coverage"

coverage: test-coverage ## Alias for test-coverage

open-coverage: test-coverage ## Open the coverage report in default browser
	@if command -v xdg-open > /dev/null; then \
		xdg-open coverage/index.html; \
	elif command -v open > /dev/null; then \
		open coverage/index.html; \
	else \
		echo "Please open coverage/index.html in your browser"; \
	fi

test-phpunit: ## Run PHPUnit tests with optional arguments
	$(DOCKER_COMPOSE) run --rm $(DOCKER_SERVICE) bash -c "cd /var/www && ./vendor/bin/phpunit $(filter-out $@,$(MAKECMDGOALS))"

## —— Help ——————————————————————————————————————————————————————————————————————
help: ## Display this help screen
	@echo "\n\033[33mUsage:\033[0m\n  make [command] [arguments...]\n"
	@echo "\033[33mAvailable commands:\033[0m"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST) | sort

.DEFAULT_GOAL := help

%:
	@:
