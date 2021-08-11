<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Controller\Adminhtml\Test;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var boolean|\Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * @var \AnyPlaceMedia\SendSMS\Helper\SendSMS
     */
    protected $helper;

    /**
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \AnyPlaceMedia\SendSMS\Helper\SendSMS      $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \AnyPlaceMedia\SendSMS\Helper\SendSMS $helper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->helper            = $helper;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('AnyPlaceMedia_SendSMS::test');
        $resultPage->getConfig()->getTitle()->prepend(__('Test'));
        $resultPage->addBreadcrumb(__('AnyPlaceMedia'), __('AnyPlaceMedia'));
        $resultPage->addBreadcrumb(__('SendSMS'), __('Test'));

        # POST
        $phone   = $this->getRequest()->getParam('phone');
        $message = $this->getRequest()->getParam('message');
        $gdpr    = $this->getRequest()->getParam('gdpr');
        $short   = $this->getRequest()->getParam('short');

        if (!empty($phone) && !empty($message)) {
            $this->helper->sendSMS($phone, $message, 'test', $gdpr, $short);

            $messageBlock = $resultPage->getLayout()->createBlock(
                'Magento\Framework\View\Element\Messages',
                'answer'
            );

            $messageBlock->addSuccess(__('The message was sent.'));
            $resultPage->getLayout()->setChild(
                'sendsms_messages',
                $messageBlock->getNameInLayout(),
                'answer_alias'
            );
        }

        return $resultPage;
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return true;
    }
}
