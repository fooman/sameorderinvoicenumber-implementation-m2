<?php

namespace Fooman\SameOrderInvoiceNumber\Observer;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @magentoAppArea      adminhtml
 */
class InvoiceObserverTest extends TestCase
{

    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     */
    public function testInvoiceNumberWithoutPrefix()
    {
        $invoice = $this->invoiceOrder();

        self::assertEquals('100000001', $invoice->getIncrementId());
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store sameorderinvoicenumber/settings/invoiceprefix INV-
     */
    public function testInvoiceNumberWithPrefix()
    {
        $invoice = $this->invoiceOrder();

        self::assertEquals('INV-100000001', $invoice->getIncrementId());
    }

    /**
     * @return \Magento\Sales\Api\Data\InvoiceInterface
     */
    protected function invoiceOrder()
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $this->objectManager->create(\Magento\Sales\Api\Data\OrderInterface::class)
            ->load('100000001', 'increment_id');

        /** @var \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement */
        $invoiceManagement = $this->objectManager->get(\Magento\Sales\Api\InvoiceManagementInterface::class);

        /** @var \Magento\Sales\Api\Data\InvoiceInterface $shipment */
        $invoice = $invoiceManagement->prepareInvoice($order);

        /** @var \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository */
        $invoiceRepository = $this->objectManager->get(\Magento\Sales\Api\InvoiceRepositoryInterface::class);
        $invoiceRepository->save($invoice);
        return $invoice;
    }
}
