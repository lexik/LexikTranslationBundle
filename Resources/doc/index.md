Installation
============

Add the bunde to your `composer.json` file:

```javascript
require: {
    // ...
    "lexik/translation-bundle": "~7.1"
}
```

Or install directly through composer with:

```shell
# Latest stable
composer require lexik/translation-bundle ~7.1

# For latest unstable version
composer require lexik/translation-bundle dev-master
```

Then run a composer update:

```shell
composer update
# OR
composer update lexik/translation-bundle # to only update the bundle
```

Register the bundle with your kernel:

```php
// in AppKernel::registerBundles()
$bundles = [
    // ...
    new Lexik\Bundle\TranslationBundle\LexikTranslationBundle(),
    // ...
];
```

Then install the required assets:

```bash
    ./bin/console assets:install
```

___________________

Configuration
=============

#### Minimum configuration

You must at least define the fallback locale(s) as for the `framework.translator` node, and define all locales you will manage.

```yml
# app/config/config.yml
lexik_translation:
    fallback_locale: [en]         # (required) default locale(s) to use
    managed_locales: [en, fr, de] # (required) locales that the bundle has to manage
```

#### Additional configuration options

*Default values are shown here.*

Configure where to store translations, by default the bundle will use Doctrine ORM but you can also use Doctrine MongoDB.
You can also define the name of the entity / document manager which uses [different connection](http://symfony.com/doc/current/cookbook/doctrine/multiple_entity_managers.html).

Note that MongoDB 2.0.0 or later is required if you choose to use MongoDB.

```yml
lexik_translation:
    storage:
        type: orm                  # orm | mongodb
        object_manager: something  # The name of the entity / document manager which uses different connection (see: http://symfony.com/doc/current/cookbook/doctrine/multiple_entity_managers.html)
                                   # When using propel, this can be used to specify the propel connection name
```

Change the layout used with the bundle's template:

```yml
lexik_translation:
    base_layout: "LexikTranslationBundle::layout.html.twig"
```

For new symfony or twig versions:

```yml
lexik_translation:
    base_layout: "@LexikTranslation/layout.html.twig"
```

You can customize the edition grid by using input text or textarea fields.
You can also shown/hidden similar columns on the grid. This means on the grid if a locale column is shown/hidden then similar locales columns will be shown/hidden too.
(e.g.: if the col "en" is shown/hidden all "en_XX" cols will be shown/hidden too)

```yml
lexik_translation:
    grid_input_type: text       # text|textarea
    grid_toggle_similar: false
```

If you export translation by using YAML, you can switch the following option to `true` to print a nice tree in the yml source files.
(It is a little slower).

```yml
lexik_translation:
    exporter:
        use_yml_tree: false
```

If you export translation by using JSON, you can choose to export JSON with a hierarchical structure.

```yml
lexik_translation:
    exporter:
        json_hierarchical_format: false
```

You can choose the resource's type you want to load, by default the bundle will load translations from files + database, but you can choose to use only one of these two resource types.
Note that if you use files + database, if a translation exists in both resources, the value from the database will override the file's translation because the database is loaded after.
By default the bundle will only load resources for managed locales.

```yml
lexik_translation:
    resources_registration:
        type:                 all  # all | files | database
        managed_locales_only: true
```

The two following options can be used if you want the bundle to automatically clear translations cache files. 
To do this, the bundle will check the latest update date among the translations (in the database).

```yml
lexik_translation:
    auto_cache_clean: false
    auto_cache_clean_interval: 600  # number of seconds to wait before trying to check if translations have changed in the database.
```

From the translations grid you can get untranslated keys from a given Symfony profile token. This option should be enabled only in **dev** environment.
Note that the key must exist in the database to appear in the grid.
If you want to force the translations keys to appear in the grid you can enable the `create_missing` option.
If you do so, while getting missing translations from a profile, if a key/domain pair does not exist in the database the bundle will create it.
During this process some new keys and translations can be created in the database. Each new translation will be associated to a file according to the tanslation's domain and locale.
In this case the bundle will look for files in `app/Resources/translations`. You can change the default format of these files by using the `file_format` option.

```yml
lexik_translation:
    dev_tools:
        enable: false
        create_missing: false
        file_format: yml
```

If you use Doctrine ORM, you have to update your database:

    ./bin/console doctrine:schema:update --force

#### Routing

To use the translation edition page, add the routing file to you application:

```yml
# app/config/routing.yml
lexik_translation_edition:
    resource: "@LexikTranslationBundle/Resources/config/routing.yml"
    prefix:   /my-prefix
```

The translations edition page will be available here:

* `/my-prefix/` for the overview page

* `/my-prefix/grid` for the translations grid

**Note**: The grid will be empty until you import translations in the database.
If the grid does not appear, please check that your base template has a block named `javascript_footer`.

___________________

Import translations
===================

To import translations files content into your database just run the following command:

    ./bin/console lexik:translations:import [bundleName] [--cache-clear] [--force] [--globals]

This command will import all application and bundles translations files according to the `managed_locales` defined in configuration (it will also load tanslations from SF components).

Command arguments:
* `bundle`: Import translations for this specific bundle.

Command options:
* `--cache-clear` (or `-c`): remove translations cache files (it won't clear all cache files but just files from `app/cache/[env]/translations/`).
* `--force` (or `-f`): update the translations even if the elements already exist in the database.
* `--globals` (or `-g`): import only from the `app/Resources/translations`. It will ignore the option if you provide a BundleName to import.
* `--locales` (or `-l`): import only for these locales, instead of using the managed locales from the config. eg: `--locales=fr --locales=en`
* `--domains` (or `-d`): Only import files for given domains (comma separated). eg `--domains=messages,validators`
* `--case-insensitive` (or `-i`): Convert keys as lower case to check if a key has already been imported.
* `--import-path` (or `-p`): Search for translations at given path. Cannot be used with globals, merge or only-vendors option. eg `--import-path=\tmp`
* `--only-vendors` (or `-o`): Only import files from vendor-bundles. eg `--only-vendors`
* `--merge` (or `-m`): Merge translations (use ones with latest updatedAt date).
        
Export translations
===================

To export translations from the database into files, run the following command:

    ./bin/console lexik:translations:export [--locales=en,de] [--domains=messages,validators] [--format=yml] [--case-insensitive]

This command will export all translations from the database into files. A translation is exported in the same file (and format) it was imported in,
except for vendors files which are exported in `app/Resources/translations/` and in this case the command will only export translations that have changed.

Command options:
* `--locales`: Only export files for given locales.
* `--domains`: Only export files for given domains.
* `--format`: Force the output format.
* `--override`: Only export modified phrases (app/Resources/translations are exported fully anyway).
* `--export-path`: Export files to given path.

*Note that it's not required to export translations to make them appear on your website as the `DatabaseLoader` will load them.*

TESTING
=======

[Read the documentation for testing ](./testing.md)
