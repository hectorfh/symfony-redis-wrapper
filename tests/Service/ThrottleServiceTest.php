<?php

namespace IpartnersBundle\Tests\Service;

use IpartnersBundle\Service\ThrottleService;
use IpartnersBundle\Tests\IpartnersBaseTest;


/**
 * Class ThrottleServiceTest
 * @package IpartnersBundle\Tests\Service
 */
class ThrottleServiceTest extends IpartnersBaseTest
{

    /**
     * @var ThrottleService
     */
    private $throttleService;

    /**
     * Set up.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->throttleService =
                $this->createdKernel->getContainer()->get(ThrottleService::SERVICE_NAME);

    }

    /**
     * @test
     */
    public function test_throttle_ok()
    {
        $result = $this->throttleService->throttle('/admin/dashboard/trustregister/details/1234/list', '101');
        $this->assertTrue($result);

        $result = $this->throttleService->throttle('/admin/dashboard/otraurl/1234/list', '101');
        $this->assertFalse($result);

        $result = $this->throttleService->throttle('/admin/dashboard/otraurl/1234/list', NULL);
        $this->assertFalse($result);

        for ($i = 0; $i < 11; $i ++) {
            $result = $this->throttleService->throttle('/admin/dashboard/trustregister/details/1234/list', 10);
        }
        $this->assertTrue($result);

        for ($i = 0; $i < 11; $i ++) {
            $result = $this->throttleService->throttle('/admin/dashboard/otraurl/1234/list', 10);
        }
        $this->assertFalse($result);
    }

}
