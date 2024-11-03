<?php


namespace AppBundle\Tests\Service;


use AppBundle\Constant\AdvisorType;
use AppBundle\Constant\InvestmentEntityFatcaCrsInformationStatus;
use AppBundle\Constant\SemaphoreLight;
use AppBundle\Dto\AdvisorClientDto;
use AppBundle\Dto\AdvisorClientLightDto;
use AppBundle\Dto\AdvisorDto;
use AppBundle\Entity\AdvisorPlatform;
use AppBundle\Entity\InvestmentEntity;
use AppBundle\Entity\InvestmentEntityFatcaCrsInformation;
use AppBundle\Entity\InvestmentEntityIndividual;
use AppBundle\Entity\InvestmentEntityPlatform;
use AppBundle\Entity\InvestmentEntitySmsf;
use AppBundle\Entity\Investor;
use AppBundle\Entity\InvestorPlatformAdvisor;
use AppBundle\Entity\Types\AcStatus;
use AppBundle\Entity\Types\InvestmentEntityStatus;
use AppBundle\Entity\Types\InvestorType;
use AppBundle\Entity\WholesaleData;
use AppBundle\Service\AdvisorClientDtoFactory;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\TestDatasetUtil;
use AppBundle\Tests\TestUtil;

class AdvisorClientDtoFactoryTest extends AppBaseTest {

    /**
     * @var AdvisorClientDtoFactory
     */
    protected $target;

    protected function setUp() {
        parent::setUp();
        $this->target = $this->createdKernel->getContainer()->get(AdvisorClientDtoFactory::SERVICE_NAME);
        TestDatasetUtil::createPersistentBaseDataset($this->entityManager, $this->persistentEntities);
        TestDatasetUtil::createPersistentInvestorDataset($this->entityManager, $this->persistentEntities);
        TestDatasetUtil::createPersistentDatasetAccountantStatus($this->entityManager, $this->persistentEntities);
    }

    /**
     * @test
     */
    public function test_createAdvisorClientDto_allGreen() {
        // Mock & Stubs ----------------------------------------------------------------------------------------------------
        TestDatasetUtil::createPersistentAdvisorClientDtoFactoryAllGreen($this->entityManager, $this->persistentEntities);

        $portfolioServiceStub = $this->getMockBuilder('AppBundle\Service\PortfolioService')
            ->disableOriginalConstructor()->getMock();
        $portfolioServiceStub->method('getPortfolioSnapshot')
            ->willReturn(array(
                'current_valuation' => 1500
            ));
        $this->target->setPortfolioService($portfolioServiceStub);

        // Test ----------------------------------------------------------------------------------------------------
        $investor = $this->persistentEntities['investorClient'];
        $advisor = $this->persistentEntities['advisor'];
        $advisorClientDto = $this->target->createAdvisorClientDtoForBulkTradePage($investor, $advisor);


        // Asserts ----------------------------------------------------------------------------------------------------
        $this->assertEquals(1500, $advisorClientDto->getHoldings());
        $this->assertEquals('SAUL GOODMAN', $advisorClientDto->getFullName());
        $this->assertEquals('0/0', $advisorClientDto->getEntitiesLabel());
    }

