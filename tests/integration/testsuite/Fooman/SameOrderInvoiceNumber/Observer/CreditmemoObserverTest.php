<?php

namespace Fooman\SameOrderInvoiceNumber\Observer;

use Fooman\PhpunitBridge\BaseUnitTestCase;

/**
 * @magentoAppArea      adminhtml
 */
class CreditmemoObserverTest extends BaseUnitTestCase
{

    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     */
    public function testCreditmemoNumberWithoutPrefix()
    {
        $creditmemo = $this->creditOrder();

        $this->assertEquals('100000001', $creditmemo->getIncrementId());
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store sameorderinvoicenumber/settings/creditmemoprefix CR-
     */
    public function testCreditmemoNumberWithPrefix()
    {
        $creditmemo = $this->creditOrder();

        $this->assertEquals('CR-100000001', $creditmemo->getIncrementId());
    }

    /**
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     */
    protected function creditOrder()
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $this->objectManager->create(\Magento\Sales\Api\Data\OrderInterface::class)
            ->load('100000001', 'increment_id');

        /** @var \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory */
        $creditmemoFactory = $this->objectManager->get(\Magento\Sales\Model\Order\CreditmemoFactory::class);

        /** @var \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo */
        $creditmemo = $creditmemoFactory->createByOrder($order);

        /** @var \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository */
        $creditmemoRepository = $this->objectManager->get(\Magento\Sales\Api\CreditmemoRepositoryInterface::class);
        $creditmemoRepository->save($creditmemo);
        return $creditmemo;
    }
}
