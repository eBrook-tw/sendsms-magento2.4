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

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

class Regions implements \Magento\Framework\Option\ArrayInterface
{
    public function __construct(
        CollectionFactory $customerCollectionFactory,
        ResourceConnection $connection
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->connection                = $connection;
    }

    /**
     * Retrieve options array.
     *
     * @return array
     */
    public function toOptionArray()
    {

        $customerAddressEntityTableName = $this->connection->getTableName(
            'customer_address_entity'
        );
        $collection = $this->customerCollectionFactory->create();
        $collection->getSelect()->join(
            $customerAddressEntityTableName,
            'e.entity_id=' . $customerAddressEntityTableName . '.parent_id',
            ['region', 'region_id']
        );

        $data = $collection->getData();

        $result = [];
        foreach ($data as $customer) {
            $option = [
                'value' => $customer['region'],
                'label' => $customer['region'],
            ];

            if (!in_array($option, $result)) {
                $result[] = $option;
            }
        }

        return $result;
    }
}
