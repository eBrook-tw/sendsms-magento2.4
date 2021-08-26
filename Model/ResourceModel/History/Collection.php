<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Model\ResourceModel\History;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \AnyPlaceMedia\SendSMS\Model\History::class,
            \AnyPlaceMedia\SendSMS\Model\ResourceModel\History::class
        );
    }
}
