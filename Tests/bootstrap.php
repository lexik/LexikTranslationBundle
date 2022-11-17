<?php

define("ORM_TYPE", getenv("ORM") ?: "doctrine");

define("DB_ENGINE", getenv("DB_ENGINE") ?: "pdo_mysql");
define("DB_HOST", getenv("DB_HOST") ?: "127.0.0.1");
define("DB_PORT", getenv("DB_PORT") ?: 3306);
define('DB_NAME', getenv("DB_NAME") ?: "lexik_translation_test");
define("DB_USER", getenv("DB_USER") ?: "root");
define("DB_PASSWD", getenv("DB_PASSWD") ?: "");

if (file_exists($file = __DIR__.'/autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/autoload.php.dist')) {
    require_once $file;
}
