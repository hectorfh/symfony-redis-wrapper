<?php

namespace IpartnersBundle\Tests\Handler;

use IpartnersBundle\Entity\Types\PlatformId;
use IpartnersBundle\Filter\PlatformFilter;
use IpartnersBundle\Handler\InMemoryDbSessionHandler;
use IpartnersBundle\Service\PlatformConfigService;
use IpartnersBundle\Tests\IpartnersBaseTest;
use IpartnersBundle\Tests\TestUtil;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class InMemoryDbSessionHandlerTest
 * @package IpartnersBundle\Tests\Handler
 */
class InMemoryDbSessionHandlerTest extends IpartnersBaseTest
{

    /**
     * @var InMemoryDbSessionHandler
     */
    private $inMemoryDbSessionHandler;

    /**
     * Set up.
     *
     */
    protected function setUp()
    {
        $env = getenv('SYMFONY_TESTS_ENV');
        $options = $env ? ["environment" => $env] : [];

        //start the symfony kernel
        $this->createdKernel = static::createKernel($options);
        $this->createdKernel->boot();

        $this->startTimer();

        $this->entityManager = $this->createdKernel->getContainer()->get('doctrine.orm.entity_manager');

        $platformId = $this->createdKernel->getContainer()->getParameter('platform_id');
        $platformName = $this->createdKernel->getContainer()->getParameter('platform_name');
        $this->setPlatformConfigInRequestStack($platformId, $platformName);

        $this->inMemoryDbSessionHandler = $this->createdKernel->getContainer()->get(InMemoryDbSessionHandler::SERVICE_NAME);

    }

    /**
     * @test
     */
    public function test_read_write_ok()
    {

        $sessionId = 'sessionId';
        $data = 'data';

        $this->inMemoryDbSessionHandler->write($sessionId, $data);
        $storedData = $this->inMemoryDbSessionHandler->read($sessionId);

        $this->assertEquals($data, $storedData);

    }

    /**
     * @test
     */
    public function test_destroy_ok()
    {

        $sessionId = 'sessionId';
        $data = 'data';

        $this->inMemoryDbSessionHandler->write($sessionId, $data);
        $this->inMemoryDbSessionHandler->destroy($sessionId);
        $storedData = $this->inMemoryDbSessionHandler->read($sessionId);

        $this->assertEquals('', $storedData);

    }

}
