<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Command;

use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
// Removed unused use directive
use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Lexik\Bundle\TranslationBundle\Command\ImportTranslationsCommand;

/**
 * Test the translations import command, with option and arguments
 *
 * @covers \Lexik\Bundle\TranslationBundle\Command\ImportTranslationsCommand
 */
class ImportTranslationsCommandTest extends WebTestCase
{
    private static Application $application;

    /**
     *
     */
    public function setUp(): void
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        static::$application = new Application(kernel: static::$kernel);
        /** @var EntityManager $em */
        $em = static::$kernel->getContainer()->get(id: 'doctrine.orm.entity_manager');
        $emProvider = new SingleManagerProvider(entityManager: $em);
        $dropCommand = new DropCommand(entityManagerProvider: $emProvider);
        $createCommand = new CreateCommand(entityManagerProvider: $emProvider);

        static::addDoctrineCommands(dropCommand: $dropCommand, createCommand: $createCommand);

        static::rebuildDatabase();
    }

    /**
     *
     */
    private static function addDoctrineCommands(DropCommand $dropCommand, CreateCommand $createCommand)
    {
        static::$application->add($dropCommand);
        static::$application->add($createCommand);
    }

    /**
     *
     */
    private static function rebuildDatabase()
    {
        static::$kernel->getContainer()->get('doctrine.dbal.default_connection');

        static::runCommand("doctrine:schema:drop", ['--force' => true]);
        static::runCommand("doctrine:schema:create");
    }

    private static function runCommand($commandName, $options = [])
    {
        $options["-e"] = self::$kernel->getEnvironment();

        $options['command'] = $commandName;

        $input = new ArrayInput($options);
        $output = new NullOutput();

        static::$application->setAutoExit(false);
        self::$application->run($input, $output);
    }

    /**
     * Test execute with all the options
     *
     * @group command
     */
    public function testExecute()
    {
        $container = self::$kernel->getContainer();
        static::$application->add(
            command: new ImportTranslationsCommand(
                translator: $container->get('lexik_translation.translator'),
                localeManager: $container->get(LocaleManagerInterface::class),
                fileImporter: $container->get('lexik_translation.importer.file')
            )
        );

        $command = static::$application->find("lexik:translations:import");

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command'       => $command->getName(),
                'bundle'        => 'LexikTranslationBundle',
                '--cache-clear' => true,
                '--force'       => true,
                '--locales'     => ['en', 'fr'],
            ]
        );

        $resultLines = explode("\n", $commandTester->getDisplay());

        $this->assertEquals('# LexikTranslationBundle:', $resultLines[0]);
        $this->assertMatchesRegularExpression('/Using dir (.)+\/Resources\/translations to lookup translation files/', $resultLines[1]);
        $this->assertMatchesRegularExpression('/translations\/LexikTranslationBundle\.((fr)|(en))\.yml" \.\.\. 30 translations/', $resultLines[2]);
        $this->assertMatchesRegularExpression('/translations\/LexikTranslationBundle\.((fr)|(en))\.yml" \.\.\. 30 translations/', $resultLines[3]);
        $this->assertEquals('Removing translations cache files ...', $resultLines[4]);
    }
}
