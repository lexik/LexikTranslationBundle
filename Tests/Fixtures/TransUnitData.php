<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Tests fixtures class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitData implements FixtureInterface
{
    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\DataFixtures.FixtureInterface::load()
     */
    public function load(ObjectManager $manager)
    {
        // add files
        $files = array();
        $domains = array(
            'superTranslations' => array('fr', 'en', 'de'),
            'messages' => array('fr', 'en'),
        );

        foreach ($domains as $name => $locales) {
            foreach ($locales as $locale) {
                $file = $this->createFileInstance($manager);
                $file->setDomain($name);
                $file->setLocale($locale);
                $file->setExtention('yml');
                $file->setPath('Resources/translations');
                $file->setHash(md5(sprintf('Resources/translations/%s.%s.yml', $name, $locale)));

                $manager->persist($file);
                $files[$name][$locale] = $file;
            }
        }

        $manager->flush();

        // add translations for "key.say_hello"
        $transUnit = $this->createTransUnitInstance($manager);
        $transUnit->setKey('key.say_hello');
        $transUnit->setDomain('superTranslations');

        $translations = array(
           'fr' => 'salut',
           'en' => 'hello',
           'de' => 'heil',
        );

        foreach ($translations as $locale => $content) {
            $translation = $this->createTranslationInstance($manager);
            $translation->setLocale($locale);
            $translation->setContent($content);
            $translation->setFile($files['superTranslations'][$locale]);

            $transUnit->addTranslation($translation);
        }

        $manager->persist($transUnit);
        $manager->flush();


        // add translations for "key.say_goodbye"
        $transUnit = $this->createTransUnitInstance($manager);
        $transUnit->setKey('key.say_goodbye');

        $translations = array(
            'fr' => 'au revoir',
            'en' => 'goodbye',
        );

        foreach ($translations as $locale => $content) {
            $translation = $this->createTranslationInstance($manager);
            $translation->setLocale($locale);
            $translation->setContent($content);
            $translation->setFile($files['messages'][$locale]);

            $transUnit->addTranslation($translation);
        }

        $manager->persist($transUnit);
        $manager->flush();


        // add translations for "key.say_wtf"
        $transUnit = $this->createTransUnitInstance($manager);
        $transUnit->setKey('key.say_wtf');

        $translations = array(
            'fr' => 'c\'est quoi ce bordel !?!',
            'en' => 'what the fuck !?!',
        );

        foreach ($translations as $locale => $content) {
            $translation = $this->createTranslationInstance($manager);
            $translation->setLocale($locale);
            $translation->setContent($content);
            $translation->setFile($files['messages'][$locale]);

            $transUnit->addTranslation($translation);
        }

        $manager->persist($transUnit);
        $manager->flush();
    }

    /**
     * Create the right TransUnit instance.
     *
     * @param ObjectManager $manager
     */
    protected function createTransUnitInstance(ObjectManager $manager)
    {
        $instance = null;

        if ($manager instanceof \Doctrine\ORM\EntityManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Entity\TransUnit();
        } else if ($manager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Document\TransUnit();
        }

        return $instance;
    }

    /**
     * Create the right Translation instance.
     *
     * @param ObjectManager $manager
     */
    protected function createTranslationInstance(ObjectManager $manager)
    {
        $instance = null;

        if ($manager instanceof \Doctrine\ORM\EntityManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Entity\Translation();
        } else if ($manager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Document\Translation();
        }

        return $instance;
    }

    /**
     * Create the right File instance.
     *
     * @param ObjectManager $manager
     */
    protected function createFileInstance(ObjectManager $manager)
    {
        $instance = null;

        if ($manager instanceof \Doctrine\ORM\EntityManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Entity\File();
        } else if ($manager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Document\File();
        }

        return $instance;
    }
}