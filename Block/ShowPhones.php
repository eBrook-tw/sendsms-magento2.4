<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ShowPhones extends Template
{
    protected $_coreRegistry;

    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    public function getPhones()
    {
        $phonesno = 0;
        if ($this->_coreRegistry->registry('phonesno')) {
            $phonesno = $this->_coreRegistry->registry('phonesno');
        }
        return "We found $phonesno phone numbers matching the filters.";
    }
}
