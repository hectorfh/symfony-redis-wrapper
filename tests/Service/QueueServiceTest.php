<?php

namespace AppBundle\Tests\Service;

use AppBundle\Entity\Types\PlatformId;
use AppBundle\Filter\PlatformFilter;
use AppBundle\Service\QueueService;
use AppBundle\Service\PlatformConfigService;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\TestUtil;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class QueueServiceTest
 * @package AppBundle\Tests\Service
 */
class QueueServiceTest extends AppBaseTest
{

    /**
     * @var QueueService
     */
    private $queueService;

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

        $this->queueService =
                $this->createdKernel->getContainer()->get(QueueService::SERVICE_NAME);

    }

    /**
     * @test
     */
    public function test_push_pop_ok()
    {
        $this->queueService->clear(QueueService::ASYNC_PROC_QUEUE);

        $array = ['key' => 'value'];

        $this->queueService->push(QueueService::ASYNC_PROC_QUEUE, $array);
        $stored = $this->queueService->pop(QueueService::ASYNC_PROC_QUEUE);

        $this->assertEquals($array, $stored);

    }

    /**
     * @test
     */
    public function test_pop_with_trim_ok()
    {
        $this->queueService->clear(QueueService::TEST_QUEUE);

        $array1 = ['key1' => 'value1'];
        $array2 = ['key2' => 'value2'];

        $this->queueService->push(QueueService::TEST_QUEUE, $array1);
        $this->queueService->push(QueueService::TEST_QUEUE, $array2);
        $stored = $this->queueService->pop(QueueService::TEST_QUEUE);
        $this->assertEquals($array1, $stored);
        $stored = $this->queueService->pop(QueueService::TEST_QUEUE);
        $this->assertNull($stored);

    }

}
