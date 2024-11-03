<?php

namespace IpartnersBundle\Tests\Service;

use IpartnersBundle\Entity\Types\AsyncProcessType;
use IpartnersBundle\Entity\Types\DistributionVisibility;
use IpartnersBundle\Service\AsyncProcessService;
use IpartnersBundle\Service\DistributionGroupService;
use IpartnersBundle\Service\DistributionService;
use IpartnersBundle\Tests\IpartnersBaseTest;
use IpartnersBundle\Tests\TestDatasetUtil;

/**
 * Class DistributionGroupServiceTest
 * @package IpartnersBundle\Tests\Service
 */
class DistributionGroupServiceTest extends IpartnersBaseTest
{

    /**
     * @var DistributionGroupService
     */
    private $distributionGroupService;

    /**
     * @var DistributionService
     */
    private $distributionService;

    /**
     * @var AsyncProcessService
     */
    private $asyncProcessService;

    /**
     * Set up.
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $this->distributionGroupService =
                $this->createdKernel->getContainer()->get(DistributionGroupService::SERVICE_NAME);

        $this->distributionService =
                $this->createdKernel->getContainer()->get(DistributionService::SERVICE_NAME);

        $this->asyncProcessService =
            $this->createdKernel->getContainer()->get(AsyncProcessService::SERVICE_NAME);
    }

    /**
     * @test
     */
    public function test_distributionGroupsByDeal_ok()
    {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $paginatorData = $this->distributionService->distributionsByDealDatesPaginator($persistentEntities["distribution1"]->getDeal()->getId(), 0, 100);

        $distributionGroups = $this->distributionGroupService->distributionGroupsByDeal($persistentEntities["distribution1"]->getDeal()->getId(), $paginatorData);

        $this->assertEquals(4, count($distributionGroups));
        $this->assertDistributionGroups($persistentEntities, $distributionGroups);
    }

    /**
     * @test
     */
    public function test_distributionGroupsByDeal_cached()
    {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $paginatorData = $this->distributionService->distributionsByDealDatesPaginator($persistentEntities["distribution1"]->getDeal()->getId(), 0, 100);

        $this->distributionGroupService->distributionGroupsByDeal($persistentEntities["distribution1"]->getDeal()->getId(), $paginatorData);

        $distributionGroups = $this->distributionGroupService->distributionGroupsByDeal($persistentEntities["distribution1"]->getDeal()->getId(), $paginatorData);

        $this->assertEquals(4, count($distributionGroups));
        $this->assertDistributionGroups($persistentEntities, $distributionGroups);
    }

