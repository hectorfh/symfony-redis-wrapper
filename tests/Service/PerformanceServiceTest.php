<?php

namespace AppBundle\Tests\Service;

use AppBundle\Service\PerformanceService;
use AppBundle\Service\SlackService;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\MockObj;
use AppBundle\Tests\TestUtil;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PerformanceServiceTest
 * @package AppBundle\Tests\Service
 */
class PerformanceServiceTest extends AppBaseTest
{

    /**
     * @var performanceService
     */
    private $performanceService;

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

        $this->performanceService =
                $this->createdKernel->getContainer()->get(PerformanceService::SERVICE_NAME);

    }

    public function test_postPerformanceReport_ok()
    {
        $this->performanceService->recordRequestTime('/', 0.1053434);
        $this->performanceService->recordRequestTime('/', 0.8053434);
        $this->performanceService->recordRequestTime('/a/43/x', 0.3213464);
        $this->performanceService->recordRequestTime('/a/43/x', 0.4343221);
        $this->performanceService->recordRequestTime('/b/a', 0.204534);
        $this->performanceService->collectRecords();
        $this->performanceService->recordRequestTime('/b/a', 0.3132);
        $this->performanceService->recordRequestTime('/b/a?a=3&b=4', 0.04534);
        $this->performanceService->recordRequestTime('/b/44,434,43;43', 0.543432);
        $this->performanceService->recordRequestTime('/c/f/g', 0.054323);
        $this->performanceService->recordRequestTime('/c/f/g', 2.124323);

        $this->performanceService->collectRecords();

        $slackService = new MockObj();
        $slackService->sendMessageOfPerformanceReport = function($msg) use (&$performanceReports) {
            $performanceReports[] = $msg;
        };
        $this->performanceService->setSlackService($slackService);

        $this->performanceService->postDailyReport();

        // order by p95
        $expected = <<<EOD
```| Endpoint |    Count |   Med |   P95 |   Max |
| ------ | -------- | ----- | ----- | ----- |
| /c/f/g |        2 | 1.089 | 2.124 | 2.124 |
| /      |        2 | 0.455 | 0.805 | 0.805 |
| /b/#   |        1 | 0.543 | 0.543 | 0.543 |
| /a/#/x |        2 | 0.378 | 0.434 | 0.434 |
| /b/a   |        3 | 0.205 | 0.313 | 0.313 |```
EOD;

        $this->assertEquals($expected, $performanceReports[0]);

        // order by max
        $expected = <<<EOD
```| Endpoint |    Count |   Med |   P95 |   Max |
| ------ | -------- | ----- | ----- | ----- |
| /c/f/g |        2 | 1.089 | 2.124 | 2.124 |
| /      |        2 | 0.455 | 0.805 | 0.805 |
| /b/#   |        1 | 0.543 | 0.543 | 0.543 |
| /a/#/x |        2 | 0.378 | 0.434 | 0.434 |
| /b/a   |        3 | 0.205 | 0.313 | 0.313 |```
EOD;

        $this->assertEquals($expected, $performanceReports[1]);

        // order by count
        $expected = <<<EOD
```| Endpoint |    Count |   Med |   P95 |   Max |
| ------ | -------- | ----- | ----- | ----- |
| /b/a   |        3 | 0.205 | 0.313 | 0.313 |
| /c/f/g |        2 | 1.089 | 2.124 | 2.124 |
| /      |        2 | 0.455 | 0.805 | 0.805 |
| /a/#/x |        2 | 0.378 | 0.434 | 0.434 |
| /b/#   |        1 | 0.543 | 0.543 | 0.543 |```
EOD;

        $this->assertEquals($expected, $performanceReports[2]);

        // check for deletion
        $this->performanceService->recordRequestTime('/c/f/g', 1.974323);

        $this->performanceService->collectRecords();

        $performanceReports = [];
        $this->performanceService->postDailyReport();

        $expected = <<<EOD
```| Endpoint |    Count |   Med |   P95 |   Max |
| ------ | -------- | ----- | ----- | ----- |
| /c/f/g |        1 | 1.974 | 1.974 | 1.974 |```
EOD;

        $this->assertEquals($expected, $performanceReports[0]);

        $performanceReports = [];
        $this->performanceService->postWeeklyReport();

        // order by p95
        $expected = <<<EOD
```| Endpoint |    Count |   Med |   P95 |   Max |
| ------ | -------- | ----- | ----- | ----- |
| /c/f/g |        3 | 1.532 | 2.124 | 2.124 |
| /      |        2 | 0.455 | 0.805 | 0.805 |
| /b/#   |        1 | 0.543 | 0.543 | 0.543 |
| /a/#/x |        2 | 0.378 | 0.434 | 0.434 |
| /b/a   |        3 | 0.205 | 0.313 | 0.313 |```
EOD;

        $this->assertEquals($expected, $performanceReports[0]);

        // order by max
        $expected = <<<EOD
```| Endpoint |    Count |   Med |   P95 |   Max |
| ------ | -------- | ----- | ----- | ----- |
| /c/f/g |        3 | 1.532 | 2.124 | 2.124 |
| /      |        2 | 0.455 | 0.805 | 0.805 |
| /b/#   |        1 | 0.543 | 0.543 | 0.543 |
| /a/#/x |        2 | 0.378 | 0.434 | 0.434 |
| /b/a   |        3 | 0.205 | 0.313 | 0.313 |```
EOD;

        $this->assertEquals($expected, $performanceReports[1]);

        // order by count
        $expected = <<<EOD
```| Endpoint |    Count |   Med |   P95 |   Max |
| ------ | -------- | ----- | ----- | ----- |
| /c/f/g |        3 | 1.532 | 2.124 | 2.124 |
| /b/a   |        3 | 0.205 | 0.313 | 0.313 |
| /      |        2 | 0.455 | 0.805 | 0.805 |
| /a/#/x |        2 | 0.378 | 0.434 | 0.434 |
| /b/#   |        1 | 0.543 | 0.543 | 0.543 |```
EOD;

        $this->assertEquals($expected, $performanceReports[2]);

    }

    public function test_postPerformanceReport_slice_ok()
    {
        $this->performanceService->recordRequestTime('/', 0.1053434, 1);
        $this->performanceService->recordRequestTime('/a/43/x', 0.3213464);
        $this->performanceService->recordRequestTime('/a/43/x', 0.4343221);
        $this->performanceService->recordRequestTime('/b/a', 0.204534);
        $this->performanceService->collectRecords();
        $this->performanceService->recordRequestTime('/b/a', 0.3132);
        $this->performanceService->recordRequestTime('/b/a?a=3&b=4', 0.04534);
        $this->performanceService->recordRequestTime('/b/44,434,43;43', 0.543432);
        $this->performanceService->recordRequestTime('/c/f/g', 0.054323);
        $this->performanceService->recordRequestTime('/c/f/g', 2.124323);

        for ($i = 0; $i < 50; $i++) {
            $fi = (1000 + $i) / 1000;
            $this->performanceService->recordRequestTime("/t/t$i", $fi);
        }

        $this->performanceService->collectRecords();

        $slackService = new MockObj();
        $slackService->sendMessageOfPerformanceReport = function($msg) use (&$performanceReports) {
            $performanceReports[] = $msg;
        };
        $this->performanceService->setSlackService($slackService);

        $this->performanceService->postDailyReport();

        // order by p95

        $expected = <<<EOD
```| Endpoint  |    Count |   Med |   P95 |   Max |
| --------- | -------- | ----- | ----- | ----- |
| /c/f/g    |        2 | 1.089 | 2.124 | 2.124 |
| /t/t49    |        1 | 1.049 | 1.049 | 1.049 |
| /t/t48    |        1 | 1.048 | 1.048 | 1.048 |
| /t/t47    |        1 | 1.047 | 1.047 | 1.047 |
| /t/t46    |        1 | 1.046 | 1.046 | 1.046 |
| /t/t45    |        1 | 1.045 | 1.045 | 1.045 |
| /t/t44    |        1 | 1.044 | 1.044 | 1.044 |
| /t/t43    |        1 | 1.043 | 1.043 | 1.043 |
| /t/t42    |        1 | 1.042 | 1.042 | 1.042 |
| /t/t41    |        1 | 1.041 | 1.041 | 1.041 |
| /t/t40    |        1 | 1.040 | 1.040 | 1.040 |
| /t/t39    |        1 | 1.039 | 1.039 | 1.039 |
| /t/t38    |        1 | 1.038 | 1.038 | 1.038 |
| /t/t37    |        1 | 1.037 | 1.037 | 1.037 |
| /t/t36    |        1 | 1.036 | 1.036 | 1.036 |
| /t/t35    |        1 | 1.035 | 1.035 | 1.035 |
| /t/t34    |        1 | 1.034 | 1.034 | 1.034 |
| /t/t33    |        1 | 1.033 | 1.033 | 1.033 |
| /t/t32    |        1 | 1.032 | 1.032 | 1.032 |
| /t/t31    |        1 | 1.031 | 1.031 | 1.031 |
| /t/t30    |        1 | 1.030 | 1.030 | 1.030 |
| /t/t29    |        1 | 1.029 | 1.029 | 1.029 |
| /t/t28    |        1 | 1.028 | 1.028 | 1.028 |
| /t/t27    |        1 | 1.027 | 1.027 | 1.027 |
| /t/t26    |        1 | 1.026 | 1.026 | 1.026 |
| /t/t25    |        1 | 1.025 | 1.025 | 1.025 |
| /t/t24    |        1 | 1.024 | 1.024 | 1.024 |
| /t/t23    |        1 | 1.023 | 1.023 | 1.023 |
| /t/t22    |        1 | 1.022 | 1.022 | 1.022 |
| /t/t21    |        1 | 1.021 | 1.021 | 1.021 |
| Others... |       28 | 1.008 | 1.019 | 1.020 |```
EOD;

        $this->assertEquals($expected, $performanceReports[0]);

        // order by max

        $expected = <<<EOD
```| Endpoint  |    Count |   Med |   P95 |   Max |
| --------- | -------- | ----- | ----- | ----- |
| /c/f/g    |        2 | 1.089 | 2.124 | 2.124 |
| /t/t49    |        1 | 1.049 | 1.049 | 1.049 |
| /t/t48    |        1 | 1.048 | 1.048 | 1.048 |
| /t/t47    |        1 | 1.047 | 1.047 | 1.047 |
| /t/t46    |        1 | 1.046 | 1.046 | 1.046 |
| /t/t45    |        1 | 1.045 | 1.045 | 1.045 |
| /t/t44    |        1 | 1.044 | 1.044 | 1.044 |
| /t/t43    |        1 | 1.043 | 1.043 | 1.043 |
| /t/t42    |        1 | 1.042 | 1.042 | 1.042 |
| /t/t41    |        1 | 1.041 | 1.041 | 1.041 |
| /t/t40    |        1 | 1.040 | 1.040 | 1.040 |
| /t/t39    |        1 | 1.039 | 1.039 | 1.039 |
| /t/t38    |        1 | 1.038 | 1.038 | 1.038 |
| /t/t37    |        1 | 1.037 | 1.037 | 1.037 |
| /t/t36    |        1 | 1.036 | 1.036 | 1.036 |
| /t/t35    |        1 | 1.035 | 1.035 | 1.035 |
| /t/t34    |        1 | 1.034 | 1.034 | 1.034 |
| /t/t33    |        1 | 1.033 | 1.033 | 1.033 |
| /t/t32    |        1 | 1.032 | 1.032 | 1.032 |
| /t/t31    |        1 | 1.031 | 1.031 | 1.031 |
| /t/t30    |        1 | 1.030 | 1.030 | 1.030 |
| /t/t29    |        1 | 1.029 | 1.029 | 1.029 |
| /t/t28    |        1 | 1.028 | 1.028 | 1.028 |
| /t/t27    |        1 | 1.027 | 1.027 | 1.027 |
| /t/t26    |        1 | 1.026 | 1.026 | 1.026 |
| /t/t25    |        1 | 1.025 | 1.025 | 1.025 |
| /t/t24    |        1 | 1.024 | 1.024 | 1.024 |
| /t/t23    |        1 | 1.023 | 1.023 | 1.023 |
| /t/t22    |        1 | 1.022 | 1.022 | 1.022 |
| /t/t21    |        1 | 1.021 | 1.021 | 1.021 |
| Others... |       28 | 1.008 | 1.019 | 1.020 |```
EOD;

        $this->assertEquals($expected, $performanceReports[1]);

        // order by count

        $expected = <<<EOD
```| Endpoint  |    Count |   Med |   P95 |   Max |
| --------- | -------- | ----- | ----- | ----- |
| /b/a      |        3 | 0.205 | 0.313 | 0.313 |
| /c/f/g    |        2 | 1.089 | 2.124 | 2.124 |
| /a/#/x    |        2 | 0.378 | 0.434 | 0.434 |
| /t/t10    |        1 | 1.010 | 1.010 | 1.010 |
| /t/t20    |        1 | 1.020 | 1.020 | 1.020 |
| /t/t19    |        1 | 1.019 | 1.019 | 1.019 |
| /t/t18    |        1 | 1.018 | 1.018 | 1.018 |
| /t/t17    |        1 | 1.017 | 1.017 | 1.017 |
| /t/t16    |        1 | 1.016 | 1.016 | 1.016 |
| /t/t15    |        1 | 1.015 | 1.015 | 1.015 |
| /t/t14    |        1 | 1.014 | 1.014 | 1.014 |
| /t/t13    |        1 | 1.013 | 1.013 | 1.013 |
| /t/t12    |        1 | 1.012 | 1.012 | 1.012 |
| /t/t11    |        1 | 1.011 | 1.011 | 1.011 |
| /t/t8     |        1 | 1.008 | 1.008 | 1.008 |
| /t/t9     |        1 | 1.009 | 1.009 | 1.009 |
| /t/t22    |        1 | 1.022 | 1.022 | 1.022 |
| /t/t7     |        1 | 1.007 | 1.007 | 1.007 |
| /t/t6     |        1 | 1.006 | 1.006 | 1.006 |
| /t/t5     |        1 | 1.005 | 1.005 | 1.005 |
| /t/t4     |        1 | 1.004 | 1.004 | 1.004 |
| /t/t3     |        1 | 1.003 | 1.003 | 1.003 |
| /t/t2     |        1 | 1.002 | 1.002 | 1.002 |
| /t/t1     |        1 | 1.001 | 1.001 | 1.001 |
| /t/t0     |        1 | 1.000 | 1.000 | 1.000 |
| /b/#      |        1 | 0.543 | 0.543 | 0.543 |
| /t/t21    |        1 | 1.021 | 1.021 | 1.021 |
| /t/t23    |        1 | 1.023 | 1.023 | 1.023 |
| /t/t49    |        1 | 1.049 | 1.049 | 1.049 |
| /t/t37    |        1 | 1.037 | 1.037 | 1.037 |
| Others... |       25 | 1.035 | 1.047 | 1.048 |```
EOD;

        $this->assertEquals($expected, $performanceReports[2]);

        $this->performanceService->postWeeklyReport();

    }

}
