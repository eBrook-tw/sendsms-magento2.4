<?php
/**
 * Ebrook
 *
 * @category    Ebrook
 * @package     AnyPlaceMedia_SendSMS
 * @copyright   Copyright © 2021 Ebrook co., ltd. (https://www.ebrook.com.tw)
 * @source https://github.com/sendSMS-RO/sendsms-magento2.4
 */

namespace AnyPlaceMedia\SendSMS\Helper;

use AnyPlaceMedia\SendSMS\Model\HistoryFactory;
use Exception;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Email\Model\Template\FilterFactory;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Http_Client;

class SendSMS extends AbstractHelper
{
    const XML_PATH_PREFIX = 'sendsms_settings/sendsms/%s';

    const BASE_URL = 'https://api.sendsms.ro/json?%s';

    const TYPE_ORDER = 'order';

    const TYPE_ORDER_COMMENT = 'order_comment';

    const TYPE_SHIPMENT = 'shipment';

    const TYPE_SHIPMENT_COMMENT = 'shipment_comment';

    const TYPE_INVOICE = 'invoice';

    const TYPE_INVOICE_COMMENT = 'invoice_comment';

    const TYPE_CREDITMEMO = 'creditmemo';

    const TYPE_CREDITMEMO_COMMENT = 'creditmemo_comment';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DateTime
     */
    protected $storeDate;

    /**
     * @var HistoryFactory
     */
    protected $history;

    /**
     * @var ConfigInterface
     */
    protected $resourceConfig;

    /**
     * @var CollectionFactory
     */
    protected $collection;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Write
     */
    protected $directory;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var Json
     */
    protected $jsonTool;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $pricingHelper;

    /**
     * @var FilterFactory
     */
    protected $templateFilterFactory;

    /**
     * @var null
     */
    protected $username = null;

    /**
     * @var null
     */
    protected $password = null;

    /**
     * @param ScopeConfigInterface  $scopeConfig
     * @param DateTime              $date
     * @param HistoryFactory        $history
     * @param ConfigInterface       $resourceConfig
     * @param CollectionFactory     $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Filesystem            $filesystem
     * @param CurlFactory           $curlFactory
     * @param Json                  $jsonTool
     * @param LoggerInterface       $logger
     * @param Data                  $pricingHelper
     * @param FilterFactory         $templateFilterFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        HistoryFactory $history,
        ConfigInterface $resourceConfig,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        CurlFactory $curlFactory,
        Json $jsonTool,
        LoggerInterface $logger,
        Data $pricingHelper,
        FilterFactory $templateFilterFactory
    ) {
        $this->scopeConfig    = $scopeConfig;
        $this->storeDate      = $date;
        $this->history        = $history;
        $this->resourceConfig = $resourceConfig;
        $this->collection     = $collectionFactory->create();
        $this->storeManager   = $storeManager;
        $this->filesystem     = $filesystem;
        $this->directory      = $filesystem->getDirectoryWrite(
            DirectoryList::VAR_DIR
        );
        $this->curlFactory           = $curlFactory;
        $this->jsonTool              = $jsonTool;
        $this->logger                = $logger;
        $this->pricingHelper         = $pricingHelper;
        $this->templateFilterFactory = $templateFilterFactory;
    }

    /**
     * Get config value
     *
     * @param  string $path
     * @param  string $scope
     * @param  string|int $scopeCode
     * @return mixed
     */
    public function getValue(
        $path,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        return $this->scopeConfig->getValue(
            sprintf(self::XML_PATH_PREFIX, $path),
            $scope,
            $scopeCode
        );
    }

    /**
     *
     * @param  null|int  $storeId
     * @return boolean
     */
    public function isEneabled($storeId = null)
    {
        return (boolean) (int) $this->getValue(
            'enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        if (null === $this->username) {
            $this->username = $this->getValue('sendsms_settings_username');
        }

        return $this->username;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        if (null === $this->password) {
            $this->password = trim(
                $this->getValue('sendsms_settings_password')
            );
        }

        return $this->password;
    }

