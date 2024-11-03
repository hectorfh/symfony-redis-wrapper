<?php

namespace AppBundle\Tests\Listener;

use AppBundle\Listener\SlowEndpointListener;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\Util\MockWrap;

/**
 * Class SlowEndpointListenerTest
 * @package AppBundle\Tests\Listener
 */
class SlowEndpointListenerTest extends AppBaseTest
{

    /**
     * @var SlowEndpointListener
     */
    private $slowEndpointListener;

    /**
     * Set up.
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $this->slowEndpointListener =
                $this->createdKernel->getContainer()->get(SlowEndpointListener::LISTENER_NAME);
    }

    /**
     * @test
     */
    public function test_onKernelResponse_ok()
    {
        $attributes = new MockWrap();
        $attributes->get = function($name) {
            if ($name == SlowEndpointListener::START_TIME_ATTR) {
                return 1697606213;
            }
        };

        $request = new MockWrap();
        $request->attributes = $attributes;
        $request->getMethod = function() {
            return 'POST';
        };
        $request->getUri = function() {
            return 'https://ipartners.iplatforms.com.au/endpoint1';
        };
        $request->getContent = function() {
            return '{ "attr" : "value" }';
        };

        $event = new MockWrap();
        $event->getRequest = function() use ($request) {
            return $request;
        };
        $event->isMasterRequest = function() {
            return TRUE;
        };

        $errorMsg = NULL;
        $logger = new MockWrap();
        $logger->error = function($msg) use (&$errorMsg) {
            $errorMsg = $msg;
        };
        $this->slowEndpointListener->setLogger($logger);
        $this->slowEndpointListener->onKernelResponse($event);

        $this->assertRegExp(
            '/Slow endpoint, time: .*, uri: POST https:\/\/ipartners.iplatforms.com.au\/endpoint1, body: { "attr" : "value" }/',
            $errorMsg);
    }

}
