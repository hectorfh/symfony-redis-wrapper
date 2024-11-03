<?php

namespace AppBundle\Tests\Service;

use AppBundle\Service\HyperinflatorService;
use AppBundle\Service\InvestService;
use AppBundle\Entity\Types\Visibility;
use AppBundle\Tests\AppBaseTest;
use AppBundle\Tests\TestDatasetUtil;
use AppBundle\Tests\TestUtil;
use AppBundle\Entity\Types\DealCurrentState;


/**
 * Class HyperinflatorServiceTest
 * @package AppBundle\Tests\Service
 */
class HyperinflatorServiceTest extends AppBaseTest
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

        $ies = $this->entityManager->getRepository('AppBundle:InvestmentEntity')->findAll();
        foreach ($ies as $ie) {
            $ie->setTfn('112243254');
        }
        $this->entityManager->flush();

        $formerCountInvestments = $this->entityManager->getRepository('AppBundle:Investment')->createQueryBuilder('i')->select('COUNT(i.id)')->getQuery()->getSingleScalarResult();
        $formerCountPayments = $this->entityManager->getRepository('AppBundle:Payment')->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $formerCountTrhs = $this->entityManager->getRepository('AppBundle:TrustRegisterHolder')->createQueryBuilder('trh')->select('COUNT(trh.id)')->getQuery()->getSingleScalarResult();
        $formerCountCoupons = $this->entityManager->getRepository('AppBundle:CouponOption')->createQueryBuilder('co')->select('COUNT(co.id)')->getQuery()->getSingleScalarResult();

        $this->hyperinflatorService->hyperinflateTrustRegister($trustRegister->getId(), 10);

        $countInvestments = $this->entityManager->getRepository('AppBundle:Investment')->createQueryBuilder('i')->select('COUNT(i.id)')->getQuery()->getSingleScalarResult();
        $countPayments = $this->entityManager->getRepository('AppBundle:Payment')->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $countTrhs = $this->entityManager->getRepository('AppBundle:TrustRegisterHolder')->createQueryBuilder('trh')->select('COUNT(trh.id)')->getQuery()->getSingleScalarResult();
        $countCoupons = $this->entityManager->getRepository('AppBundle:CouponOption')->createQueryBuilder('co')->select('COUNT(co.id)')->getQuery()->getSingleScalarResult();

        $this->assertEquals(10, $countInvestments - $formerCountInvestments);
        $this->assertEquals(30, $countPayments - $formerCountPayments);
        $this->assertEquals(10, $countTrhs - $formerCountTrhs);
        $this->assertEquals(10, $countCoupons - $formerCountCoupons);
    }

}
