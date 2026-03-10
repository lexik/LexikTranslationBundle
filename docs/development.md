# Development setup

This document explains how to run the development tooling (tests, PHPStan, Rector, PHP-CS-Fixer) and how to do it **cross-platform** when `make` is not available.

## Makefile (Linux / macOS)

The project uses a **Makefile** for convenience. You need [GNU Make](https://www.gnu.org/software/make/) installed (it usually is on Linux and macOS).

```bash
make help        # List all available commands
make ensure-up   # Start Docker services and install Composer dependencies
make test        # Run PHPUnit
make phpstan     # Run PHPStan
make cs-fix      # Run PHP-CS-Fixer
make rector      # Run Rector
make install     # Composer install
make update      # Composer update
make cache-clear # Clear Composer cache
make up          # Start containers only
make down        # Stop containers
make build       # Build Docker images
```

All PHP commands run inside the `lexik_translation` Docker container, so you don’t need PHP or extensions installed on the host.

## Cross-platform: without Make (e.g. Windows)

If `make` is not installed (typical on Windows), you can run the same steps using **Docker Compose** directly.

1. **Start services and install dependencies**

   ```bash
   docker compose up -d
   docker compose run --rm lexik_translation composer install --prefer-dist --no-progress
   ```

2. **Run tests**

   ```bash
   docker compose run --rm lexik_translation composer test
   ```

3. **Run PHPStan**

   ```bash
   docker compose run --rm lexik_translation vendor/bin/phpstan analyse --memory-limit=512M
   ```

4. **Run PHP-CS-Fixer**

   ```bash
   docker compose run --rm lexik_translation vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
   ```

5. **Run Rector**

   ```bash
   docker compose run --rm lexik_translation vendor/bin/rector process
   ```

6. **Other commands**

   - Composer install: `docker compose run --rm lexik_translation composer install --prefer-dist --no-progress`
   - Composer update: `docker compose run --rm lexik_translation composer update --no-progress`
   - Clear Composer cache: `docker compose run --rm lexik_translation composer clear-cache`

On **Windows** you can also install Make (e.g. via [Chocolatey](https://chocolatey.org/) (`choco install make`), [Scoop](https://scoop.sh/), or [WSL](https://docs.microsoft.com/en-us/windows/wsl/)) and use the Makefile as on Linux/macOS.

## Cache files

The following cache/result files are **not** committed (they are in `.gitignore`):

- `.php-cs-fixer.cache` — PHP-CS-Fixer cache
- `.phpunit.result.cache` — PHPUnit result cache

You can delete them locally at any time; they will be recreated when you run the tools again.
