<?php

namespace AppBundle\Tests\Service;

use AppBundle\Entity\ModuleLog;
use AppBundle\Entity\Types\PlatformId;
use AppBundle\Handler\IplatformsCustomLogHandler;
use AppBundle\Filter\PlatformFilter;
use AppBundle\Service\InMemoryDbService;
use AppBundle\Service\PlatformConfigService;
use AppBundle\Service\SlackService;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\TestUtil;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class InMemoryDbServiceTest
 * @package AppBundle\Tests\Service
 */
class InMemoryDbServiceTest extends AppBaseTest
{

    /**
     * @var InMemoryDbService
     */
    private $inMemoryDbService;

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

        $this->inMemoryDbService =
                $this->createdKernel->getContainer()->get(InMemoryDbService::SERVICE_NAME);

    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_set_get_ok()
    {

        $key = 'keyA';
        $value = 'valueA';

        $this->inMemoryDbService->set(InMemoryDbService::PERFORMANCE_DB, $key, $value, 10);
        $storedValue = $this->inMemoryDbService->get(InMemoryDbService::PERFORMANCE_DB, $key);

        $this->assertEquals($value, $storedValue);

    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_get_expired()
    {

        $key = 'keyA';
        $value = 'valueA';

        $this->inMemoryDbService->set(InMemoryDbService::SESSIONS_DB, $key, $value, 1);
        sleep(2);
        $storedValue = $this->inMemoryDbService->get(InMemoryDbService::SESSIONS_DB, $key);

        $this->assertNull($storedValue);

    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_delete_ok()
    {

        $key = 'keyA';
        $value = 'valueA';

        $this->inMemoryDbService->set(InMemoryDbService::SESSIONS_DB, $key, $value, 10);
        $this->inMemoryDbService->delete(InMemoryDbService::SESSIONS_DB, $key);
        $storedValue = $this->inMemoryDbService->get(InMemoryDbService::SESSIONS_DB, $key);

        $this->assertNull($storedValue);

    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_scan_ok()
    {

        $db = InMemoryDbService::SESSIONS_DB;
        $keyA = 'keyA';
        $valueA = 'valueA';
        $keyB = 'keyB';
        $valueB = 'valueB';

        $this->inMemoryDbService->set($db, $keyA, $valueA, 10);
        $this->inMemoryDbService->set($db, $keyB, $valueB, 10);

        $stored = $this->inMemoryDbService->scan($db, 'key');

        $expected = ['sess_'.$keyA => $valueA, 'sess_'.$keyB => $valueB];
        $this->assertEquals($expected, $stored);

    }

}
