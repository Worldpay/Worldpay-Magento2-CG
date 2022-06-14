<?php
//error_reporting(0);
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Payment;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;
use Laminas\Uri\UriFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Pay extends \Magento\Framework\App\Action\Action
{
    public const PAYMENT_MANIFEST_JSON = 'payment-manifest.json';
    public const MANIFEST_JSON = 'manifest.json';
   
    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */

    /**
     * @var curlHelper
     */
    public $curlHelper;

    /**
     * @var _rawBody
     */
    protected $_assetRepo;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Sapient\Worldpay\Helper\CurlHelper $curlHelper
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Setup\JsonPersistor $jsonPersistor
     * @param \Sapient\Worldpay\Helper\Data $wpHelper
     * @param \Magento\Framework\Filesystem\Io\File $file
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Sapient\Worldpay\Helper\CurlHelper $curlHelper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Setup\JsonPersistor $jsonPersistor,
        \Sapient\Worldpay\Helper\Data $wpHelper,
        \Magento\Framework\Filesystem\Io\File $file
    ) {
        
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->curlHelper = $curlHelper;
        $this->_assetRepo = $assetRepo;
        $this->_filesystem = $filesystem;
        $this->jsonPersistor = $jsonPersistor;
        $this->wpHelper = $wpHelper;
        $this->file  =  $file;
    }

    /**
     * Execute
     */
    public function execute()
    {
        
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        
        $mediPathPaymentManifest = $this->wpHelper->getBaseUrlMedia('sapient_worldpay/'.self::PAYMENT_MANIFEST_JSON);
        $mediPathManifest = $this->wpHelper->getBaseUrlMedia('sapient_worldpay/'.self::MANIFEST_JSON);

        $paymentManifestJson = [
            'default_applications' => [
                $mediPathManifest
            ],
            'supported_origins'=> [
                $this->wpHelper->getBaseUrl()
            ]
        ];
        $paymentAppManifestjson = [
            'name'=>'Pay with Worldpay',
            'short_name'=>'Worldpay',
            'description'=>'Worldpay Payments',
            'icons'=> [
                    [
                        'src' => $this->_assetRepo->getUrl("Sapient_Worldpay::images/cc/worldpay_logo.png"),
                        'sizes' => '48x48',
                        'type' => 'image/png'
                    ]
                ],
            'serviceworker'=>[
                'src' => $this->_assetRepo->getUrl("Sapient_Worldpay::chromepay/sw.js"),
                'scope'=> $this->_assetRepo->getUrl("Sapient_Worldpay::chromepay/").'/',
                'use_cache' => false
            ]
        ];

        $this->jsonGenerator($paymentManifestJson, self::PAYMENT_MANIFEST_JSON);
        $this->jsonGenerator($paymentAppManifestjson, self::MANIFEST_JSON);
        
        $result->setHeader('link', '<'.$mediPathPaymentManifest.'>; rel="payment-method-manifest"');
        return $result;
    }

    /**
     * JsonGenerator
     *
     * @param array $content
     * @param string $filename
     */
    public function jsonGenerator(array $content, $filename)
    {
        //$content = [];
        $mediaPath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)
                    ->getAbsolutePath()
                    .'sapient_worldpay';
        $filePath = $mediaPath
                    . DIRECTORY_SEPARATOR
                    . $filename;

        if ($this->file->fileExists($filePath)) {
            return;
        }
        
        try {
            $this->file->checkAndCreateFolder($mediaPath);
            $this->jsonPersistor->persist($content, $filePath);
        } catch (\Exception $e) {
            $this->wplogger->info($e->getMessage());
        }
    }
}
