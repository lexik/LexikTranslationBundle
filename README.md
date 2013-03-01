Overview
========

This Symfony2 bundle allow to import translation files content into the database and provide a GUI to edit translations.

[![Build Status](https://travis-ci.org/lexik/LexikTranslationBundle.png?branch=master)](https://travis-ci.org/lexik/LexikTranslationBundle)

The idea is to:

* write your translations files (xliff, yml or php) as usual for at least one language (the default language of your website for example).
* load translations into the database by using a command line.
* freely edit/add translation through an edition page.

The bundle override the translator service and provide a DatabaseLoader.
Database translations content is loaded last so it override content from xliff, yml and php translations files.
You can also export translations from the database in to files in case of you need to get translations files with the same content as the database.

Documentation
=============

For installation and configuration refer to [Resources/doc/index.md](https://github.com/lexik/LexikTranslationBundle/blob/master/Resources/doc/index.md)

___________________

Here a little screen shot of the edition page :)

![edition page screen](https://github.com/lexik/LexikTranslationBundle/raw/master/Resources/doc/screen/grid.jpg)
