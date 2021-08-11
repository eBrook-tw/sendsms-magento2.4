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

class Filters implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var null|\Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\Registry $_coreRegistry
     */
    public function __construct(
        \Magento\Framework\Registry $_coreRegistry
    ) {
        $this->_coreRegistry = $_coreRegistry;
    }

    /**
     * Retrieve options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result  = [];
        $filters = $this->_coreRegistry->registry('sendsms_filters');
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $result[] = [
                    'value' => $filter['telephone'],
                    'label' => $filter['telephone'],
                ];
            }
        }

        return $result;
    }
}