    /**
     * Check credentials
     *
     * @return boolean
     */
    public function checkCredentials()
    {
        return $this->getUsername() && $this->getPassword();
    }

    /**
     * Request SMS gateway
     *
     * @param  string     $method
     * @param  array      $params
     * @param  array|null $data
     * @return Curl
     */
    public function doRequest($method, array $params, array $data = [])
    {
        $params = array_merge(
            [
                'username' => $this->getUsername(),
                'password' => $this->getPassword(),
            ],
            $params
        );
        $url = sprintf(
            self::BASE_URL,
            http_build_query($params, null, '&', PHP_QUERY_RFC3986)
        );

        $curl = $this->curlFactory->create();

        $curl->setOption(CURLOPT_HEADER, 0);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOption(CURLOPT_RETURNTRANSFER, 1);

        //set curl header
        $curl->addHeader('Content-Type', 'application/json');

        if (Zend_Http_Client::POST === $method) {
            $this->curl->addHeader('Connection', 'keep-alive');

            $curl->post($url, $data);
        } else {
            //get request with url
            $curl->get($url);
        }

        try {
            //read response
            $status = $this->jsonTool->unserialize($curl->getBody());
        } catch (\Exception $e) {
            $status = [
                'status'  => '-500',
                'message' => __('Request failed.'),
                'details' => $curl->getBody(),
            ];
        }

        return $status;
    }

