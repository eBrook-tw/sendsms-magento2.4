<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Model\ResourceModel;

/**
 * SendSMS history mysql resource
 */
class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'history_id';

    /**
     * @var string
     */
    protected $_mainTable = 'sendsms_history';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sendsms_history', 'history_id');
    }
}
