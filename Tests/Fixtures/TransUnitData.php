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

        // add trans unit and translations for key "key.say_hello"
        $translationsByClient = array(
        		'Custom' => array(
					           'fr' => 'Salut Custom',
					           'en' => 'Hello Custom',
        				       'de' => 'Heil Custom'
					        ),
        		'CanalTP' => array(
		        			   'fr' => 'Salut',
		        			   'en' => 'Hello',
		        			   'de' => 'Heil'
        					)
        );

		foreach ($translationsByClient as $client => $translations) {
			// trans unit creation
			$transUnit = $this->createTransUnitInstance($manager);
			$transUnit->setKey('key.say_hello');
			$transUnit->setDomain('superTranslations');
			$transUnit->setClient($client);

			// translations creation
	        foreach ($translations as $locale => $content) {
	            $translation = $this->createTranslationInstance($manager);
	            $translation->setLocale($locale);
	            $translation->setContent($content);
	            $translation->setFile($files['superTranslations'][$locale]);	
	            $transUnit->addTranslation($translation);
	        }
	        
	        $manager->persist($transUnit);
	        $manager->flush();
		}        


        // add trans unit and translations for key "key.say_goodbye"
        $translationsByClient = array(
        		'Custom' => array(
					           'fr' => 'Au revoir Custom',
            				   'en' => 'Goodbye Custom'
					        ),
        		'CanalTP' => array(
		        			   'fr' => 'Au revoir Custom',
		        			   'en' => 'Goodbye Custom'
        					)
        );

    	foreach ($translationsByClient as $client => $translations) {
			// trans unit creation
			$transUnit = $this->createTransUnitInstance($manager);
			$transUnit->setKey('key.say_goodbye');
			$transUnit->setDomain('messages');
			$transUnit->setClient($client);

			// translations creation
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
		
		// add trans unit and translations for key "key.say_wtf"
		$translationsByClient = array(
				'Custom' => array(
						'fr' => 'C\'est quoi ce bordel !?! Custom',
						'en' => 'What the fuck !?!'
				),
				'CanalTP' => array(
						'fr' => 'C\'est quoi ce bordel !?!',
						'en' => 'What the fuck !?! Custom'
				)
		);
		
		foreach ($translationsByClient as $client => $translations) {
			// trans unit creation
			$transUnit = $this->createTransUnitInstance($manager);
			$transUnit->setKey('key.say_wtf');
			$transUnit->setDomain('messages');
			$transUnit->setClient($client);
		
			// translations creation
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