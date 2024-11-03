<?php

namespace IpartnersBundle\Tests\Service;

use IpartnersBundle\Constant\TaskStatus;
use IpartnersBundle\Entity\TaskSemaphore;
use IpartnersBundle\Service\TaskService;
use IpartnersBundle\Tests\IpartnersBaseTest;

/**
 * Class TaskServiceTest
 * @package IpartnersBundle\Tests\Service
 */
class TaskServiceTest extends IpartnersBaseTest
{

    /**
     * @var TaskService
     */
    private $taskService;

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
        $this->taskService =
                $this->createdKernel->getContainer()->get(TaskService::SERVICE_NAME);

        $this->entityManager->beginTransaction();

    }

    /**
     * @test
     */
    public function test_startTask_ok()
    {
        $taskSemaphore = new TaskSemaphore();
        $taskSemaphore->setName('test-task-1');
        $taskSemaphore->setStatus(TaskStatus::INACTIVE);
        $this->entityManager->persist($taskSemaphore);
        $this->entityManager->flush();

        $this->assertTrue($this->taskService->starTask('test-task-1'));

        // task must start ok
        $this->assertEquals(TaskStatus::ACTIVE, $taskSemaphore->getStatus());
        $this->assertNotNull($taskSemaphore->getStartDate());

        // let's test setting last start date 30 min ago
        $startDate = (new \DateTime())->sub(new \DateInterval('PT30M'));
        $taskSemaphore->setStartDate($startDate);

        // task must not restart because it is active
        $this->assertFalse($this->taskService->starTask('test-task-1'));
        $this->assertEquals($taskSemaphore->getStartDate(), $startDate);

        // task must restart because start date was 30 min ago and time limit is 60 secs
        $this->assertTrue($this->taskService->starTask('test-task-1', false, 60));
        $this->assertNotEquals($taskSemaphore->getStartDate(), $startDate);
    }

}
