<?php
/**
 * @copyright  Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Fooman\SameOrderInvoiceNumber\Observer;

class InvoiceObserver extends AbstractObserver
{

    /**
     * path for prefix config setting
     *
     * @var string
     */
    protected $prefixConfigPath = 'sameorderinvoicenumber/settings/invoiceprefix';

    /**
     * @param $order
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection
     */
    public function getCollection($order)
    {
        return $order->getInvoiceCollection();
    }

    /**
     * change the invoice increment to the order increment id
     * only affects invoices without id (=new invoices)
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->assignIncrement($observer->getInvoice());
    }
}
