<?php

namespace FOM\Component\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SharedApplicationWebTestCase extends WebTestCase
{
    protected static $client;
    protected static $application;
    protected static $options = array();

    public static function setUpBeforeClass()
    {
        //self::runCommand('cache:clear --no-debug');
        //self::runCommand('cache:warmup --no-debug');
        self::runCommand('doctrine:database:drop --force');
        self::runCommand('doctrine:database:create');
        self::runCommand('doctrine:schema:create');
        self::runCommand('fom:user:resetroot --username=root --password=root --email=root@example.com --silent');
        self::runCommand('doctrine:fixtures:load --fixtures=./mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Epsg/ --append');
    }

    protected static function runCommand($command) {
        $command = sprintf('%s --quiet', $command);
        return self::getApplication()->run(new StringInput($command));
    }


    protected static function getApplication() {
        if(!static::$application) {
            $options = static::$options;
            static::$client = static::createClient($options);

            static::$application = new Application(static::$client->getKernel());
            static::$application->setAutoExit(false);
        }

        return static::$application;
    }
}
