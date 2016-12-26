Overview
========

This Symfony2 bundle allows you to:

* import translation files content into the database and provide a GUI to edit translations.
* export translations from the database into files.
* have an overview to check translation domains are completely translated.
* add new translations in the database.

[![Build Status](https://travis-ci.org/lexik/LexikTranslationBundle.png?branch=master)](https://travis-ci.org/lexik/LexikTranslationBundle)
[![Latest Stable Version](https://poser.pugx.org/lexik/translation-bundle/v/stable.svg)](https://packagist.org/packages/lexik/translation-bundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/dda3efbd-0df3-44eb-beca-3659000021d4/mini.png)](https://insight.sensiolabs.com/projects/dda3efbd-0df3-44eb-beca-3659000021d4)

The idea is to:

* write your translations files (xliff, yml or php) as usual for at least one language (the default language of your website for example).
* load translations into the database by using a command line.
* freely edit/add translation through an edition page.

The bundle overrides the translator service and provides a DatabaseLoader.
Database translations content is loaded last so it overrides content from xliff, yml and php translations files.
You can also export translations from the database into files in case you need to get translations files with the same content as the database.

Documentation
=============

For installation and configuration refer to [Resources/doc/index.md](https://github.com/lexik/LexikTranslationBundle/blob/master/Resources/doc/index.md)

___________________

Here is a little screen shot of the edition page :)

![edition page screen](https://github.com/lexik/LexikTranslationBundle/raw/master/Resources/doc/screen/grid.jpg)
