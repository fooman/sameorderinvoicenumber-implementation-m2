<?php

namespace Fooman\SameOrderInvoiceNumber\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class CreditmemoObserverTest extends TestCase
{
    const TEST_STORE_ID = 1;
    const TEST_PREFIX = 'CRE-';

    /** @var CreditmemoObserver */
    protected $object;

    /** @var ObjectManager */
    protected $objectManager;


    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * @param     $orderIncrement
     * @param int $existingCreditmemos
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getCreditmemoCollectionMock($orderIncrement, $existingCreditmemos = 0)
    {
        $selectMock = $this->createPartialMock(\Magento\Framework\DB\Select::class, ['reset']);
        $selectMock->expects($this->any())
            ->method('reset')
            ->will($this->returnSelf());

        $creditmemoCollectionMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection::class,
            ['getIterator', 'getSelect', 'addAttributeToFilter']
        );
        $creditmemoCollectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);
        $creditmemoCollectionMock->expects($this->any())
            ->method('addAttributeToFilter')
            ->will($this->returnSelf());

        $items = [];

        switch ($existingCreditmemos) {
            case 2:
                $creditMemoMock = $this->createPartialMock(
                    \Magento\Sales\Model\Order\Creditmemo::class,
                    ['getIncrementId']
                );
                $creditMemoMock->expects($this->any())
                    ->method('getIncrementId')
                    ->willReturn($orderIncrement . '-1');
                $items[1] = $creditMemoMock;
            //no break intentionally
            case 1:
                $creditMemoMock = $this->createPartialMock(
                    \Magento\Sales\Model\Order\Creditmemo::class,
                    ['getIncrementId']
                );
                $creditMemoMock->expects($this->any())
                    ->method('getIncrementId')
                    ->willReturn($orderIncrement);
                $items[0] = $creditMemoMock;
                break;
        }

        $creditmemoCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        return $creditmemoCollectionMock;
    }

    /**
     * @param $orderIncrement
     * @param $creditMemoCollectionMock
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getCreditmemoMock($orderIncrement, $creditMemoCollectionMock)
    {
        //Mock Order
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIncrementId', 'getStoreId', 'getCreditmemosCollection'])
            ->getMock();

        $orderMock->expects($this->any())
            ->method('getIncrementId')
            ->willReturn($orderIncrement);

        $orderMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(self::TEST_STORE_ID);

        $orderMock->expects($this->any())
            ->method('getCreditmemosCollection')
            ->willReturn($creditMemoCollectionMock);

        //Mock Creditmemo
        $creditmemoMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getId'])
            ->getMock();

        $creditmemoMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $creditmemoMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        return $creditmemoMock;
    }

    /**
     * @dataProvider salesOrderCreditmemoSaveBeforeDataProvider
     * @param $input
     * @param $expected
     */
    public function testSalesOrderCreditmemoSaveBefore($input, $expected)
    {
        $this->object = $this->objectManager->getObject(
            \Fooman\SameOrderInvoiceNumber\Observer\CreditmemoObserver::class,
            [
                'scopeConfig' => $this->getScopeConfigMock()
            ]
        );

        $creditmemoMock = $this->getCreditmemoMock(
            $input['order_increment_id'],
            $this->getCreditmemoCollectionMock($input['order_increment_id'], $input['existing_creditmemos'])
        );

        //Mock Observer
        /** @var \Magento\Framework\Event\Observer $observer */
        $observer = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getCreditmemo']);
        $observer->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($creditmemoMock);


        //Execute Observer
        $this->object->execute($observer);

        $this->assertEquals($expected, $creditmemoMock->getIncrementId());
    }

    /**
     * @dataProvider salesOrderCreditmemoSaveBeforeDataProvider
     *
     * @param $input
     * @param $expected
     */
    public function testSalesOrderCreditmemoSaveBeforeWithPrefix($input, $expected)
    {
        $this->object = $this->objectManager->getObject(
            \Fooman\SameOrderInvoiceNumber\Observer\CreditmemoObserver::class,
            [
                'scopeConfig' => $this->getScopeConfigMock(true)
            ]
        );

        $creditmemoMock = $this->getCreditmemoMock(
            $input['order_increment_id'],
            $this->getCreditmemoCollectionMock(
                self::TEST_PREFIX . $input['order_increment_id'],
                $input['existing_creditmemos']
            )
        );

        //Mock Observer
        /** @var \Magento\Framework\Event\Observer $observer */
        $observer = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getCreditmemo']);
        $observer->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($creditmemoMock);


        //Execute Observer
        $this->object->execute($observer);

        $this->assertEquals(self::TEST_PREFIX . $expected, $creditmemoMock->getIncrementId());
    }


    /**
     * @return array
     */
    public function salesOrderCreditmemoSaveBeforeDataProvider()
    {
        return [
            [
                'input'          => [
                    'order_increment_id'   => '100000015',
                    'existing_creditmemos' => 0
                ],
                'expectedResult' => '100000015',
            ],
            [
                'input'          => [
                    'order_increment_id'   => '200000001',
                    'existing_creditmemos' => 0
                ],
                'expectedResult' => '200000001',
            ],
            [
                'input'          => [
                    'order_increment_id'   => 'TEST--001',
                    'existing_creditmemos' => 0
                ],
                'expectedResult' => 'TEST--001',
            ],
            [
                'input'          => [
                    'order_increment_id'   => '100000015',
                    'existing_creditmemos' => 1
                ],
                'expectedResult' => '100000015-1',
            ],
            [
                'input'          => [
                    'order_increment_id'   => '100000015',
                    'existing_creditmemos' => 2
                ],
                'expectedResult' => '100000015-2',
            ]
        ];
    }

    /**
     * @param bool $withPrefixes
     *
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected function getScopeConfigMock($withPrefixes = false)
    {
        if ($withPrefixes) {
            $scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

            $scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'sameorderinvoicenumber/settings/creditmemoprefix',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    self::TEST_STORE_ID
                )
                ->willReturn(self::TEST_PREFIX);
        } else {
            $scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        }
        return $scopeConfigMock;
    }
}
