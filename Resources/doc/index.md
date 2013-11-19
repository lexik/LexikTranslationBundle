Installation
============

Add the bunde to your `composer.json` file:

```javascript
require: {
    // ...
    "lexik/translation-bundle": "v1.4.0"
}
```

Then run a composer update:

```shell
composer.phar update
# OR
composer.phar update lexik/translation-bundle # to only update the bundle
```

Register the bundle with your kernel:

```php
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Lexik\Bundle\TranslationBundle\LexikTranslationBundle(),
    // ...
);
```

___________________

Configuration
=============

Minimum configuration:

```yml
# app/config/config.yml
lexik_translation:
    fallback_locale: en      # (required) default locale to use
    managed_locales: [en]    # (required) locales that the bundle have to manage
```

Additional configuration options (default values are shown here):

```yml
# app/config/config.yml
lexik_translation:
    base_layout:      "LexikTranslationBundle::layout.html.twig"   # layout used with the translation edition template
    use_yml_tree: false                                    # if "true" we will print a nice tree in the yml source files. It is a little slower.
    storage:
        type: orm                                          # where to store translations: "orm" or "mongodb"
        object_manager: something                          # The name of the entity / document manager which uses different connection (see: http://symfony.com/doc/current/cookbook/doctrine/multiple_entity_managers.html)
    resources_registration:
        type:                 all                                  # resources type to register: "all", "files" or "database"
        managed_locales_only: true                                 # will only load resources for managed locales
```

*Note that MongoDB 2.0.0 or later is required if you choose to use MongoDB to store translations.*

To use the translation edition page, add the routing file to you application:

```yml
# app/config/routing.yml
lexik_translation_edition:
    resource: "@LexikTranslationBundle/Resources/config/routing.yml"
    prefix:   /my-prefix
```

The translations edition page will be available here: /my-prefix/grid

Note: The grid will be empty until you import translations in database and use [jqGrid 4.4.4](http://www.trirand.com/blog/).
If the grid does not appear, please check your base template has a block named `javascript_footer`.

___________________

Import translations
===================

To import translations files content into your database just run the following command:

    ./app/console lexik:translations:import [bundleName] [--cache-clear] [--force] [--globals]

This command will import all application and bundles translations files according to the `managed_locales` defined in configuration.

Command arguments:
* `bundleName`: only import translations for the given bundle name.

Command options:
* `--cache-clear` (or `-c`): remove translations cache files (it won't clear all cache files but just files from `app/cache/[env]/translations/`).
* `--force` (or `-f`): update the translations even if the element already exist in the database.
* `--globals` (or `-g`): import only from the `app/Resources/translations`. It will ignore the option if you provide a BundleName to import.
* `--locales` (or `-l`): import only for these locales, instead of using the managed locales from the config. ex: `--locales=fr --locales=en`

Export translations
===================

To export translations from the database in to files run the following command:

    ./app/console lexik:translations:export [--locales=en,de] [--domains=messages,validators] [--format=yml]

This command will export all translations from the database in to files. A translation is exported in the same file (and format) it was imported in,
except for vendors files which are exported in `app/Resources/translations/` and in this case the command will only export translations that changed.

Command options:
* `--locales`: Only export files for given locales.
* `--domains`: Only export files for given domains.
* `--format`: Force the output format.

*Note that it's not required to export translations to make them appear on your website as the `DatabaseLoader` will load them.*
