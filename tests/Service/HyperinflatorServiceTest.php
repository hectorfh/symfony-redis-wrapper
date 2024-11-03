<?php

namespace IpartnersBundle\Tests\Service;

use IpartnersBundle\Service\HyperinflatorService;
use IpartnersBundle\Service\InvestService;
use IpartnersBundle\Entity\Types\Visibility;
use IpartnersBundle\Tests\IpartnersBaseTest;
use IpartnersBundle\Tests\TestDatasetUtil;
use IpartnersBundle\Tests\TestUtil;
use IpartnersBundle\Entity\Types\DealCurrentState;


/**
 * Class HyperinflatorServiceTest
 * @package IpartnersBundle\Tests\Service
 */
class HyperinflatorServiceTest extends IpartnersBaseTest
{

    /**
     * @var HyperinflatorService
     */
    private $hyperinflatorService;

    /**
     * Set up.
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $this->hyperinflatorService =
                $this->createdKernel->getContainer()->get(HyperinflatorService::SERVICE_NAME);

    }

    /**
     * @test
     */
    public function test_hyperinflateTrustRegister_ok()
    {

        $returnArray = [];
        TestDatasetUtil::createPersistentBaseDataset($this->entityManager, $returnArray);
        TestDatasetUtil::createPersistentInvestorDataset($this->entityManager, $returnArray);
        TestDatasetUtil::createPersistentDealDataset($this->entityManager, $returnArray,
            'dealTR1', TestUtil::TMINUS2M, Visibility::VISIBLE, DealCurrentState::OPEN,
            array(
                TestUtil::TMINUS2M => '60',
                TestUtil::TPLUS5D => '20',
                TestUtil::TPLUS4M => '20'
            )
        );
        $adminUser = TestUtil::createAdminUser();
        $this->entityManager->persist($adminUser);
        $deal = $returnArray['dealTR1'];
        $trustRegister = TestUtil::createTrustRegister($deal, $adminUser);
        $this->entityManager->persist($trustRegister);
        $this->entityManager->flush();

        $ies = $this->entityManager->getRepository('IpartnersBundle:InvestmentEntity')->findAll();
        foreach ($ies as $ie) {
            $ie->setTfn('112243254');
        }
        $this->entityManager->flush();

        $formerCountInvestments = $this->entityManager->getRepository('IpartnersBundle:Investment')->createQueryBuilder('i')->select('COUNT(i.id)')->getQuery()->getSingleScalarResult();
        $formerCountPayments = $this->entityManager->getRepository('IpartnersBundle:Payment')->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $formerCountTrhs = $this->entityManager->getRepository('IpartnersBundle:TrustRegisterHolder')->createQueryBuilder('trh')->select('COUNT(trh.id)')->getQuery()->getSingleScalarResult();
        $formerCountCoupons = $this->entityManager->getRepository('IpartnersBundle:CouponOption')->createQueryBuilder('co')->select('COUNT(co.id)')->getQuery()->getSingleScalarResult();

        $this->hyperinflatorService->hyperinflateTrustRegister($trustRegister->getId(), 10);

        $countInvestments = $this->entityManager->getRepository('IpartnersBundle:Investment')->createQueryBuilder('i')->select('COUNT(i.id)')->getQuery()->getSingleScalarResult();
        $countPayments = $this->entityManager->getRepository('IpartnersBundle:Payment')->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $countTrhs = $this->entityManager->getRepository('IpartnersBundle:TrustRegisterHolder')->createQueryBuilder('trh')->select('COUNT(trh.id)')->getQuery()->getSingleScalarResult();
        $countCoupons = $this->entityManager->getRepository('IpartnersBundle:CouponOption')->createQueryBuilder('co')->select('COUNT(co.id)')->getQuery()->getSingleScalarResult();

        $this->assertEquals(10, $countInvestments - $formerCountInvestments);
        $this->assertEquals(30, $countPayments - $formerCountPayments);
        $this->assertEquals(10, $countTrhs - $formerCountTrhs);
        $this->assertEquals(10, $countCoupons - $formerCountCoupons);
    }

}
