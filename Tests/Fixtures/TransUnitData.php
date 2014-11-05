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
            'messages' => array('fr', 'en', 'br', 'de'),
        	'otherMessages' => array('fr', 'en'),
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

        // add trans unit and translations for key "journey.form.tab_title"
        $translationsByClient = array(
        		'Custom' => array(
					           'fr' => 'Itinéraire Custom',
					           'en' => 'Itinerary'
					        ),
        		'CanalTP' => array(
		        			   'fr' => 'Itinéraire',
		        			   'en' => 'Itinerary',
		        			   'de' => 'Verbindung',
		        			   'br'	=> 'Hent'
        					)
        );

		foreach ($translationsByClient as $client => $translations) {
			// trans unit creation
			$transUnit = $this->createTransUnitInstance($manager);
			$transUnit->setKey('journey.form.tab_title');
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


        // add trans unit and translations for key "schedule.form.tab_title"
        $translationsByClient = array(
        		'Custom' => array(
					           'en' => 'Times Custom'
					        ),
        		'CanalTP' => array(
		        			   'fr' => 'Horaires',
		        			   'en' => 'Times',
		        			   'de' => 'Fahrpläne',
		        			   'br'	=> 'Eurioù'
        					)
        );

    	foreach ($translationsByClient as $client => $translations) {
			// trans unit creation
			$transUnit = $this->createTransUnitInstance($manager);
			$transUnit->setKey('schedule.form.tab_title');
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