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

class GenericButton
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    protected $collection;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $collectionFactory
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->registry   = $registry;
        $this->collection = $collectionFactory->create();
    }

    /**
     * Return the synonyms group Id.
     *
     * @return int|null
     */
    public function getId()
    {
        $contact = $this->registry->registry('campaign');
        return $contact ? $contact->getId() : null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
