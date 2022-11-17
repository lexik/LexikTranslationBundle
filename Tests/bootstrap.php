<?php
/*
 * The defaults are used in CI for the github action.
 *
 * Somehow copying a .env.ci there did not work, so I put defaults here that work
 */
define("ORM_TYPE", getenv("ORM") ?: "doctrine");

define("DB_ENGINE", getenv("DB_ENGINE") ?: "pdo_mysql");
define("DB_HOST", getenv("DB_HOST") ?: "127.0.0.1");
define("DB_PORT", getenv("DB_PORT") ?: 3306);
define('DB_NAME', getenv("DB_NAME") ?: "lexik_translation_test");
define("DB_USER", getenv("DB_USER") ?: "root");
define("DB_PASSWD", getenv("DB_PASSWD") ?: "");
define("MONGO_SERVER", getenv("MONGO_SERVER") ?: "mongodb://admin:secret@mongo:27017/admin");

if (file_exists($file = __DIR__.'/autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/autoload.php.dist')) {
    require_once $file;
}
