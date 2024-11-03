<?php


namespace AppBundle\Tests\Service;


use AppBundle\Service\FrontAuthService;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\MockObj;

class FrontAuthServiceTest extends AppBaseTest
{
    /** @var FrontAuthService */
    private $target;

    protected function setUp()
    {
        parent::setUp();

        $this->target = $this->createdKernel->getContainer()->get(FrontAuthService::SERVICE_NAME);

        $this->target->setEndpoints([
            'TEST /front/auth/service/test' => ['test-permission'],
            '* /home-invest/*' => ['basic-permission']
        ]);

    }

    /**
     * @test
     */
    public function test_allowed_true() {

        $rolePermissionServiceMock = new MockObj();
        $rolePermissionServiceMock->hasAnyPermission = function($permissions) use (&$requiredPermissions) {
            $requiredPermissions = $permissions;
            return TRUE;
        };
        $this->target->setRolePermissionService($rolePermissionServiceMock);

        // method-path found
        $requiredPermissions = [];
        $allowed = $this->target->allowed('TEST', '/front/auth/service/test');
        $this->assertEquals(['test-permission'], $requiredPermissions);
        $this->assertTrue($allowed);

        // method-path found, default permission
        $requiredPermissions = [];
        $allowed = $this->target->allowed('GET', '/home-invest');
        $this->assertEquals(['basic-permission'], $requiredPermissions);
        $this->assertTrue($allowed);

        // method-path not found
        $requiredPermissions = [];
        $allowed = $this->target->allowed('GET', '/front/auth/service/test');
        $this->assertEquals([], $requiredPermissions);
        $this->assertTrue($allowed);

    }

    /**
     * @test
     */
    public function test_allowed_false() {

        $rolePermissionServiceMock = new MockObj();
        $rolePermissionServiceMock->hasAnyPermission = function($permissions) use (&$requiredPermissions) {
            $requiredPermissions = $permissions;
            return FALSE;
        };
        $this->target->setRolePermissionService($rolePermissionServiceMock);

        // method-path found
        $requiredPermissions = [];
        $allowed = $this->target->allowed('TEST', '/front/auth/service/test');
        $this->assertEquals(['test-permission'], $requiredPermissions);
        $this->assertFalse($allowed);

    }

}
