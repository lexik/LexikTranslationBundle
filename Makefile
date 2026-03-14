.PHONY: up down build test rector cs-fix phpstan ensure-up help cache-clear install update

DOCKER_RUN = docker compose run --rm lexik_translation

.DEFAULT_GOAL := help

help:
	@echo "LexikTranslationBundle - Available commands:"
	@echo ""
	@echo "  make up          Start services in the background"
	@echo "  make ensure-up   Start services and install dependencies (composer)"
	@echo "  make down        Stop and remove containers"
	@echo "  make build       Build Docker images"
	@echo "  make test        Run test suite (PHPUnit)"
	@echo "  make rector      Run Rector (refactoring)"
	@echo "  make cs-fix      Apply PHP-CS-Fixer (code style)"
	@echo "  make phpstan     Run PHPStan (static analysis)"
	@echo "  make cache-clear Clear Composer cache"
	@echo "  make install     Install dependencies (composer install)"
	@echo "  make update      Update dependencies (composer update)"
	@echo "  make help        Show this help"

up:
	docker compose up -d

ensure-up:
	docker compose up -d
	$(DOCKER_RUN) composer install --prefer-dist --no-progress

down:
	docker compose down

build:
	docker compose build

test: ensure-up
	$(DOCKER_RUN) composer test

rector: ensure-up
	$(DOCKER_RUN) vendor/bin/rector process

cs-fix: ensure-up
	$(DOCKER_RUN) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

phpstan: ensure-up
	$(DOCKER_RUN) vendor/bin/phpstan analyse --memory-limit=512M

cache-clear: ensure-up
	$(DOCKER_RUN) composer clear-cache

install: ensure-up
	$(DOCKER_RUN) composer install --prefer-dist --no-progress

update: ensure-up
	$(DOCKER_RUN) composer update --no-progress
