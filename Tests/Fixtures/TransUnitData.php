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
        // key.say_hello
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

            $transUnit->addTranslation($translation);
        }

        $manager->persist($transUnit);
        $manager->flush();


        // key.say_goodbye
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

            $transUnit->addTranslation($translation);
        }

        $manager->persist($transUnit);
        $manager->flush();


        // key.say_wtf
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
    protected function createTransUnitInstance($manager)
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
    protected function createTranslationInstance($manager)
    {
        $instance = null;

        if ($manager instanceof \Doctrine\ORM\EntityManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Entity\Translation();
        } else if ($manager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $instance = new \Lexik\Bundle\TranslationBundle\Document\Translation();
        }

        return $instance;
    }
}