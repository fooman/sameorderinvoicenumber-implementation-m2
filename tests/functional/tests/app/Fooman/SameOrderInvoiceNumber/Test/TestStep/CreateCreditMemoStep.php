<?php

namespace Fooman\SameOrderInvoiceNumber\Test\TestStep;

use Magento\Mtf\Client\Locator;
use Magento\Checkout\Test\Fixture\Cart;
use Magento\Sales\Test\Fixture\OrderInjectable;
use Magento\Sales\Test\Page\Adminhtml\OrderCreditMemoNew;
use Magento\Sales\Test\Page\Adminhtml\OrderIndex;
use Magento\Sales\Test\Page\Adminhtml\SalesOrderView;

class CreateCreditMemoStep extends \Magento\Sales\Test\TestStep\CreateCreditMemoStep
{
    protected $cart;

    /**
     * @param Cart               $cart
     * @param OrderIndex         $orderIndex
     * @param SalesOrderView     $salesOrderView
     * @param OrderInjectable    $order
     * @param OrderCreditMemoNew $orderCreditMemoNew
     * @param array              $data
     */
    public function __construct(
        Cart $cart,
        OrderIndex $orderIndex,
        SalesOrderView $salesOrderView,
        OrderInjectable $order,
        OrderCreditMemoNew $orderCreditMemoNew,
        $data = null
    ) {
        $this->cart = $cart;
        $this->orderIndex = $orderIndex;
        $this->salesOrderView = $salesOrderView;
        $this->order = $order;
        $this->orderCreditMemoNew = $orderCreditMemoNew;
        $this->data = $data;
    }

    public function run()
    {
        $this->orderIndex->open();
        $this->orderIndex->getSalesOrderGrid()->searchAndOpen(['id' => $this->order->getId()]);
        $this->salesOrderView->getOrderForm()->waitForElementVisible(
            '#order_creditmemo', Locator::SELECTOR_CSS
        );
        sleep(5);
        $this->salesOrderView->getPageActions()->orderCreditMemo();

        $this->salesOrderView->getOrderForm()->waitForElementVisible(
            '#edit_form', Locator::SELECTOR_CSS
        );

        if (is_callable([$this, 'compare'])) {
            $items = $this->cart->getItems();
            $this->orderCreditMemoNew->getFormBlock()->fillProductData($this->data, $items);
            if ($this->compare($items, $this->data)) {
                $this->orderCreditMemoNew->getFormBlock()->updateQty();
            }
            $this->orderCreditMemoNew->getFormBlock()->fillFormData($this->data);
        } elseif (!empty($this->data)) {
            $this->orderCreditMemoNew->getFormBlock()->fillProductData(
                $this->data,
                $this->order->getEntityId()['products']
            );
            $this->orderCreditMemoNew->getFormBlock()->updateQty();
            $this->orderCreditMemoNew->getFormBlock()->fillFormData($this->data);
        }

        $this->orderCreditMemoNew->getFormBlock()->submit();
        sleep(5);
        return [
            'ids' => ['creditMemoIds' => $this->getCreditMemoIds()]
        ];
    }

    /**
     * Get credit memo ids.
     *
     * @return array
     */
    protected function getCreditMemoIds()
    {
        $orderForm = $this->salesOrderView->getOrderForm();
        $orderForm->waitForElementVisible('#sales_order_view_tabs_order_creditmemos', Locator::SELECTOR_CSS);
        sleep(2);
        $orderForm->openTab('creditmemos');
        $orderForm->waitForElementVisible('#sales_order_view_tabs_order_creditmemos_content', Locator::SELECTOR_CSS);
        return $orderForm->getTab('creditmemos')->getGridBlock()->getIds();
    }
}
