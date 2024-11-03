<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Tests\AppBaseTest;
use AppBundle\Entity\PlatformUserRole;
use AppBundle\Service\UserService;
use AppBundle\Service\PlatformService;
use AppBundle\Service\UserRoleService;
use AppBundle\Service\JwtApiService;
use AppBundle\Entity\AuditInfo;
use AppBundle\Filter\PlatformFilter;
use AppBundle\Dto\UserJWT_RequestDto;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;


class AuthListenerTest  extends AppBaseTest
{

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var PlatformService
     */
    private $platformService;

    /**
     * @var UserRoleService
     */
    private $userRoleService;

    /**
     * @var JwtApiService
     */
    private $jwtApiService;

    protected function setUp()
    {
 
        $env = getenv('SYMFONY_TESTS_ENV');
        $options = $env ? ["environment" => $env] : [];

        //start the symfony kernel
        $this->createdKernel = static::createKernel($options);
        $this->createdKernel->boot();

        $this->startTimer();

        $this->entityManager = $this->createdKernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->createdKernel->getContainer()->get('logger');

        $platformId = $this->createdKernel->getContainer()->getParameter('platform_id');
        $platformName = $this->createdKernel->getContainer()->getParameter('platform_name');

        $this->entityManager->getFilters()
            ->enable(PlatformFilter::FILTER_NAME)
            ->setParameter("platform_id", $platformId);

        $this->setPlatformConfigInRequestStack($platformId, $platformName);

        $this->userService = $this->createdKernel->getContainer()->get(UserService::SERVICE_NAME);
        $this->platformService = $this->createdKernel->getContainer()->get(PlatformService::SERVICE_NAME);
        $this->userRoleService = $this->createdKernel->getContainer()->get(UserRoleService::SERVICE_NAME);
        $this->jwtApiService = $this->createdKernel->getContainer()->get(JwtApiService::SERVICE_NAME);
    }


    public function testVerifyJWT()
    {

        /** @var User $user */
        $user = $this->userService->getUserById(10000);

        $this->addRoleToUser($user->getId(), "ROLE_ADMIN", "subdom");

        $client = static::createClient();

        $jwt = $this->jwtApiService->_createJwt(10000);

        // OK
        $this->doRequest($client, 'GET', '/auth-test/1', $jwt);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $expectedContent = [ 'attrA' => 1, 'attrB' => 'two' ];
        $this->assertEquals(json_encode($expectedContent), $response->getContent());

        // no permissions
        $this->doRequest($client, 'POST', '/auth-test/1', $jwt);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());

        $this->doRequest($client, 'GET', '/auth-test/2', $jwt);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());

        // wrong signature
        $jwtWrongKeySignature = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2NjcyNDgxMzksImV4cCI6Mzg3NDc2ODEzOSwidXNlcl9pZCI6MTAwMDB9.1gB2YdQjRWcfTwl_Fkmigj47-guMoLVptA9zu3VcgW4';

        $this->doRequest($client, 'GET', '/auth-test/1', $jwtWrongKeySignature);
        $response = $client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
        
        // expired token
        $jwtExpired = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2NjcyNzQyMjEsImV4cCI6MTY2NzI3NzgyMSwidXNlcl9pZCI6MTAwMDB9.0a4dwrYc2sOwIW-QnODFuZte7Dwwhz5oKxekm-tYqWA';

        $this->doRequest($client, 'GET', '/auth-test/1', $jwtExpired);
        $response = $client->getResponse();
        $this->assertEquals(401, $response->getStatusCode());

    }

    /**
     *
     *
     * @param string $client
     * @param string $method
     * @param string $jwt
     */
    private function doRequest($client, $method, $url, $jwt) {

        $client->request(
            $method,
            $url,
            [],
            [],
            [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_x-platform-name' => 'subdom',
                'HTTP_Authorization'   => 'Bearer ' . $jwt
            ]
        );
        
    }
    
    /**
     *
     *
     * @param int $userId
     * @param int $roleName
     * @param int $platformId
     */
    private function addRoleToUser($userId, $roleName, $platformName) {

        /** @var Platform $platform */
        $platform = $this->platformService->findByName($platformName);

        /** @var Role $role */
        $role = $this->entityManager->getRepository('AppBundle:Role')->findOneByRole($roleName);

        /** @var User $user */
        $user = $this->userService->findById($userId);

        /** @var PlatformUserRole $platformUserRole */
        $platformUserRole = $this->entityManager->getRepository('AppBundle:PlatformUserRole')
            ->findOneBy(["platform" => $platform, "role" => $role, "user" => $user]);

        if ($platformUserRole) {
            return;
        }

        $platformUserRole = new PlatformUserRole();

        $platformUserRole->setId(1589272);
        $platformUserRole->setPlatform($platform);
        $platformUserRole->setRole($role);
        $platformUserRole->setUser($user);
        $platformUserRole->setAuditInfo(new AuditInfo());
        $platformUserRole->getAuditInfo()->setCreatedDate(new \DateTime('NOW'));

        $this->entityManager->persist($platformUserRole);
        $this->entityManager->flush();
        
    }
    
}
