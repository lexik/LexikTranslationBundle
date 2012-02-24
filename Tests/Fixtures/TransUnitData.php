<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Fixtures;

use Lexik\Bundle\TranslationBundle\Model\Translation;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;

use Doctrine\Common\DataFixtures\FixtureInterface;

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
    public function load($manager)
    {
        // key.say_hello
        $transUnit = new TransUnit();
        $transUnit->setKey('key.say_hello');
        $transUnit->setDomain('superTranslations');

        $manager->persist($transUnit);
        $manager->flush();

        $translations = array(
           'fr' => 'salut',
           'en' => 'hello',
           'de' => 'heil',
        );

        foreach ($translations as $locale => $content) {
            $translation = new Translation();
            $translation->setLocale($locale);
            $translation->setContent($content);

            $transUnit->addTranslation($translation);
        }

        // key.say_goodbye
        $transUnit = new TransUnit();
        $transUnit->setKey('key.say_goodbye');

        $manager->persist($transUnit);
        $manager->flush();

        $translations = array(
            'fr' => 'au revoir',
            'en' => 'goodbye',
        );

        foreach ($translations as $locale => $content) {
            $translation = new Translation();
            $translation->setLocale($locale);
            $translation->setContent($content);

            $transUnit->addTranslation($translation);
        }

        $manager->flush();

        // key.say_wtf
        $transUnit = new TransUnit();
        $transUnit->setKey('key.say_wtf');

        $manager->persist($transUnit);
        $manager->flush();

        $translations = array(
            'fr' => 'c\'est quoi ce bordel !?!',
            'en' => 'what the fuck !?!',
        );

        foreach ($translations as $locale => $content) {
            $translation = new Translation();
            $translation->setLocale($locale);
            $translation->setContent($content);

            $transUnit->addTranslation($translation);
        }

        $manager->flush();
    }
}