<?php

namespace Fooman\SameOrderInvoiceNumber\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ShipmentObserverTest extends \Fooman\PhpunitBridge\BaseUnitTestCase
{
    const TEST_STORE_ID = 1;
    const TEST_PREFIX = 'SHIP-';

    /** @var ShipmentObserver */
    protected $object;

    /** @var ObjectManager */
    protected $objectManager;


    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * @param     $orderIncrement
     * @param int $existingShipments
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getShipmentCollectionMock($orderIncrement, $existingShipments = 0)
    {
        $shipmentCollectionFactoryMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection::class,
            ['getSize', 'getIterator']
        );
        $shipmentCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('getSize')
            ->will($this->returnValue($existingShipments));

        $items = [];

        switch ($existingShipments) {
            case 2:
                $shipmentMock = $this->createPartialMock(
                    \Magento\Sales\Model\Order\Shipment::class,
                    ['getIncrementId']
                );
                $shipmentMock->expects($this->any())
                    ->method('getIncrementId')
                    ->willReturn($orderIncrement . '-1');
                $items[1] = $shipmentMock;
            //no break intentionally
            case 1:
                $shipmentMock = $this->createPartialMock(
                    \Magento\Sales\Model\Order\Shipment::class,
                    ['getIncrementId']
                );
                $shipmentMock->expects($this->any())
                    ->method('getIncrementId')
                    ->willReturn($orderIncrement);
                $items[0] = $shipmentMock;
                break;
        }

        $shipmentCollectionFactoryMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        return $shipmentCollectionFactoryMock;
    }

    /**
     * @param $orderIncrement
     * @param $shipmentCollectionMock
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getShipmentMock($orderIncrement, $shipmentCollectionMock)
    {
        //Mock Order
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIncrementId', 'getStoreId', 'getShipmentsCollection'])
            ->getMock();

        $orderMock->expects($this->any())
            ->method('getIncrementId')
            ->will($this->returnValue($orderIncrement));

        $orderMock->expects($this->any())
            ->method('getStoreId')
            ->will($this->returnValue(self::TEST_STORE_ID));

        $orderMock->expects($this->any())
            ->method('getShipmentsCollection')
            ->will($this->returnValue($shipmentCollectionMock));

        //Mock Shipment
        $shipmentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Shipment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getId'])
            ->getMock();

        $shipmentMock->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));

        $shipmentMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(null));

        return $shipmentMock;
    }

    /**
     * @dataProvider salesOrderShipmentSaveBeforeDataProvider
     *
     * @param $input
     * @param $expected
     */
    public function testSalesOrderShipmentSaveBefore($input, $expected)
    {
        $this->object = $this->objectManager->getObject(
            \Fooman\SameOrderInvoiceNumber\Observer\ShipmentObserver::class,
            [
                'scopeConfig' => $this->getScopeConfigMock()
            ]
        );

        $shipmentMock = $this->getShipmentMock(
            $input['order_increment_id'],
            $this->getShipmentCollectionMock($input['order_increment_id'], $input['existing_shipments'])
        );

        //Mock Observer
        /** @var \Magento\Framework\Event\Observer $observer */
        $observer = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getShipment']);
        $observer->expects($this->once())
            ->method('getShipment')
            ->will($this->returnValue($shipmentMock));


        //Execute Observer
        $this->object->execute($observer);

        $this->assertEquals($expected, $shipmentMock->getIncrementId());
    }

    /**
     * @dataProvider salesOrderShipmentSaveBeforeDataProvider
     *
     * @param $input
     * @param $expected
     */
    public function testSalesOrderShipmentSaveBeforeWithPrefix($input, $expected)
    {
        $this->object = $this->objectManager->getObject(
            \Fooman\SameOrderInvoiceNumber\Observer\ShipmentObserver::class,
            [
                'scopeConfig' => $this->getScopeConfigMock(true)
            ]
        );

        $shipmentMock = $this->getShipmentMock(
            $input['order_increment_id'],
            $this->getShipmentCollectionMock(
                self::TEST_PREFIX . $input['order_increment_id'],
                $input['existing_shipments']
            )
        );

        //Mock Observer
        /** @var \Magento\Framework\Event\Observer $observer */
        $observer = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getShipment']);
        $observer->expects($this->once())
            ->method('getShipment')
            ->will($this->returnValue($shipmentMock));


        //Execute Observer
        $this->object->execute($observer);

        $this->assertEquals(self::TEST_PREFIX . $expected, $shipmentMock->getIncrementId());
    }


    /**
     * @return array
     */
    public function salesOrderShipmentSaveBeforeDataProvider()
    {
        return [
            [
                'input'          => [
                    'order_increment_id' => '100000015',
                    'existing_shipments' => 0
                ],
                'expectedResult' => '100000015',
            ],
            [
                'input'          => [
                    'order_increment_id' => '200000001',
                    'existing_shipments' => 0
                ],
                'expectedResult' => '200000001',
            ],
            [
                'input'          => [
                    'order_increment_id' => 'TEST--001',
                    'existing_shipments' => 0
                ],
                'expectedResult' => 'TEST--001',
            ],
            [
                'input'          => [
                    'order_increment_id' => '100000015',
                    'existing_shipments' => 1
                ],
                'expectedResult' => '100000015-1',
            ],
            [
                'input'          => [
                    'order_increment_id' => '100000015',
                    'existing_shipments' => 2
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
                    'sameorderinvoicenumber/settings/shipmentprefix',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    self::TEST_STORE_ID
                )
                ->will($this->returnValue(self::TEST_PREFIX));
        } else {
            $scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        }
        return $scopeConfigMock;
    }
}
