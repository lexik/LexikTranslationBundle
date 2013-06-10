#!/usr/bin/env php
<?php

set_time_limit(0);

if (isset($argv[1])) {
    $_SERVER['SYMFONY_VERSION'] = $argv[1];
}

$vendorDir = __DIR__.'/../vendor';
if (!is_dir($vendorDir)) {
    mkdir($vendorDir);
}

$deps = array(
    array('symfony',               'https://github.com/symfony/symfony.git'),
    array('doctrine-common',       'https://github.com/doctrine/common.git'),
    array('doctrine-dbal',         'https://github.com/doctrine/dbal.git'),
    array('doctrine',              'https://github.com/doctrine/doctrine2.git'),
    array('doctrine-fixtures',     'https://github.com/doctrine/data-fixtures.git'),
    array('doctrine-mongodb',      'https://github.com/doctrine/mongodb.git'),
    array('doctrine-mongodb-odm',  'https://github.com/doctrine/mongodb-odm.git'),
    array('DoctrineMongoDBBundle', 'https://github.com/doctrine/DoctrineMongoDBBundle.git')
);

$revs = array(
    'v2.2' => array(
        'symfony'               => 'v2.2.2',
        'doctrine-common'       => '2.3.0',
        'doctrine-dbal'         => '2.3.2',
        'doctrine'              => '2.3.2',
        'doctrine-fixtures'     => 'v1.0.0-ALPHA3',
        'mongodb'               => '1.0.3',
        'mongodb-odm'           => '1.0.0-BETA9',
        'DoctrineMongoDBBundle' => 'v3.0.0-BETA4',
    ),
    'v2.3' => array(
        'symfony'               => 'v2.3.0',
        'doctrine-common'       => '2.3.0',
        'doctrine-dbal'         => '2.3.4',
        'doctrine'              => '2.3.4',
        'doctrine-fixtures'     => 'v1.0.0-ALPHA3',
        'mongodb'               => '1.0.3',
        'mongodb-odm'           => '1.0.0-BETA9',
        'DoctrineMongoDBBundle' => 'v3.0.0-BETA4',
    ),
);

if (!isset($_SERVER['SYMFONY_VERSION'])) {
    $_SERVER['SYMFONY_VERSION'] = 'origin/master';
}

foreach ($deps as $index => $dep) {
    list($name, $url) = $dep;
    $rev = isset($revs[$_SERVER['SYMFONY_VERSION']][$name]) ? $revs[$_SERVER['SYMFONY_VERSION']][$name] : 'origin/master';

    $installDir = (substr($name, -6) == 'Bundle') ? $vendorDir.'/bundles/Symfony/Bundle/'.$name : $vendorDir.'/'.$name;
    if (!is_dir($installDir)) {
        echo sprintf("\n> Installing %s\n", $name);

        system(sprintf('git clone %s %s', escapeshellarg($url), escapeshellarg($installDir)));
    } else {
        echo sprintf("\n> Updating %s\n", $name);
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', escapeshellarg($installDir), escapeshellarg($rev)));
}
