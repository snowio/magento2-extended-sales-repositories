<?php
namespace SnowIO\ExtendedSalesRepositories\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use PHPUnit\Framework\TestCase;
use SnowIO\ExtendedSalesRepositories\Api\CreditmemoByOrderIncrementIdInterface;
use SnowIO\ExtendedSalesRepositories\Exception\SnowCreditMemoException;

class CreditmemoByOrderIncrementIdTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var CreditmemoRepositoryInterface */
    private $creditmemoRepository;

    /** @var CreditmemoByOrderIncrementIdInterface */
    private $creditmemoByOrderIncrementId;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->creditmemoRepository = $this->objectManager->get(CreditmemoRepositoryInterface::class);
        $this->creditmemoByOrderIncrementId = $this->objectManager->get(CreditmemoByOrderIncrementIdInterface::class);
    }

    /**
     * @magentoDataFixture SnowIO_ExtendedSalesRepositories::Test/Integration/_files/order.php
     * @dataProvider getStandardCaseTestData
     * @param string $orderIncrementId
     * @param CreditmemoInterface $creditmemo
     * @param callable[] $assertions
     */
    public function testStandardCase(CreditmemoInterface $creditmemo, $assertions)
    {
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->creditmemoByOrderIncrementId->createAndRefund("100000001", $creditmemo);
        foreach ($assertions as $assertion) {
            $assertion($creditmemo);
        }
    }


    /**
     * @magentoDataFixture SnowIO_ExtendedSalesRepositories::Test/Integration/_files/order.php
     * @dataProvider getErroneousCaseTestData
     */
    public function testErroneousCase(CreditmemoInterface $creditmemo, string $errorClass)
    {
        self::expectException($errorClass);
        $this->creditmemoByOrderIncrementId->createAndRefund("100000001", $creditmemo);
    }

    public function getStandardCaseTestData()
    {
        return [
            "should create and refund the credit memo using the order increment id" => [
                $this->objectManager->create(CreditmemoInterface::class)
                    ->setItems([
                        $this->objectManager->create(CreditmemoItemInterface::class)
                            ->setSku('simple')
                            ->setQty(1)
                    ]),
                [
                    $this->assertExistsInRepository(),
                    $this->assertItemsRefunded()
                ]
            ],
            "should create and refund an adhoc adjustment credit memo using the order increment id" => [
                $this->objectManager->create(CreditmemoInterface::class)
                    ->setAdjustmentPositive(50),
                [
                    $this->assertExistsInRepository(),
                    $this->assertAdjustmentAmount(50.0),
                ]
            ]
        ];
    }

    public function getErroneousCaseTestData()
    {
        return [
            "should throw if no credit memo has no items and is not an adjustment" => [
                $this->objectManager->create(CreditmemoInterface::class),
                SnowCreditMemoException::class
            ]
        ];
    }

    private function assertExistsInRepository()
    {
        return function (CreditmemoInterface $creditmemo) {
            $retrievedCreditmemo = $this->creditmemoRepository->get($creditmemo->getEntityId());
            self::assertNotEmpty($retrievedCreditmemo);
        };
    }

    private function assertItemsRefunded()
    {
        return function (CreditmemoInterface $creditmemo) {
            $retrievedCreditmemo = $this->creditmemoRepository->get($creditmemo->getEntityId());
            $itemsBySku = [];
            foreach ($retrievedCreditmemo->getItems() as $retrievedCreditMemoItem) {
                $itemsBySku[$retrievedCreditMemoItem->getSku()] = [
                    'qty' => $retrievedCreditMemoItem->getQty()
                ];
            }

            foreach ($creditmemo->getItems() as $creditmemoItem) {
                self::assertNotEmpty($itemsBySku[$creditmemoItem->getSku()]);
                self::assertEquals($creditmemoItem->getQty(), $itemsBySku[$creditmemoItem->getSku()]['qty']);
            }
        };
    }

    private function assertAdjustmentAmount(float $amount)
    {
        return function (CreditmemoInterface $creditmemo) use ($amount) {
            $retrievedCreditmemo = $this->creditmemoRepository->get($creditmemo->getEntityId());
            self::assertEquals($amount, $retrievedCreditmemo->getBaseAdjustmentPositive());
        };
    }
}
