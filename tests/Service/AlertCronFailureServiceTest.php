<?php

namespace IpartnersBundle\Tests\Service;

use IpartnersBundle\Entity\TaskSemaphore;
use IpartnersBundle\Service\AlertCronFailureService;
use IpartnersBundle\Service\CurlService;
use IpartnersBundle\Service\InMemoryDbService;
use IpartnersBundle\Service\SlackService;
use IpartnersBundle\Tests\IpartnersBaseTest;
use IpartnersBundle\Tests\Util\MockWrap;
use IpartnersBundle\Tests\Util\TestsTimer;
use IpartnersBundle\Tests\TestUtil;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AlertCronFailureServiceTest
 * @package IpartnersBundle\Tests\Service
 */
class AlertCronFailureServiceTest extends IpartnersBaseTest
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

        $this->entityManager->createQueryBuilder()->delete('IpartnersBundle:TaskSemaphore')->getQuery()->execute();

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
