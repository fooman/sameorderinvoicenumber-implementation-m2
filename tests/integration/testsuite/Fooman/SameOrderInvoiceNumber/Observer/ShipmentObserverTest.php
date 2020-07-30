<?php

namespace Fooman\SameOrderInvoiceNumber\Observer;

use Fooman\PhpunitBridge\BaseUnitTestCase;

/**
 * @magentoAppArea      adminhtml
 */
class ShipmentObserverTest extends BaseUnitTestCase
{

    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     */
    public function testShipmentNumberWithoutPrefix()
    {
        $shipment = $this->shipOrder();

        self::assertEquals('100000001', $shipment->getIncrementId());
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store sameorderinvoicenumber/settings/shipmentprefix SHIP-
     */
    public function testShipmentNumberWithPrefix()
    {
        $shipment = $this->shipOrder();

        self::assertEquals('SHIP-100000001', $shipment->getIncrementId());
    }

    /**
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    protected function shipOrder()
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $this->objectManager->create(\Magento\Sales\Api\Data\OrderInterface::class)
            ->load('100000001', 'increment_id');

        /** @var \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory */
        $shipmentFactory = $this->objectManager->get(\Magento\Sales\Model\Order\ShipmentFactory::class);

        //We want to ship all available items
        $itemsToShip = [];
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getQtyToShip() > 0) {
                $itemsToShip[$orderItem->getItemId()] = $orderItem->getQtyToShip();
            }
        }

        /** @var \Magento\Sales\Api\Data\ShipmentInterface $shipment */
        $shipment = $shipmentFactory->create($order, $itemsToShip);

        /** @var \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository */
        $shipmentRepository = $this->objectManager->get(\Magento\Sales\Api\ShipmentRepositoryInterface::class);
        $shipmentRepository->save($shipment);
        return $shipment;
    }
}
