<?php

namespace Fooman\SameOrderInvoiceNumber\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class InvoiceObserverTest extends TestCase
{
    const TEST_STORE_ID = 1;
    const TEST_PREFIX = 'INV-';

    /** @var InvoiceObserver */
    protected $object;

    /** @var ObjectManager */
    protected $objectManager;


    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * @param     $orderIncrement
     * @param int $existingInvoices
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getInvoiceCollectionMock($orderIncrement, $existingInvoices = 0)
    {
        $selectMock = $this->createPartialMock(\Magento\Framework\DB\Select::class, ['reset']);
        $selectMock->expects($this->any())
            ->method('reset')
            ->will($this->returnSelf());

        $invoiceCollectionMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class,
            ['getIterator', 'getSelect', 'addAttributeToFilter']
        );
        $invoiceCollectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);
        $invoiceCollectionMock->expects($this->any())
            ->method('addAttributeToFilter')
            ->will($this->returnSelf());

        $items = [];

        switch ($existingInvoices) {
            case 2:
                $invoiceMock = $this->createPartialMock(
                    \Magento\Sales\Model\Order\Invoice::class,
                    ['getIncrementId']
                );
                $invoiceMock->expects($this->any())
                    ->method('getIncrementId')
                    ->willReturn($orderIncrement . '-1');
                $items[1] = $invoiceMock;
            //no break intentionally
            case 1:
                $invoiceMock = $this->createPartialMock(
                    \Magento\Sales\Model\Order\Invoice::class,
                    ['getIncrementId']
                );
                $invoiceMock->expects($this->any())
                    ->method('getIncrementId')
                    ->willReturn($orderIncrement);
                $items[0] = $invoiceMock;
                break;
        }

        $invoiceCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        return $invoiceCollectionMock;
    }

    /**
     * @param $orderIncrement
     * @param $invoiceMemoCollectionMock
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getInvoiceMock($orderIncrement, $invoiceMemoCollectionMock)
    {
        //Mock Order
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIncrementId', 'getStoreId', 'getInvoiceCollection'])
            ->getMock();

        $orderMock->expects($this->any())
            ->method('getIncrementId')
            ->willReturn($orderIncrement);

        $orderMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(self::TEST_STORE_ID);

        $orderMock->expects($this->any())
            ->method('getInvoiceCollection')
            ->willReturn($invoiceMemoCollectionMock);


        //Mock Invoice
        $invoiceMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Invoice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrder', 'getId'])
            ->getMock();

        $invoiceMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $invoiceMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        return $invoiceMock;
    }

    /**
     * @dataProvider salesOrderInvoiceSaveBeforeDataProvider
     *
     * @param $input
     * @param $expected
     */
    public function testSalesOrderInvoiceSaveBefore($input, $expected)
    {
        $this->object = $this->objectManager->getObject(
            \Fooman\SameOrderInvoiceNumber\Observer\InvoiceObserver::class,
            [
                'scopeConfig' => $this->getScopeConfigMock()
            ]
        );

        $invoiceMock = $this->getInvoiceMock(
            $input['order_increment_id'],
            $this->getInvoiceCollectionMock($input['order_increment_id'], $input['existing_invoices'])
        );

        //Mock Observer
        /** @var \Magento\Framework\Event\Observer $observer */
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getInvoice'])
            ->getMock();

        $observer->expects($this->once())
            ->method('getInvoice')
            ->willReturn($invoiceMock);


        //Execute Observer
        $this->object->execute($observer);

        $this->assertEquals($expected, $invoiceMock->getIncrementId());
    }

    /**
     * @dataProvider salesOrderInvoiceSaveBeforeDataProvider
     *
     * @param $input
     * @param $expected
     */
    public function testSalesOrderInvoiceSaveBeforeWithPrefix($input, $expected)
    {
        $this->object = $this->objectManager->getObject(
            \Fooman\SameOrderInvoiceNumber\Observer\InvoiceObserver::class,
            [
                'scopeConfig' => $this->getScopeConfigMock(true)
            ]
        );

        $invoiceMock = $this->getInvoiceMock(
            $input['order_increment_id'],
            $this->getInvoiceCollectionMock(
                self::TEST_PREFIX . $input['order_increment_id'],
                $input['existing_invoices']
            )
        );

        //Mock Observer
        /** @var \Magento\Framework\Event\Observer $observer */
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getInvoice'])
            ->getMock();

        $observer->expects($this->once())
            ->method('getInvoice')
            ->willReturn($invoiceMock);


        //Execute Observer
        $this->object->execute($observer);

        $this->assertEquals(self::TEST_PREFIX . $expected, $invoiceMock->getIncrementId());
    }


    /**
     * @return array
     */
    public function salesOrderInvoiceSaveBeforeDataProvider()
    {
        return [
            [
                'input'          => [
                    'order_increment_id' => '100000015',
                    'existing_invoices'  => 0
                ],
                'expectedResult' => '100000015',
            ],
            [
                'input'          => [
                    'order_increment_id' => '200000001',
                    'existing_invoices'  => 0
                ],
                'expectedResult' => '200000001',
            ],
            [
                'input'          => [
                    'order_increment_id' => 'TEST--001',
                    'existing_invoices'  => 0
                ],
                'expectedResult' => 'TEST--001',
            ],
            [
                'input'          => [
                    'order_increment_id' => '100000015',
                    'existing_invoices'  => 1
                ],
                'expectedResult' => '100000015-1',
            ],
            [
                'input'          => [
                    'order_increment_id' => '100000015',
                    'existing_invoices'  => 2
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
                    'sameorderinvoicenumber/settings/invoiceprefix',
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
