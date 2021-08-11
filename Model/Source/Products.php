<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Model\Source;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Products implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        CollectionFactory $productCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Retrieve options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        $result = [];
        foreach ($collection as $product) {
            $result[] = [
                'value' => $product->getId(),
                'label' => $product->getName() ?: $product->getSku(),
            ];
        }

        return $result;
    }
}