    /**
     * Send a SMS
     *
     * @param  string  $phone
     * @param  string  $message
     * @param  string  $type
     * @param  boolean $gdpr
     * @param  boolean $short
     * @param  null    $storeId
     * @return void
     */
    public function sendSMS(
        $phone,
        $message,
        $type = 'order',
        $gdpr = false,
        $short = false,
        $storeId = null
    ) {
        $from = $this->getValue(
            'sendsms_settings_from',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $simulation = $this->getValue(
            'sendsms_settings_simulation',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($simulation && $type !== 'test') {
            $phone = $this->getValue(
                'sendsms_settings_simulation_number',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        $phone = $this->validatePhone($phone);

        if (!empty($phone) && $this->checkCredentials()) {
            $status = $this->doRequest(
                Zend_Http_Client::GET,
                [
                    'action' => 'message_send' . ($gdpr ? '_gdpr' : ''),
                    'from'   => $this->getValue(
                        'sendsms_settings_from',
                        ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    'to'     => $phone,
                    'text'   => $message,
                    'short'  => $short ? 'true' : 'false',
                ]
            );

            # add to history
            $history = $this->history->create();
            $history->addData([
                'status'  => $status['status'] ?? '',
                'message' => $status['message'] ?? '',
                'details' => $status['details'] ?? '',
                'content' => $message,
                'type'    => $type,
                'sent_on' => $this->storeDate->date(),
                'phone'   => $phone,
            ]);

            $history->save();

            $price     = $this->getValue('sendsms_settings_price');
            $priceTime = $this->getValue('sendsms_settings_price_date');
            $nowTime   = $this->storeDate->date('Y-m-d H:i:s');
            if (empty($priceTime) || empty($price) || $priceTime < $nowTime) {
                $this->routeCheckPrice($phone);
            }

            return true;
        }

        return false;
    }

    /**
     * Create a batch send Campaign
     *
     * @param  array $phones
     * @param  string $message
     * @return void
     * @throws Exception
     */
    public function batchCreate($phones, $message)
    {
        $from       = $this->getValue('sendsms_settings_from');
        $simulation = $this->getValue('sendsms_settings_simulation');

        $simulationPhone = false;
        if ($simulation) {
            $simulationPhone = $this->getValue('sendsms_settings_simulation_number');
        }

        try {
            $filepath = 'sendsms/batch.csv';
            $this->directory->create('sendsms');
            $stream = $this->directory->openFile($filepath, 'w+');

            $header = ['message', 'to', 'from'];
            $stream->writeCsv($header);

            foreach ($phones as $phone) {
                $data = [
                    $message,
                    $this->validatePhone($simulationPhone ?: $phone),
                    $from,
                ];

                $stream->writeCsv($data);
                if ($simulation) {
                    break;
                }
            }

            unset($stream);

            $name = 'Magento - ' . $this->storeManager
                ->getStore()->getName() . ' - ' . uniqid();

            $readableFile = $this->filesystem
                ->getDirectoryRead(DirectoryList::VAR_DIR)
                ->openFile('sendsms/batch.csv');
            $data = $readableFile->readAll();
            $readableFile->close();

            $status = $this->doRequest(
                Zend_Http_Client::POST,
                [
                    'action'     => 'batch_create',
                    'name'       => $name,
                    'start_time' => '',
                ],
                [
                    'data' => $data,
                ]
            );

            $this->directory->delete($filepath);

            # add to history
            $history = $this->history->create();
            $history->addData([
                'status'  => $status['status'] ?? '',
                'message' => $status['message'] ?? '',
                'details' => $status['details'] ?? '',
                'content' => __('We created your campaign. Go '
                    . 'and check the batch called: %1', $name),
                'type'    => __('Batch Campaign'),
                'sent_on' => $this->storeDate->date(),
                'phone'   => __('See hub.sendsms.ro'),
            ]);
            $history->save();

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Route check price
     *
     * @param  string $to
     * @return void
     */
    public function routeCheckPrice($to)
    {
        if ($this->checkCredentials()) {
            $status = $this->doRequest(
                Zend_Http_Client::GET,
                [
                    'action' => 'route_check_price',
                    'to'     => $to,
                ]
            );

            if (isset($status['details']['status'])
                && $status['details']['status'] === 64) {
                $this->resourceConfig->saveConfig(
                    'sendsms_settings/sendsms/sendsms_settings_price',
                    $status['details']['cost'],
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    Store::DEFAULT_STORE_ID
                );

                $this->resourceConfig->saveConfig(
                    'sendsms_settings/sendsms/sendsms_settings_price_date',
                    date('Y-m-d H:i:s', strtotime('+1 day')),
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    Store::DEFAULT_STORE_ID
                );
            }
        }
    }

    /**
     * Get account balance
     *
     * @return string|boolean
     */
    public function getBalance()
    {
        if ($this->checkCredentials()) {
            return $this->doRequest(
                Zend_Http_Client::GET,
                ['action' => 'user_get_balance']
            );
        }

        return false;
    }

    /**
     * Validate phone number
     *
     * @param  string $phoneNumber
     * @return string
     */
    public function validatePhone($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return '';
        }

        $phoneNumber = $this->clearPhoneNumber($phoneNumber);

        //Strip out leading zeros:
        //this will check the country code and apply it if needed
        $cc = $this->scopeConfig->getValue('sendsms_settings_prefix');

        if ($cc === 'INT') {
            return $phoneNumber;
        }

        $phoneNumber = ltrim($phoneNumber, '0');

        if (!preg_match('/^' . $cc . '/', $phoneNumber)) {
            $phoneNumber = $cc . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Normalize phone number
     *
     * @param  string $phoneNumber
     * @return string
     */
    public function clearPhoneNumber($phoneNumber)
    {
        $phoneNumber = str_replace(
            ['+', '-'],
            '',
            filter_var($phoneNumber, FILTER_SANITIZE_NUMBER_INT)
        );

        //Strip spaces and non-numeric characters:
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        return $phoneNumber;
    }

    /**
     * Clean specified characters
     *
     * @param  string $string
     * @return string
     */
    public function cleanDiacritice($string)
    {
        $bad = [
            "\xC4\x82", "\xC4\x83", "\xC3\x82", "\xC3\xA2",
            "\xC3\x8E", "\xC3\xAE", "\xC8\x98", "\xC8\x99",
            "\xC8\x9A", "\xC8\x9B", "\xC5\x9E", "\xC5\x9F",
            "\xC5\xA2", "\xC5\xA3", "\xC3\xA3", "\xC2\xAD",
            "\xe2\x80\x93",
        ];

        $cleanLetters = [
            "A", "a", "A", "a",
            "I", "i", "S", "s",
            "T", "t", "S", "s",
            "T", "t", "a", " ",
            "-",
        ];

        return str_replace($bad, $cleanLetters, $string);
    }

    /**
     * Get creditmemo comment transport
     *
     * @param  Creditmemo $creditmemo
     * @param  string     $comment
     * @return array
     */
    public function getCreditmemoCommentTransport(
        Creditmemo $creditmemo,
        $comment
    ) {
        $order = $creditmemo->getOrder();

        return [
            'order'      => $order,
            'creditmemo' => $creditmemo,
            'comment'    => $comment,
            'billing'    => $order->getBillingAddress(),
            'store'      => $order->getStore(),
            'order_data' => [
                'customer_name'         => $order->getCustomerName(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get creditmemo transport
     *
     * @param  Creditmemo $creditmemo
     * @return array
     */
    public function getCreditmemoTransport(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        return [
            'order'         => $order,
            'order_id'      => $order->getId(),
            'creditmemo'    => $creditmemo,
            'creditmemo_id' => $creditmemo->getId(),
            'comment'       => $creditmemo->getCustomerNoteNotify() ? $creditmemo->getCustomerNote() : '',
            'billing'       => $order->getBillingAddress(),
            'store'         => $order->getStore(),
            'order_data'    => [
                'customer_name'         => $order->getCustomerName(),
                'is_not_virtual'        => $order->getIsNotVirtual(),
                'email_customer_note'   => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get invoice comment transport
     *
     * @param  Invoice $invoice
     * @param  string  $comment
     * @return array
     */
    public function getInvoiceCommentTransport(
        Invoice $invoice,
        $comment
    ) {
        $order = $invoice->getOrder();

        return [
            'order'      => $order,
            'invoice'    => $invoice,
            'comment'    => $comment,
            'billing'    => $order->getBillingAddress(),
            'store'      => $order->getStore(),
            'order_data' => [
                'customer_name'         => $order->getCustomerName(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get invoice transport
     *
     * @param  Invoice $invoice
     * @return array
     */
    public function getInvoiceTransport(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        return [
            'order'      => $order,
            'order_id'   => $order->getId(),
            'invoice'    => $invoice,
            'invoice_id' => $invoice->getId(),
            'comment'    => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
            'billing'    => $order->getBillingAddress(),
            'store'      => $order->getStore(),
            'order_data' => [
                'customer_name'         => $order->getCustomerName(),
                'is_not_virtual'        => $order->getIsNotVirtual(),
                'email_customer_note'   => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get order comment transport
     *
     * @param  Order  $order
     * @param  string $comment
     * @return array
     */
    public function getOrderCommentTransport(Order $order, $comment)
    {
        return [
            'order'      => $order,
            'comment'    => $comment,
            'billing'    => $order->getBillingAddress(),
            'store'      => $order->getStore(),
            'order_data' => [
                'customer_name'         => $order->getCustomerName(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get order transport
     *
     * @param  Order  $order
     * @return string
     */
    public function getOrderTransport(Order $order)
    {
        return [
            'order'                => $order,
            'order_id'             => $order->getId(),
            'billing'              => $order->getBillingAddress(),
            'store'                => $order->getStore(),
            'created_at_formatted' => $order->getCreatedAtFormatted(2),
            'order_data'           => [
                'customer_name'         => $order->getCustomerName(),
                'is_not_virtual'        => $order->getIsNotVirtual(),
                'email_customer_note'   => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get shipment comment transport
     *
     * @param  Shipment $shipment
     * @param  string   $comment
     * @return array
     */
    public function getShipmentCommentTransport(Shipment $shipment, $comment)
    {
        $order = $shipment->getOrder();
        return [
            'order'      => $order,
            'shipment'   => $shipment,
            'comment'    => $comment,
            'billing'    => $order->getBillingAddress(),
            'store'      => $order->getStore(),
            'order_data' => [
                'customer_name'         => $order->getCustomerName(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get shipment transport
     *
     * @param  Shipment $shipment
     * @return array
     */
    public function getShipmentTransport(Shipment $shipment)
    {
        $order = $shipment->getOrder();

        return [
            'order'       => $order,
            'order_id'    => $order->getId(),
            'shipment'    => $shipment,
            'shipment_id' => $shipment->getId(),
            'comment'     => $shipment->getCustomerNoteNotify() ? $shipment->getCustomerNote() : '',
            'billing'     => $order->getBillingAddress(),
            'store'       => $order->getStore(),
            'order_data'  => [
                'customer_name'         => $order->getCustomerName(),
                'is_not_virtual'        => $order->getIsNotVirtual(),
                'email_customer_note'   => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
            ],
        ];
    }

    /**
     * Get transport
     *
     * @param  AbstractModel $object
     * @param  string        $type
     * @param  string        $comment
     * @return string
     */
    public function getTransport(
        AbstractModel $object,
        $type,
        $comment = ''
    ) {
        $transport = [];
        switch ($type) {
            case self::TYPE_ORDER:
                $transport = $this->getOrderTransport($object);
                break;

            case self::TYPE_ORDER_COMMENT:
                $transport = $this->getOrderCommentTransport($object, $comment);
                break;

            case self::TYPE_SHIPMENT:
                $transport = $this->getShipmentTransport($object);
                break;

            case self::TYPE_SHIPMENT_COMMENT:
                $transport = $this->getShipmentCommentTransport(
                    $object,
                    $comment
                );
                break;

            case self::TYPE_INVOICE:
                $transport = $this->getInvoiceTransport($object);
                break;

            case self::TYPE_INVOICE_COMMENT:
                $transport = $this->getInvoiceCommentTransport(
                    $object,
                    $comment
                );
                break;

            case self::TYPE_CREDITMEMO:
                $transport = $this->getCreditmemoTransport($object);
                break;

            case self::TYPE_CREDITMEMO_COMMENT:
                $transport = $this->getCreditmemoCommentTransport(
                    $object,
                    $comment
                );
                break;
        }

        return $transport;
    }

    /**
     * Process sales SMS template
     *
     * @param  AbstractModel $object
     * @param  string        $type
     * @param  string        $comment
     * @return string
     */
    public function processTemplate(
        AbstractModel $object,
        $type,
        $comment = ''
    ) {
        $message = $this->getValue(
            sprintf('sendsms_settings_order_messages/%s', $type),
            ScopeInterface::SCOPE_STORE,
            $object->getStoreId()
        );

        $filter = $this->templateFilterFactory->create([
            'variables' => $this->getTransport(
                $object,
                $type,
                $comment
            ),
        ]);

        return $filter->filter($message);
    }

    /**
     * Get phone number
     *
     * @param  AbstractModel $object
     * @param  string $type
     * @return string
     */
    public function getPhoneNumber(AbstractModel $object, $type)
    {
        $phoneNumber = '';
        switch ($type) {
            case self::TYPE_ORDER:
            case self::TYPE_ORDER_COMMENT:
                $phoneNumber = $object->getBillingAddress()->getTelephone();
                break;

            case self::TYPE_SHIPMENT:
            case self::TYPE_SHIPMENT_COMMENT:
            case self::TYPE_INVOICE:
            case self::TYPE_INVOICE_COMMENT:
            case self::TYPE_CREDITMEMO:
            case self::TYPE_CREDITMEMO_COMMENT:
                $order       = $object->getOrder();
                $phoneNumber = $order->getBillingAddress()->getTelephone();
                break;
        }

        return $phoneNumber;
    }
}