    /**
     * @test
     */
    public function test_resolveWholesaleLight_Red() {
        // Given
        $newInvestor = new Investor();

        // When
        /** @var AdvisorClientLightDto $advisorClientLightDto */
        $advisorClientLightDto = $this->target->resolveWholesaleLight($newInvestor);

        // Then
        $this->assertNotNull($advisorClientLightDto);
        $this->assertEquals(SemaphoreLight::RED, $advisorClientLightDto->getColor());
        $this->assertEquals('The wholesale process has not started' , $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function test_resolveWholesaleLight_Green() {
        // Given
        /** @var Investor $investor1 */
        $investor1 = $this->persistentEntities['investor1'];

        // When
        /** @var AdvisorClientLightDto $advisorClientLightDto */
        $advisorClientLightDto = $this->target->resolveWholesaleLight($investor1);

        // Then
        $this->assertNotNull($advisorClientLightDto);
        $this->assertEquals(SemaphoreLight::GREEN, $advisorClientLightDto->getColor());
        $this->assertEquals('The Accountant Certificate has been approved' , $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function test_resolveWholesaleLight_Orange_Awaiting_for_review() {
        // Given (TestDatasetUtil::createPersistentDatasetAccountantStatus)
        /** @var Investor $investor6 */
        $investor6 = $this->persistentEntities['investor6'];

        // When
        /** @var AdvisorClientLightDto $advisorClientLightDto */
        $advisorClientLightDto = $this->target->resolveWholesaleLight($investor6);

        // Then
        $this->assertNotNull($advisorClientLightDto);
        $this->assertEquals(SemaphoreLight::ORANGE, $advisorClientLightDto->getColor());
        $this->assertEquals('The clients accountant has provided an accountant certification. Awaiting for review by the platform administration team.', $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function test_resolveWholesaleLight_Orange_Awaiting_for_review_multi_acc_cer() {
        // Given (TestDatasetUtil::createPersistentDatasetAccountantStatus)
        /** @var Investor $investor7 */
        $investor7 = $this->persistentEntities['investor7'];

        // When
        /** @var AdvisorClientLightDto $advisorClientLightDto */
        $advisorClientLightDto = $this->target->resolveWholesaleLight($investor7);

        // Then
        $this->assertNotNull($advisorClientLightDto);
        $this->assertEquals(SemaphoreLight::ORANGE, $advisorClientLightDto->getColor());
        $this->assertEquals('The clients accountant has provided an accountant certification. Awaiting for review by the platform administration team.', $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function test_resolveWholesaleLight_Orange_accountant_has_been_contacted() {
        // Given (TestDatasetUtil::createPersistentDatasetAccountantStatus)
        /** @var Investor $investor8 */
        $investor8 = $this->persistentEntities['investor8'];

        // When
        /** @var AdvisorClientLightDto $advisorClientLightDto */
        $advisorClientLightDto = $this->target->resolveWholesaleLight($investor8);

        // Then
        $this->assertNotNull($advisorClientLightDto);
        $this->assertEquals(SemaphoreLight::ORANGE, $advisorClientLightDto->getColor());
        $this->assertEquals('The clients accountant has been contacted to verify the clients wholesale status', $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function createBasicAsyncAdvisorClientDto_ok() {
        $investor = TestUtil::createInvestor('investorReloco@mail.com');
        $investorPlatform = TestUtil::createInvestorPlatform($investor);
        $investmentEntityIndividual = TestUtil::createInvestmentEntityIndividual($investor);
        $investmentEntityPlatform = TestUtil::createInvestmentEntityPlatform($investmentEntityIndividual,InvestmentEntityStatus::APPROVED);

        $investorAdvisor = TestUtil::createInvestor('advisor@test.com',"Advisor","test",InvestorType::WHOLESALE);
        $userAdvisor = TestUtil::createUser($investorAdvisor,$investorAdvisor->getEmail());
        $advisor = TestUtil::createAdvisor($userAdvisor,'Advisor Test 3');
        $advisorPlatform = new AdvisorPlatform($advisor);
        $investorPlatformAdvisor = new InvestorPlatformAdvisor($investorPlatform, $advisorPlatform);

        $this->entityManager->persist($investor);
        $this->entityManager->persist($investorPlatform);
        $this->entityManager->persist($investmentEntityIndividual);
        $this->entityManager->persist($investmentEntityPlatform);
        $this->entityManager->persist($investorAdvisor);
        $this->entityManager->persist($userAdvisor);
        $this->entityManager->persist($advisor);
        $this->entityManager->persist($advisorPlatform);
        $this->entityManager->persist($investorPlatformAdvisor);
        $this->entityManager->flush();

        $units = 200;

        $advisorClientDto = $this->target->createBasicAsyncAdvisorClientDto($investmentEntityPlatform,$investorPlatform, $units);

        $this->assertEquals($units, $advisorClientDto->getUnits());
        $this->assertEquals('investorReloco@mail.com', $advisorClientDto->getEmail());
        $this->assertEquals(AdvisorType::FULL, $advisorClientDto->getAdvisors()[0]['type']);
    }

    /**
     * @test
     */
    public function createBasicAdvisorClientDtoWithExtFields_ok() {
        $investor = TestUtil::createInvestor('investorReloco@mail.com');
        $investorPlatform = TestUtil::createInvestorPlatform($investor);
        $investmentEntityIndividual = TestUtil::createInvestmentEntityIndividual($investor);
        $investmentEntityPlatform = TestUtil::createInvestmentEntityPlatform($investmentEntityIndividual,InvestmentEntityStatus::APPROVED);


        $this->entityManager->persist($investor);
        $this->entityManager->persist($investorPlatform);
        $this->entityManager->persist($investmentEntityIndividual);
        $this->entityManager->persist($investmentEntityPlatform);
        $this->entityManager->flush();

        $units = 200;

        $advisorClientDto = $this->target->createBasicAdvisorClientDtoWithExtFields($investmentEntityPlatform,$investorPlatform, $units, 1, 1, 2, InvestmentEntityFatcaCrsInformationStatus::SENT, true);

        $this->assertEquals($units, $advisorClientDto->getUnits());
        $this->assertEquals('investorReloco@mail.com', $advisorClientDto->getEmail());
        $this->assertEquals(1, $advisorClientDto->getAdvisorId());
        $this->assertEquals(1, $advisorClientDto->getExternalPlatformId());
        $this->assertEquals(2, $advisorClientDto->getExternalIdentifierId());
        $this->assertTrue($advisorClientDto->isFatcaEnforced());
        $this->assertEquals(InvestmentEntityFatcaCrsInformationStatus::SENT, $advisorClientDto->getFatcaStatus());
    }

    /**
     * @test
     */
    public function createAdvisorClientDtoObfuscated_ok() {
        $investor = TestUtil::createInvestor('investorReloco@mail.com');
        $investorPlatform = TestUtil::createInvestorPlatform($investor);
        $investmentEntityIndividual = TestUtil::createInvestmentEntityIndividual($investor);
        $investmentEntityPlatform = TestUtil::createInvestmentEntityPlatform($investmentEntityIndividual,InvestmentEntityStatus::APPROVED);

        $this->entityManager->persist($investor);
        $this->entityManager->persist($investorPlatform);
        $this->entityManager->persist($investmentEntityIndividual);
        $this->entityManager->persist($investmentEntityPlatform);
        $this->entityManager->flush();

        $units = 200;

        $advisorClientDto = $this->target->createAdvisorClientDtoObfuscated($investmentEntityPlatform,$investorPlatform, 22, $units);

        $this->assertEquals($units, $advisorClientDto->getUnits());
        $this->assertEquals('investorReloco@mail.com', $advisorClientDto->getEmail());
        $this->assertEquals('Investor 22', $advisorClientDto->getFullName());
    }

    /**
     * @test
     */
    public function resolveEntitiesLabel_ok() {

        $investmentEntityPlatformServiceStub = $this->getMockBuilder('AppBundle\Service\InvestmentEntityPlatformService')
            ->disableOriginalConstructor()->getMock();

        $investmentEntityPlatformServiceStub
            ->method('findNotDeletedByInvestor')
            ->will($this->returnCallback(function(Investor $investor) {
                $investmentEntity1 = new InvestmentEntitySmsf();
                $investmentEntityPlatform1 = new InvestmentEntityPlatform($investmentEntity1);

                $investmentEntity2 = new InvestmentEntitySmsf();
                $investmentEntityPlatform2 = new InvestmentEntityPlatform($investmentEntity2, InvestmentEntityStatus::APPROVED);

                return [$investmentEntityPlatform1, $investmentEntityPlatform2];
            }));

        $this->target->setInvestmentEntityPlatformService($investmentEntityPlatformServiceStub);

        $investor = TestUtil::createInvestor('investorReloco@mail.com');
        $resolveEntitiesLabel = $this->target->resolveEntitiesLabel($investor);

        $this->assertEquals('0/1', $resolveEntitiesLabel);
    }

    /**
     * @test
     */
    public function resolveEntitiesTooltip_ok() {

        $investmentEntityPlatformServiceStub = $this->getMockBuilder('AppBundle\Service\InvestmentEntityPlatformService')
            ->disableOriginalConstructor()->getMock();

        $investmentEntityPlatformServiceStub
            ->method('findNotDeletedByInvestor')
            ->will($this->returnCallback(function(Investor $investor) {
                $investmentEntity1 = new InvestmentEntityIndividual();
                $investmentEntityPlatform1 = new InvestmentEntityPlatform($investmentEntity1);

                $investmentEntity2 = new InvestmentEntitySmsf();
                $investmentEntityPlatform2 = new InvestmentEntityPlatform($investmentEntity2, InvestmentEntityStatus::APPROVED);
                $investmentEntityFatcaCrsInformation = new InvestmentEntityFatcaCrsInformation($investmentEntity2);
                $investmentEntityFatcaCrsInformation->setStatus(InvestmentEntityFatcaCrsInformationStatus::VERIFIED);
                $investmentEntity2->setFatcaCrsInformation($investmentEntityFatcaCrsInformation);

                return [$investmentEntityPlatform1, $investmentEntityPlatform2];
            }));

        $this->target->setInvestmentEntityPlatformService($investmentEntityPlatformServiceStub);

        $investor = TestUtil::createInvestor('investorReloco@mail.com');
        $resolveEntitiesLabel = $this->target->resolveEntitiesTooltip($investor);

        $this->assertEquals('1/2', $resolveEntitiesLabel["label"]);
        $this->assertEquals('<div class=\'ip-flex\'><i class=\'fa-regular fa-circle-xmark ip-color-error-400 ip-space-mgn-xxs-r\'></i><div>Individual: LEO MESSI | FATCA/CRS Status: <span class=\'ip-color-error-400 ip-font-medium\'>Required</span></div>', $resolveEntitiesLabel["entities"][0]["text"]);
        $this->assertEquals('<div class=\'ip-flex\'><i class=\'fa-regular fa-circle-check ip-color-success-400 ip-space-mgn-xxs-r\'></i><div>SMSF:  | FATCA/CRS Status: <span class=\'ip-color-success-900 ip-font-medium\'>Verified</span></div>', $resolveEntitiesLabel["entities"][1]["text"]);
    }

    /**
     * @test
     */
    public function createAdvisorClientLightDto_red() {
        $advisorClientLightDto = $this->target->createAdvisorClientLightDto(AcStatus::PENDING, null, null, null, null);

        $this->assertEquals(SemaphoreLight::RED, $advisorClientLightDto->getColor());
        $this->assertContains('The wholesale process has not started', $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function createAdvisorClientLightDto_green() {
        $advisorClientLightDto = $this->target->createAdvisorClientLightDto(AcStatus::VERIFIED, null, null, null, null);

        $this->assertEquals(SemaphoreLight::GREEN, $advisorClientLightDto->getColor());
        $this->assertContains('The Accountant Certificate has been approved', $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function createAdvisorClientLightDto_orangePendingAdminReview() {
        $advisorClientLightDto = $this->target->createAdvisorClientLightDto(AcStatus::PENDING, 1, 2, null, null);

        $this->assertEquals(SemaphoreLight::ORANGE, $advisorClientLightDto->getColor());
        $this->assertContains('The clients accountant has provided an accountant certification', $advisorClientLightDto->getText());
    }

    /**
     * @test
     */
    public function createAdvisorClientLightDto_orangeContacted() {
        $advisorClientLightDto = $this->target->createAdvisorClientLightDto(AcStatus::PENDING, 1, null, null, new \DateTime());

        $this->assertEquals(SemaphoreLight::ORANGE, $advisorClientLightDto->getColor());
        $this->assertContains('The clients accountant has been contacted', $advisorClientLightDto->getText());
    }
}