    private function assertDistributionGroups($persistentEntities, $distributionGroups)
    {
        $dto = $distributionGroups["01-06-2019"]["01-06-2019"];
        $this->assertEquals(' June Multiple Distribution', $dto->getDescription());
        $this->assertEquals(9.10985, $dto->getValue());
        $this->assertEquals('Percentage', $dto->getType());
        $this->assertEquals(new \DateTime("2019-06-01"), $dto->getDate());
        $this->assertEquals(NULL, $dto->getIssuerPayedDate());
        $this->assertEquals(250, $dto->getDistributionAmount());
        $this->assertEquals(250, $dto->getDistributionPayoutAmount());
        $this->assertEquals(0, $dto->getDistributionPerformanceFee());
        $this->assertEquals(TRUE, $dto->isDepositedToAccountFilled());
        $this->assertEquals(TRUE, $dto->isDepositedToBsbFilled());
        $this->assertEquals(0, $dto->getWhtAmount());
        $this->assertEquals($persistentEntities["distribution1"]->getInvestor()->getId(), $dto->getInvestorId());
        $this->assertEquals(TRUE, $dto->isDomestic());

        $dto = $distributionGroups["21-04-2019"]["21-04-2019"];
        $this->assertEquals('', $dto->getDescription());
        $this->assertEquals(9.10985, $dto->getValue());
        $this->assertEquals('Percentage', $dto->getType());
        $this->assertEquals($persistentEntities["distribution3"]->getId(), $dto->getId());
        $this->assertEquals(new \DateTime("2019-04-21"), $dto->getDate());
        $this->assertEquals(NULL, $dto->getIssuerPayedDate());
        $this->assertEquals(250, $dto->getDistributionAmount());
        $this->assertEquals(250, $dto->getDistributionPayoutAmount());
        $this->assertEquals('Visible', $dto->getVisibility());
        $this->assertEquals(0, $dto->getDistributionPerformanceFee());
        $this->assertEquals(TRUE, $dto->isDepositedToAccountFilled());
        $this->assertEquals(TRUE, $dto->isDepositedToBsbFilled());
        $this->assertEquals(NULL, $dto->getWhtAmount());
        $this->assertEquals($persistentEntities["distribution3"]->getInvestor()->getId(), $dto->getInvestorId());
        $this->assertEquals(TRUE, $dto->isDomestic());

        $dto = $distributionGroups["01-03-2019"]["01-03-2019"];
        $this->assertEquals('', $dto->getDescription());
        $this->assertEquals(9.10985, $dto->getValue());
        $this->assertEquals('Percentage', $dto->getType());
        $this->assertEquals($persistentEntities["distribution4"]->getId(), $dto->getId());
        $this->assertEquals(new \DateTime("2019-03-01"), $dto->getDate());
        $this->assertEquals(NULL, $dto->getIssuerPayedDate());
        $this->assertEquals(350, $dto->getDistributionAmount());
        $this->assertEquals(350, $dto->getDistributionPayoutAmount());
        $this->assertEquals('Visible', $dto->getVisibility());
        $this->assertEquals(0, $dto->getDistributionPerformanceFee());
        $this->assertEquals(TRUE, $dto->isDepositedToAccountFilled());
        $this->assertEquals(TRUE, $dto->isDepositedToBsbFilled());
        $this->assertEquals(0, $dto->getWhtAmount());
        $this->assertEquals($persistentEntities["distribution4"]->getInvestor()->getId(), $dto->getInvestorId());
        $this->assertEquals(TRUE, $dto->isDomestic());

        $dto = $distributionGroups["15-02-2019"]["15-02-2019"];
        $this->assertEquals(' June Multiple Distribution', $dto->getDescription());
        $this->assertEquals(9.10985, $dto->getValue());
        $this->assertEquals('Percentage', $dto->getType());
        $this->assertEquals(new \DateTime("2019-02-15"), $dto->getDate());
        $this->assertEquals(NULL, $dto->getIssuerPayedDate());
        $this->assertEquals(1200, $dto->getDistributionAmount());
        $this->assertEquals(1200, $dto->getDistributionPayoutAmount());
        $this->assertEquals(0, $dto->getDistributionPerformanceFee());
        $this->assertEquals(TRUE, $dto->isDepositedToAccountFilled());
        $this->assertEquals(TRUE, $dto->isDepositedToBsbFilled());
        $this->assertEquals(0, $dto->getWhtAmount());
        $this->assertEquals($persistentEntities["distribution5"]->getInvestor()->getId(), $dto->getInvestorId());
        $this->assertEquals(TRUE, $dto->isDomestic());

    }

