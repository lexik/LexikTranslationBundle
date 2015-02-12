<?php

define("ORM_TYPE", getenv("ORM") ?: "doctrine");

define("DB_ENGINE", getenv("DB_ENGINE") ?: "pdo_mysql");
define("DB_HOST", getenv("DB_HOST") ?: "localhost");
define("DB_PORT", getenv("DB_PORT") ?: null);
define('DB_NAME', getenv("DB_NAME") ?: "lexik_translation_test");
define("DB_USER", getenv("DB_USER") ?: "root");
define("DB_PASSWD", getenv("DB_PASSWD") ?: null);

if (file_exists($file = __DIR__.'/autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/autoload.php.dist')) {
    require_once $file;
}
