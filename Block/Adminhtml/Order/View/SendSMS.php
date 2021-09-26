<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Block\Adminhtml\Order\View;

use AnyPlaceMedia\SendSMS\Helper\SendSMS as SmsHelper;
use AnyPlaceMedia\SendSMS\Model\Order;
use Magento\Framework\App\ObjectManager;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;

class SendSMS extends \Magento\Backend\Block\Widget
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Admin helper
     *
     * @var \Magento\Sales\Helper\Admin
     */
    protected $_adminHelper;

    /**
     * @var SmsHelper
     */
    protected $smsHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Sales\Helper\Admin             $adminHelper
     * @param SmsHelper                               $smsHelper
     * @param array                                   $data
     * @param ShippingHelper|null                     $shippingHelper
     * @param TaxHelper|null                          $taxHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        SmsHelper $smsHelper,
        array $data = [],
        ? ShippingHelper $shippingHelper = null,
        ? TaxHelper $taxHelper = null
    ) {
        $this->_coreRegistry    = $registry;
        $this->_adminHelper     = $adminHelper;
        $this->smsHelper        = $smsHelper;
        $data['shippingHelper'] = $shippingHelper ?? ObjectManager::getInstance()->get(ShippingHelper::class);
        $data['taxHelper']      = $taxHelper ?? ObjectManager::getInstance()->get(TaxHelper::class);
        parent::__construct($context, $data);
    }

    /**
     * Preparing global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('order_sendsms').parentNode, '" . $this->getSubmitUrl() . "')";
        $button  = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('Send'), 'class' => 'action-save action-secondary', 'onclick' => $onclick]
        );
        $this->setChild('submit_button', $button);
        return parent::_prepareLayout();
    }

    /**
     * get order info
     *
     * @return Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->getData('order');
        }
        if ($this->_coreRegistry->registry('current_order')) {
            return $this->_coreRegistry->registry('current_order');
        }
        if ($this->_coreRegistry->registry('order')) {
            return $this->_coreRegistry->registry('order');
        }
        return false;
    }

    /**
     * Get all phones coresponding to an order
     *
     * @return array
     */
    public function getPhones()
    {
        $order = $this->getOrder();
        if ($order) {
            return array_unique([
                $order->getBillingAddress()->getTelephone(),
                $order->getShippingAddress()->getTelephone(),
            ]);
        }
        return [];
    }

    /**
     * Check if show SMS section
     *
     * @return array
     */
    protected function _toHtml()
    {
        $order = $this->getOrder();
        if (!$order || !$this->smsHelper->isEnabled($order->getStoreId())) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Submit URL getter
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('sendsms/order', ['order_id' => $this->getOrder()->getId()]);
    }
}