    /**
     * @test
     */
    public function test_mapDistributionArrayToDistributionDtoGroupedArray_ok()
    {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $paginatorData = $this->distributionService->distributionsByDealDatesPaginator($persistentEntities["distribution1"]->getDeal()->getId(), 0, 100);

        $distributionsList = $this->distributionService->findDistributionsByDeal(
            $persistentEntities["distribution1"]->getDeal()->getId(),
            null, // investorId
            null, // visibility
            null, // date
            null, // trustRegisterHolderId
            null, // investmentEntity
            false, // fillBankDetails
            null, // issuerPayedDateNotNull
            null, // date from
            null, // date to
            $paginatorData['dates'] // dates
        );

        $distributionDtos = $this->distributionService->mapDistributionArrayToDistributionDtoGroupedArray($distributionsList, false, true, true);

        $dto = $distributionDtos["01-06-2019"]["01-06-2019"];
        $this->assertEquals($persistentEntities["distribution1"]->getDeal()->getId(), $dto->getDealId());
        $this->assertEquals(' June Multiple Distribution', $dto->getDescription());
        $this->assertEquals(9.10985, $dto->getValue());
        $this->assertEquals('Percentage', $dto->getType());
        $this->assertEquals([], $dto->getDcProcessedArray());
        $this->assertEquals('$', $dto->getCurrencyDescriptor());
        $this->assertEquals(new \DateTime("2019-06-01"), $dto->getDate());
        $this->assertEquals(NULL, $dto->getIssuerPayedDate());
        $this->assertEquals("21st April 2019", $dto->getStartDate());
        $this->assertEquals("1st June 2019", $dto->getEndDate());
        $this->assertEquals(42, $dto->getDays());
        $this->assertEquals(250, $dto->getDistributionAmount());
        $this->assertEquals(250, $dto->getDistributionPayoutAmount());
        $this->assertEquals(0, $dto->getDistributionPerformanceFee());
        $this->assertEquals(TRUE, $dto->isDepositedToAccountFilled());
        $this->assertEquals(TRUE, $dto->isDepositedToBsbFilled());
        $this->assertEquals(NULL, $dto->getInvestmentId());
        $this->assertEquals(0, $dto->getWhtAmount());
        $this->assertEquals(0, $dto->getReinvestment());
        $this->assertEquals(NULL, $dto->getAuResident());
        $this->assertEquals(NULL, $dto->getTotalUnits());
        $this->assertEquals($persistentEntities["distribution1"]->getInvestor()->getId(), $dto->getInvestorId());
        $this->assertEquals("012046", $dto->getDepositedToBsb());
        $this->assertEquals("229077666", $dto->getDepositedToAccount());
        $this->assertEquals("01-06-2019", $dto->getDateUrl());
        $this->assertEquals("MELTOR ME", $dto->getInvestorFullName());
        $this->assertEquals(NULL, $dto->getDescriptionModifiedDate());
        $this->assertEquals($persistentEntities["distribution1"]->getTrustRegisterHolder()->getInvestmentInvestmentEntity()->getId(), $dto->getInvestmentEntityId());
        $this->assertEquals('', $dto->getAccountHolderInvestmentEntity());
        $this->assertEquals(NULL, $dto->getBankAccountDomicile());
        $this->assertEquals('-', $dto->getCurrency());
        $this->assertEquals('-', $dto->getBankCodeSwift());
        $this->assertEquals('-', $dto->getBankAccountName());
        $this->assertEquals('-', $dto->getBankName());
        $this->assertEquals('-', $dto->getBankAddress());
        $this->assertEquals('-', $dto->getIntermediaryBankName());
        $this->assertEquals('-', $dto->getIntermediaryBankCode());
        $this->assertEquals('-', $dto->getAdditionalInstructions());
        $this->assertEquals(TRUE, $dto->isDomestic());
        $this->assertNotNull($dto->getDpu());
        $this->assertEquals(500, $dto->getUnitsHelds());

    }

    /**
     * @test
     */
    public function test_distributionGroupsByDeal_pagination()
    {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $paginatorData = $this->distributionService->distributionsByDealDatesPaginator($persistentEntities["distribution1"]->getDeal()->getId(), 0, 2);

        $distributionGroups = $this->distributionGroupService->distributionGroupsByDeal($persistentEntities["distribution1"]->getDeal()->getId(), $paginatorData);

        $this->assertEquals(2, count($distributionGroups));

    }

