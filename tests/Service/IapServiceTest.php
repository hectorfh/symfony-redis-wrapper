<?php

namespace AppBundle\Tests\Service;

use Symfony\Component\HttpFoundation\ParameterBag;
use AppBundle\Service\IapService;
use AppBundle\Service\InMemoryDbService;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\TestUtil;
use AppBundle\HttpClient\IapHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Created by PhpStorm.
 * User: favio
 * Date: 5/15/17
 * Time: 4:00 PM
 */
class IapServiceTest extends AppBaseTest
{

    /**
     * @var IapService
     */
    private $target;

    protected function setUp()
    {
        parent::setUp();
        $this->target = $this->createdKernel->getContainer()->get(IapService::SERVICE_NAME);
    }

    public function test_jwt_ok() {

        $inMemoryDbService = $this->createdKernel->getContainer()->get(InMemoryDbService::SERVICE_NAME);

        $inMemoryDbService->delete(InMemoryDbService::IAP_DB, IapService::IAP_JWT);

        $jwtExample1 = "eyJhbGciOiJSUzI1NiIsImtpZCI6Ijk1NTEwNGEzN2ZhOTAzZWQ4MGM1NzE0NWVjOWU4M2VkYjI5YjBjNDUiLCJ0eXAiOiJKV1QifQ.eyJhdWQiOiI1MDc2MzA0MDkwMS1ibGVjaXJkYTlzbzViMWlmamRhM2g4czRmaTgzN2hzYy5hcHBzLmdvb2dsZXVzZXJjb250ZW50LmNvbSIsImF6cCI6ImlwbGF0Zm9ybXMtZGV2QGlwbGF0Zm9ybXMtZGV2LXBvbnRlLmlhbS5nc2VydmljZWFjY291bnQuY29tIiwiZW1haWwiOiJpcGxhdGZvcm1zLWRldkBpcGxhdGZvcm1zLWRldi1wb250ZS5pYW0uZ3NlcnZpY2VhY2NvdW50LmNvbSIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJleHAiOjE2NjU5Nzc1MzQsImlhdCI6MTY2NTk3MzkzNCwiaXNzIjoiaHR0cHM6Ly9hY2NvdW50cy5nb29nbGUuY29tIiwic3ViIjoiMTA0MjQ2ODk4MDk5ODUwMDUzMTM0In0.U0unvfomz3mJFXWSD6R-V-Zdx_r2hoLuv_MSET3Y32KRJ9A66EY0ERpmpBVvnBNBJZYrKna11IGff0cU4i4FbIqLuwvxjxBnL3iBupYpnJ4XjyLlm-N5hAsJloqT-_kJ-WPDXxc1dHC_PZyayo2m2eIqXfkYXwl9JTDaQAjnp_OtytPQ0p25KkOTIxXMbCiccfF6p1VYbzg_olix27EbATfEP4p183QxIHbqvmkx33Wc2Jq20KOkDdPMof1HL_E4qKPj7sBbozrqNHgBdSrisqchvtwW2eu5dE8d4LjKDi3hZA7cktjsH0kPPu-epAuWSCzao5nEo512Ry31LCTa_w";

        $googleAuthService = $this->getMockBuilder('AppBundle\\Provider\\Google\\Service\\GoogleAuthService')->disableOriginalConstructor()->getMock();

        $googleAuthService->expects($this->once())->method('fetchAuthToken')->willReturn(['id_token' => $jwtExample1]);

        $this->target->setGoogleAuthService($googleAuthService);

        $jwt = $this->target->jwt();
        $this->assertEquals($jwtExample1, $jwt);

        // try twice (fetchAuthToken should be called only once)
        $jwt = $this->target->jwt();

    }

}
