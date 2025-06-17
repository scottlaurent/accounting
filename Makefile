.PHONY: up down build ssh composer test test-coverage test-phpunit test-pest tinker art migrate fresh seed clear key generate install update

# Project variables
DOCKER_COMPOSE = docker compose
DOCKER_COMPOSE_FILE = docker-compose.yml
DOCKER_SERVICE = app
PHP = $(DOCKER_COMPOSE) run --rm $(DOCKER_SERVICE) php
COMPOSER = $(DOCKER_COMPOSE) run --rm $(DOCKER_SERVICE) composer
ARTISAN = $(PHP) artisan
PHPUNIT = $(PHP) ./vendor/bin/phpunit

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
test: ## Run all tests
	@$(PHPUNIT) --testdox

test-coverage: ## Generate test coverage report
	@XDEBUG_MODE=coverage $(PHP) -dxdebug.mode=coverage $(PHPUNIT) --coverage-html coverage

test-phpunit: ## Run PHPUnit tests
	@$(PHPUNIT) $(filter-out $@,$(MAKECMDGOALS))

test-pest: ## Run Pest tests
	@$(PHP) vendor/bin/pest $(filter-out $@,$(MAKECMDGOALS))

## —— Laravel ———————————————————————————————————————————————————————————————————
tinker: ## Run tinker
	@$(ARTISAN) tinker

art: ## Run an Artisan command
	@$(ARTISAN) $(filter-out $@,$(MAKECMDGOALS))

migrate: ## Run database migrations
	@$(ARTISAN) migrate

fresh: ## Drop all tables and re-run migrations
	@$(ARTISAN) migrate:fresh

seed: ## Seed the database with records
	@$(ARTISAN) db:seed

clear: ## Clear all caches
	@$(ARTISAN) cache:clear
	@$(ARTISAN) config:clear
	@$(ARTISAN) route:clear
	@$(ARTISAN) view:clear

key: ## Generate application key
	@$(ARTISAN) key:generate

## —— Help ——————————————————————————————————————————————————————————————————————
help: ## Display this help screen
	@echo "\n\033[33mUsage:\033[0m\n  make [command] [arguments...]\n"
	@echo "\033[33mAvailable commands:\033[0m"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST) | sort

.DEFAULT_GOAL := help

%:
	@:

# This is a workaround for make's handling of command line arguments
# It allows you to pass additional arguments to commands like `make test --filter=ExampleTest`
# The empty recipe with `@:` tells make to do nothing for these targets
