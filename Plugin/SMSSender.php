<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Plugin;

use AnyPlaceMedia\SendSMS\Helper\SendSMS;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class SMSSender
{
    /**
     * @var SendSMS
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param SendSMS         $helper
     * @param LoggerInterface $logger
     * @param string          $type
     */
    public function __construct(
        SendSMS $helper,
        LoggerInterface $logger,
        $type = null
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->type   = $type;
    }

    /**
     * Send SMS
     *
     * @param  Sender  $subject
     * @param  callable      $proceed
     * @param  AbstractModel $object
     * @param  boolean       $forceSyncMode
     * @return boolean
     */
    public function aroundSend(
        Sender $subject,
        callable $proceed,
        AbstractModel $object,
        $forceSyncMode = false
    ) {
        $sent = $proceed($object, $forceSyncMode);

        $gdpr = $this->helper->getValue(
            'sendsms_settings_order_messages/gdpr',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        $short = $this->helper->getValue(
            'sendsms_settings_order_messages/short',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        $message = $this->helper->processTemplate(
            $object,
            $this->type
        );

        $this->logger->debug($message);

        if ($message) {
            return $this->helper->sendSMS(
                $this->helper->getPhoneNumber($object, $this->type),
                $message,
                $this->type,
                $gdpr,
                $short
            );
        }

        return $sent;
    }
}
