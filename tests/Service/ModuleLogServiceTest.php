<?php

namespace AppBundle\Tests\Service;

use AppBundle\Entity\IdentifiedLogError;
use AppBundle\Entity\ModuleLog;
use AppBundle\Entity\Types\PlatformId;
use AppBundle\Handler\IplatformsCustomLogHandler;
use AppBundle\Filter\PlatformFilter;
use AppBundle\Service\ModuleLogService;
use AppBundle\Service\PlatformConfigService;
use AppBundle\Service\QueueService;
use AppBundle\Service\SlackService;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\Util\MockWrap;
use AppBundle\Tests\TestUtil;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ModuleLogServiceTest
 * @package AppBundle\Tests\Service
 */
class ModuleLogServiceTest extends AppBaseTest
{

    /**
     * @var IplatformsCustomLogHandler
     */
    private $iplatformsCustomLogHandler;

    /**
     * @var ModuleLogService
     */
    private $moduleLogService;
    
    /**
     * @var QueueService
     */
    private $queueService;
    
    /**
     * @var SlackService
     */
    private $slackService;
    
    private $record1;

    private $record2;

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

        $this->iplatformsCustomLogHandler =
                $this->createdKernel->getContainer()->get(IplatformsCustomLogHandler::SERVICE_NAME);

        $this->moduleLogService =
                $this->createdKernel->getContainer()->get(ModuleLogService::SERVICE_NAME);

        $this->slackService =
                new MockWrap($this->createdKernel->getContainer()->get(SlackService::SERVICE_NAME));

        $this->moduleLogService->setSlackService($this->slackService);

        $this->queueService =
                $this->createdKernel->getContainer()->get(QueueService::SERVICE_NAME);

        $this->record1 = [
                'level'         => Logger::CRITICAL,
                'level_name'    => 'CRITICAL',
                'message'       => 'error message ' . time(),
                'datetime'      => new \DateTime()];

        $this->record2 = [
                'level'         => Logger::WARNING,
                'level_name'    => 'WARNING',
                'message'       => 'error message ' . (time() + 1),
                'datetime'      => new \DateTime()];

    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_view_ok()
    {

        $this->queueService->clear(QueueService::ERROR_QUEUE);
        $this->entityManager->createQueryBuilder()->delete('AppBundle:ModuleLog')->getQuery()->execute();
        $this->entityManager->createQueryBuilder()
            ->delete(IdentifiedLogError::class, 'm')
            ->getQuery()
            ->execute();

        $this->iplatformsCustomLogHandler->write($this->record1);
        $this->iplatformsCustomLogHandler->write($this->record2);
        $this->moduleLogService->pullErrors();

        /** @var ModuleLog[] $moduleLog */
        $moduleLogs =
                $this->entityManager
                ->getRepository('AppBundle:ModuleLog')
                ->findBy(['message' => $this->record1['message']]);

        $this->assertEquals(1, count($moduleLogs));

        /** @var ModuleLog $moduleLog */
        $moduleLog = $moduleLogs[0];

        $this->assertEquals($this->record1['message'], $moduleLog->getMessage());
        $this->assertEquals(PlatformId::IPARTNERS, $moduleLog->getPlatformId());

    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_notifyErrors_ok()
    {
        $this->queueService->clear(QueueService::ERROR_QUEUE);
        $this->entityManager->createQueryBuilder()->delete('AppBundle:ModuleLog')->getQuery()->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(IdentifiedLogError::class, 'm')
            ->getQuery()
            ->execute();

        $this->iplatformsCustomLogHandler->write($this->record1);
        $this->iplatformsCustomLogHandler->write($this->record2);

        $sendMessageOfLogErrorCalled = false;
        $this->slackService->sendMessageOfLogError = function() use (&$sendMessageOfLogErrorCalled) {
            $sendMessageOfLogErrorCalled = true;
        };

        $this->moduleLogService->pullErrors();
        $this->assertTrue($sendMessageOfLogErrorCalled);
    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_notifyErrors_throwsException()
    {
        $this->queueService->clear(QueueService::ERROR_QUEUE);
        $this->entityManager->createQueryBuilder()->delete('AppBundle:ModuleLog')->getQuery()->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(IdentifiedLogError::class, 'm')
            ->getQuery()
            ->execute();

        $this->iplatformsCustomLogHandler->write($this->record1);

        $this->slackService->sendMessageOfLogError = function() use (&$sendMessageOfLogErrorCalled) {
            throw new \Exception("Just another exception.");
        };

        $this->moduleLogService->pullErrors();
    }

    public function test_purgeLog_ok()
    {

        $this->queueService->clear(QueueService::ERROR_QUEUE);

        $this->entityManager->createQueryBuilder()
            ->delete(ModuleLog::class, 'm')
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(IdentifiedLogError::class, 'm')
            ->getQuery()
            ->execute();

        $record = [
                'level'         => Logger::CRITICAL,
                'level_name'    => 'CRITICAL',
                'message'       => 'error message ' . time(),
                'datetime'      => new \DateTime('@'.strtotime('-6 days'))];

        $this->iplatformsCustomLogHandler->write($record);
        $this->moduleLogService->pullErrors();

        $record = [
                'level'         => Logger::ERROR,
                'level_name'    => 'ERROR',
                'message'       => 'error message ' . (time() + 1),
                'datetime'      => new \DateTime('@'.strtotime('-4 days'))];

        $this->iplatformsCustomLogHandler->write($record);
        $this->moduleLogService->pullErrors();

        $this->moduleLogService->purgeLog();

        /** @var ModuleLog[] $moduleLog */
        $moduleLogs =
                $this->entityManager
                        ->getRepository('AppBundle:ModuleLog')
                        ->findAll();

        $this->assertEquals(1, count($moduleLogs));

    }

    public function test_identifiedError_ok()
    {
        $this->queueService->clear(QueueService::ERROR_QUEUE);

        $this->entityManager->createQueryBuilder()
            ->delete(ModuleLog::class, 'm')
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(IdentifiedLogError::class, 'm')
            ->getQuery()
            ->execute();

        $identified = new IdentifiedLogError();
        $identified->setPattern('.*2');
        $identified->setDescription('');
        $identified->setCount(0);
        $identified->setCreatedDate(new \DateTime());
        $this->entityManager->persist($identified);
        $this->entityManager->flush();

        $record = [
                'level'         => Logger::ERROR,
                'level_name'    => 'ERROR',
                'message'       => 'error message 1',
                'datetime'      => new \DateTime()
        ];
        $this->iplatformsCustomLogHandler->write($record);

        $record = [
                'level'         => Logger::ERROR,
                'level_name'    => 'ERROR',
                'message'       => 'error message 2',
                'datetime'      => new \DateTime()
        ];
        $this->iplatformsCustomLogHandler->write($record);

        $sendMessageOfLogErrorCount = 0;
        $this->slackService->sendMessageOfLogError = function() use (&$sendMessageOfLogErrorCount) {
            $sendMessageOfLogErrorCount ++;
        };

        $this->moduleLogService->pullErrors();

        $this->assertEquals(1, $sendMessageOfLogErrorCount);

        /** @var ModuleLog[] $moduleLog */
        $moduleLogs =
                $this->entityManager
                        ->getRepository('AppBundle:ModuleLog')
                        ->findAll();

        $this->assertEquals(2, count($moduleLogs));

        /** @var IdentifiedLogError[] $identifiedLogError */
        $identifiedLogErrors =
                $this->entityManager
                        ->getRepository('AppBundle:IdentifiedLogError')
                        ->findAll();

        $this->assertEquals(1, $identifiedLogErrors[0]->getCount());
    }

}
