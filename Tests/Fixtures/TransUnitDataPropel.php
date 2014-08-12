<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Fixtures;

use Lexik\Bundle\TranslationBundle\Propel\FileQuery;
use Lexik\Bundle\TranslationBundle\Propel\TranslationQuery;
use Lexik\Bundle\TranslationBundle\Propel\TransUnitQuery;
use Lexik\Bundle\TranslationBundle\Propel\File;
use Lexik\Bundle\TranslationBundle\Propel\TransUnit;
use Lexik\Bundle\TranslationBundle\Propel\Translation;

/**
 * Tests fixtures class for Propel.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitDataPropel
{
    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\DataFixtures.FixtureInterface::load()
     */
    public function load(\PropelPDO $con)
    {
        // add files
        $files = array();
        $domains = array(
            'superTranslations' => array('fr', 'en', 'de'),
            'messages' => array('fr', 'en'),
        );

        foreach ($domains as $name => $locales) {
            foreach ($locales as $locale) {
                $file = new File();
                $file->setDomain($name);
                $file->setLocale($locale);
                $file->setExtention('yml');
                $file->setPath('Resources/translations');
                $file->setHash(md5(sprintf('Resources/translations/%s.%s.yml', $name, $locale)));

                $file->save($con);
                $files[$name][$locale] = $file;
            }
        }

        // add translations for "key.say_hello"
        $transUnit = new TransUnit();
        $transUnit->setKey('key.say_hello');
        $transUnit->setDomain('superTranslations');

        $translations = array(
           'fr' => 'salut',
           'en' => 'hello',
           'de' => 'heil',
        );

        foreach ($translations as $locale => $content) {
            $translation = new Translation();
            $translation->setLocale($locale);
            $translation->setContent($content);
            $translation->setFile($files['superTranslations'][$locale]);
            $translation->setTransUnit($transUnit);
            $translation->save($con);
        }

        $transUnit->save($con);

        // add translations for "key.say_goodbye"
        $transUnit = new TransUnit();
        $transUnit->setKey('key.say_goodbye');

        $translations = array(
            'fr' => 'au revoir',
            'en' => 'goodbye',
        );

        foreach ($translations as $locale => $content) {
            $translation = new Translation();
            $translation->setLocale($locale);
            $translation->setContent($content);
            $translation->setFile($files['messages'][$locale]);
            $translation->setTransUnit($transUnit);
            $translation->save($con);
        }

        $transUnit->save($con);

        // add translations for "key.say_wtf"
        $transUnit = new TransUnit();
        $transUnit->setKey('key.say_wtf');

        $translations = array(
            'fr' => 'c\'est quoi ce bordel !?!',
            'en' => 'what the fuck !?!',
        );

        foreach ($translations as $locale => $content) {
            $translation = new Translation();
            $translation->setLocale($locale);
            $translation->setContent($content);
            $translation->setFile($files['messages'][$locale]);
            $translation->setTransUnit($transUnit);
            $translation->save($con);
        }

        $transUnit->save($con);
    }
}
