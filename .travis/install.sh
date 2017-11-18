# To be removed when these issues are resolved:
# https://github.com/composer/composer/issues/5355
# https://github.com/composer/composer/issues/5030
composer update --prefer-dist --no-interaction --prefer-stable --quiet --ignore-platform-reqs

composer update --prefer-dist --no-interaction --prefer-stable