    /**
     * @test
     */
    public function test_updateDistributionsByDistributionIdAndInvestmentEntity_ok() {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $distribution = $persistentEntities['distribution1'];

        $paginatorData = $this->distributionService->distributionsByDealDatesPaginator($distribution->getDeal()->getId(), 0, 100);

        $distributionGroups = $this->distributionGroupService->distributionGroupsByDeal($distribution->getDeal()->getId(), $paginatorData);

        $distributionDateGroups = $this->entityManager
            ->getRepository('IpartnersBundle:DistributionDateGroup')
            ->findByDealId($distribution->getDeal()->getId());
        $this->assertEquals(4, count($distributionDateGroups));

        $this->distributionService->updateDistributionsByDistributionIdAndInvestmentEntity($distribution->getId());

        $distributionDateGroups = $this->entityManager
            ->getRepository('IpartnersBundle:DistributionDateGroup')
            ->findByDealId($distribution->getDeal()->getId());
        $this->assertEquals(3, count($distributionDateGroups));

    }

    /**
     * @test
     */
    public function test_createRequestCashflowWhenSkipDistribution_ok() {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $distribution = $persistentEntities['distribution1'];

        $this->distributionService->updateDistributionsByDistributionIdAndInvestmentEntity($distribution->getId(), null, "Skipped");
        $asyncProcessRequest = $this->asyncProcessService->findActionsNotProcessedByType(AsyncProcessType::CASHFLOW_ITEMS_GENERATION);
        $this->assertNotEmpty($asyncProcessRequest);

    }

    /**
     * @test
     */
    public function test_updateDistributionsFilteringByDealOrDistributionInfo_ok() {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $distribution = $persistentEntities['distribution1'];
        $deal = $distribution->getDeal();
        $deal->setDealCode('test123');
        $this->entityManager->persist($deal);
        $this->entityManager->flush();
        $paginatorData = $this->distributionService->distributionsByDealDatesPaginator($distribution->getDeal()->getId(), 0, 100);

        $distributionGroups = $this->distributionGroupService->distributionGroupsByDeal($distribution->getDeal()->getId(), $paginatorData);

        $distributionDateGroups = $this->entityManager
            ->getRepository('IpartnersBundle:DistributionDateGroup')
            ->findByDealId($distribution->getDeal()->getId());
        $this->assertEquals(4, count($distributionDateGroups));

        $this->distributionService->updateDistributionsFilteringByDealOrDistributionInfo(
            null, $distribution->getDeal()->getId(), $distribution->getDistributionDate(), array(DistributionVisibility::STAGING,DistributionVisibility::VISIBLE), null, DistributionVisibility::VISIBLE);

        $distributionDateGroups = $this->entityManager
            ->getRepository('IpartnersBundle:DistributionDateGroup')
            ->findByDealId($distribution->getDeal()->getId());
        $this->assertEquals(3, count($distributionDateGroups));

        $asyncProcessRequest = $this->asyncProcessService->findActionsNotProcessedByType(AsyncProcessType::CASHFLOW_ITEMS_GENERATION_BY_DEAL);
        $this->assertNotEmpty($asyncProcessRequest);

    }

    /**
     * @test
     */
    public function test_deleteDistributions_ok() {
        $persistentEntities = TestDatasetUtil::createFullPersistentDataset($this->entityManager);

        $distribution = $persistentEntities['distribution1'];

        $paginatorData = $this->distributionService->distributionsByDealDatesPaginator($distribution->getDeal()->getId(), 0, 100);

        $distributionGroups = $this->distributionGroupService->distributionGroupsByDeal($distribution->getDeal()->getId(), $paginatorData);

        $distributionDateGroups = $this->entityManager
            ->getRepository('IpartnersBundle:DistributionDateGroup')
            ->findByDealId($distribution->getDeal()->getId());
        $this->assertEquals(4, count($distributionDateGroups));

        $this->distributionService->deleteDistributions($distribution->getDeal(), $distribution->getDistributionDate());

        $distributionDateGroups = $this->entityManager
            ->getRepository('IpartnersBundle:DistributionDateGroup')
            ->findByDealId($distribution->getDeal()->getId());
        $this->assertEquals(3, count($distributionDateGroups));

    }

}
