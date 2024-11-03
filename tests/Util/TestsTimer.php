<?php


namespace IpartnersBundle\Tests\Util;

use IpartnersBundle\Service\InMemoryDbService;


class TestsTimer
{

    const TESTS_TIMER_TTL = 10000;

    static public function start($inMemoryDbService, $testObj)
    {
        $key = get_class($testObj).'::'.$testObj->getName();
        $value = microtime(true);
        $inMemoryDbService->set(InMemoryDbService::TESTS_TIMER, $key, $value, self::TESTS_TIMER_TTL);
    }

    static public function end($inMemoryDbService, $className, $functionName)
    {
        $key = $className.'::'.$functionName;
        $startTime = $inMemoryDbService->get(InMemoryDbService::TESTS_TIMER, $key);
        $value = microtime(true) - $startTime;
        $inMemoryDbService->set(InMemoryDbService::TESTS_TIMER, $key, $value, self::TESTS_TIMER_TTL);
    }

}
