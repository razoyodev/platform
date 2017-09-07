<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroYamlParser;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\DoctrineIsolator;

class FixturesChecker implements HealthCheckerInterface
{
    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /**
     * @var OroYamlParser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param FixtureLoader $fixtureLoader
     * @param OroYamlParser $parser
     */
    public function __construct(FixtureLoader $fixtureLoader, OroYamlParser $parser)
    {
        $this->fixtureLoader = $fixtureLoader;
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeFeatureTested::BEFORE => 'checkFixtures'
        ];
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function checkFixtures(BeforeFeatureTested $event)
    {
        $fixtureFiles = DoctrineIsolator::getFixtureFiles($event->getFeature()->getTags());
        foreach ($fixtureFiles as $fixtureFile) {
            try {
                $file = $this->fixtureLoader->findFile($fixtureFile);
                $this->parser->parse($file);
            } catch (\Exception $e) {
                $message = 'Error while find and parse "'.$fixtureFile.'" fixture'.PHP_EOL;
                $message .= '   Suite: '.$event->getSuite()->getName().PHP_EOL;
                $message .= '   Feature: '.$event->getFeature()->getFile().PHP_EOL;
                $message .= '   '.$e->getMessage();
                $this->addError($message);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFailure()
    {
        return !empty($this->errors);
    }

    /**
     * @param string $message
     */
    private function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }
}