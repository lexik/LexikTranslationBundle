<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Lexik\Bundle\TranslationBundle\Command\ImportTranslationsCommand;

/**
 * Test the translations import command, with option and arguments
 */
class ImportTranslationsCommandTest extends WebTestCase
{
    /**
     * Test execute with all the options
     */
    public function testExecute()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $application = new Application(static::$kernel);
        $application->add(new ImportTranslationsCommand());

        $command = $application->find("lexik:translations:import");
        $command->setContainer(static::$kernel->getContainer());

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'       => $command->getName(),
            'bundle'        => 'LexikTranslationBundle',
            '--cache-clear' => true,
            '--force'       => true,
            '--locales'     => array('en', 'fr'),
        ));

        $resultLines = explode("\n", $commandTester->getDisplay());

        $this->assertEquals('# LexikTranslationBundle:', $resultLines[0]);
        $this->assertContains('translations/LexikTranslationBundle.en.yml" ... 11 translations', $resultLines[1]);
        $this->assertContains('translations/LexikTranslationBundle.fr.yml" ... 11 translations', $resultLines[2]);
        $this->assertEquals('Removing translations cache files ...', $resultLines[3]);
    }
}