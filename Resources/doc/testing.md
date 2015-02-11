# Testing

run test from console

``` bash
$ phpunit.phar
```

or you can setup vars for doctrine pdo driver like this

``` bash
$ export DB_NAME=acme && export DB_USER=acme && export DB_PASSWD=acme && export DB=mysql && export DB_HOST=acme && phpunit.phar
```

according to default credentials for travis CI you must run

``` bash
$ export DB_NAME=lexik_test && export DB_USER=root && unset DB_PASSWD && unset DB && unset DB_HOST && phpunit.phar
```

Available variables are:
 - ORM - orm system, currently we support only doctrine2, we should also support propel and mongo
 - DB_NAME - database name (default: lexik_translation_test)
 - DB_USER - database user name (default: root)
 - DB_PASSWD - database user password (default: null)
 - DB_ENGINE - database engine (default: pdo_mysql)
 - DB_PORT - database port (default: null)
