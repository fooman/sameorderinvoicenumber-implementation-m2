<?php
/**
 * @copyright  Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Fooman\SameOrderInvoiceNumber\Observer;

abstract class AbstractObserver implements \Magento\Framework\Event\ObserverInterface
{
    /** @var null|string[] */
    private $existingIncrementIds;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /** @var \Magento\Sales\Model\Order */
    private $order;

    /**
     * path for prefix config setting
     *
     * @var string
     */
    protected $prefixConfigPath;

    /** @var string */
    private $prefixedIncrementId;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param null $storeId
     *
     * @return mixed|string
     */
    public function getPrefixSetting($storeId = null)
    {
        return $this->scopeConfig->getValue(
            $this->prefixConfigPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection\AbstractCollection
     */
    abstract public function getCollection($order);

    /**
     * @param $entity
     */
    public function assignIncrement($entity)
    {
        if ($entity->getId()) {
            return;
        }

        $this->order = $entity->getOrder();
        $this->existingIncrementIds = null;

        $prefix = (string) $this->getPrefixSetting($this->order->getStoreId());
        $this->prefixedIncrementId = $prefix . $this->order->getIncrementId();

        $newNr = $this->prefixedIncrementId;

        $suffix = 0;
        while ($this->alreadyExists($newNr)) {
            $suffix++;
            $newNr = $this->prefixedIncrementId . '-' . $suffix;
        }

        $entity->setIncrementId($newNr);
    }

    public function alreadyExists(string $incrementId): bool
    {
        if (!isset($this->existingIncrementIds)) {
            $collection = $this->getCollection($this->order);
            $collection->clear();
            $collection->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);
            $collection->addAttributeToFilter('increment_id', ['like' => $this->prefixedIncrementId . '%']);

            $this->existingIncrementIds = [];
            foreach ($collection as $entity) {
                $this->existingIncrementIds[] = $entity->getIncrementId();
            }
        }

        return in_array($incrementId, $this->existingIncrementIds);
    }
}
