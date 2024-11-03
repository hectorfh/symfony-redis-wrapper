<?php

namespace AppBundle\Tests\Service;

use AppBundle\Entity\TaskSemaphore;
use AppBundle\Service\AlertCronFailureService;
use AppBundle\Service\CurlService;
use AppBundle\Service\InMemoryDbService;
use AppBundle\Service\SlackService;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\Util\MockWrap;
use AppBundle\Tests\Util\TestsTimer;
use AppBundle\Tests\TestUtil;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AlertCronFailureServiceTest
 * @package AppBundle\Tests\Service
 */
class AlertCronFailureServiceTest extends AppBaseTest
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

    }

    /**
     * @test
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function test_alertFailures_ok()
    {

        $curlService =
            new MockWrap($this->createdKernel->getContainer()->get(CurlService::SERVICE_NAME));
        $slackService = $this->createdKernel->getContainer()->get(SlackService::SERVICE_NAME);
        $slackService->setCurlService($curlService);
        $slackService->setWebHookUrlCronFailure("slackurl");

        $msg = NULL;
        $curlService->post = function($webHook, $msgEncoded) use (&$msg) {
            $msg = $msgEncoded;
        };

        $this->entityManager->createQueryBuilder()->delete('AppBundle:TaskSemaphore')->getQuery()->execute();

        $ts = new TaskSemaphore();
        $ts->setName("test");
        $startDate = new \DateTime();
        $startDate->add(\DateInterval::createFromDateString('-61 minute'));
        $ts->setStartDate($startDate);
        $this->entityManager->persist($ts);

        $ts = new TaskSemaphore();
        $ts->setName('update-investor-insight-email-ipartners');
        $startDate = new \DateTime();
        $startDate->add(\DateInterval::createFromDateString('-61 minute'));
        $ts->setStartDate($startDate);
        $this->entityManager->persist($ts);
        $this->entityManager->flush();

        $alertCronFailureService = $this->createdKernel->getContainer()->get(AlertCronFailureService::SERVICE_NAME);
        $alertCronFailureService->alertFailures();

        $expected = '/^{"text":"Failed tasks:\\\ntest .*\\\nupdate-investor-insight-email-ipartners .*\\\n","type":"mrkdwn"}$/';
        $this->assertEquals(1, preg_match($expected, $msg));

    }

}
