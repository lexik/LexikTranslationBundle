<?php

if (ORM_TYPE == "doctrine") {
    $container->loadFromExtension(
        'doctrine',
        array(
            'orm'  => array(
                'mappings' => array(
                    'Mapping' => array(
                        'type'      => 'xml',
                        'prefix'    => 'Lexik\Bundle\TranslationBundle\Entity',
                        'is_bundle' => false,
                        'dir'       => '%kernel.root_dir%/../../Resources/config/doctrine'
                    )
                )
            ),
            'dbal' => array(
                'charset'  => 'UTF8',
                'driver'   => DB_ENGINE,
                'host'     => DB_HOST,
                'port'     => DB_PORT,
                'dbname'   => DB_NAME,
                'user'     => DB_USER,
                'password' => DB_PASSWD
            )
        )
    );
} else {
    throw new \Exception("Currently only doctrine is supported");
}
