<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Recurring\Order;

use Sapient\Worldpay\Api\UpdateRecurringTokenInterface;

use Magento\Framework\Registry;
use Sapient\Worldpay\Model\Recurring\SubscriptionFactory;

class UpdateRecurringToken implements UpdateRecurringTokenInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\OrderFactory $orderFactory
     */
    protected $orderFactory;
    
    /**
     * @var RateRequestFactory $rateRequestFactory
     */
    protected $rateRequestFactory;

    /**
     * @var RateCollectorInterfaceFactory $rateCollector
     */
    protected $rateCollector;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var SubscriptionFactory $subscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var Transactions $recurringTransactions
     */
    protected $transactionCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * @var Registry $registry
     */
    protected $coreRegistry;

    /**
     * @var \Sapient\Worldpay\Model\Token\Service $savedTokenService
     */
    protected $savedTokenService;
    /**
     * @var \Sapient\Worldpay\Model\SavedToken $savedToken
     */
    protected $savedToken;

    /**
     * @var EditSubscriptionHistoryRepository $editSubscriptionRepository
     */
    protected $editSubscriptionRepository;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param SubscriptionFactory $subscriptionFactory
     * @param Transactions $recurringTransactions
     * @param Registry $registry
     * @param Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Model\Token\Service $savedTokenService
     * @param \Sapient\Worldpay\Model\SavedToken $savedToken
     * @param EditSubscriptionHistoryRepository $editSubscriptionRepository
     *
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SubscriptionFactory $subscriptionFactory,
        \Sapient\Worldpay\Model\Recurring\Subscription\Transactions $recurringTransactions,
        Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Model\Token\Service $savedTokenService,
        \Sapient\Worldpay\Model\SavedToken $savedToken,
        \Sapient\Worldpay\Model\EditSubscriptionHistoryRepository $editSubscriptionRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->coreRegistry = $registry;
        $this->customerSession = $customerSession;
        $this->transactionCollectionFactory = $recurringTransactions;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->savedTokenService = $savedTokenService;
        $this->savedToken = $savedToken;
        $this->editSubscriptionRepository = $editSubscriptionRepository;
    }

    /**
     * Get store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get token
     *
     * @param int $id
     * @return string
     */
    public function getToken($id)
    {
        return $this->savedToken->load($id);
    }

    /**
     * Get Current Subscription Order
     *
     * @return mixed
     */
    public function getCurrentSubscriptionOrder()
    {
        return $this->coreRegistry->registry('current_subscription_order');
    }

    /**
     * Get Active Recurring Transaction
     *
     * @param int $subscriptionId
     * @return mixed
     */
    public function getActiveRecurringTransaction($subscriptionId)
    {
        $customer = $this->customerSession->getCustomer();
        return $this->transactionCollectionFactory->getCollection()
            ->addFieldToFilter('status', ['eq' => 'active'])
            ->addFieldToFilter('customer_Id', ['eq' => $customer->getId()])
            ->addFieldToFilter('subscription_id', ['eq' => $subscriptionId]);
    }

    /**
     * Update Token Data
     *
     * @param string $tokenId
     * @param string $subscriptionId
     * @return string
     */

    public function updateRecurringPaymentToken($tokenId, $subscriptionId): string
    {
        $result = [
            'success' => false,
            'msg' => ''
        ];
        try {
            $currenntActiveTransaction = $this->getActiveRecurringTransaction($subscriptionId);

            if ($currenntActiveTransaction->getSize()) {
                
                $transaction = $currenntActiveTransaction->getFirstItem();
                $this->verifyTokenFromWorldpay($tokenId);

                $subscriptionOldInfo = $this->editSubscriptionRepository->getSubscriptionData($subscriptionId);
                $shippingOldAddress = $subscriptionOldInfo->getShippingAddress();
                $transactionData = $this->getTransactionData($subscriptionOldInfo);
                $paymentMethod = $transactionData->getMethod();

                $transaction->setData('worldpay_token_id', $tokenId);
                $transaction->save();
    
                $this->editSubscriptionRepository
                    ->updateEditHistory(
                        $subscriptionOldInfo,
                        $shippingOldAddress,
                        $paymentMethod
                    );
                $result = [
                    'success' => true,
                    'msg' => 'Token Updated in Recurring order'
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
        return json_encode($result);
    }

    /**
     * Verify Token From Worldpay
     *
     * @param int $savedTokenId
     * @return mixed
     */
    public function verifyTokenFromWorldpay($savedTokenId)
    {
        $customer = $this->customerSession->getCustomer();
        $savedToken = $this->getToken($savedTokenId);
        return $this->savedTokenService->getTokenInquiry(
            $savedToken,
            $customer,
            $this->getStoreId()
        );
    }

    /**
     * Get Transaction Data
     *
     * @param Subscription $subscriptionOrder
     * @return mixed
     */
    public function getTransactionData(\Sapient\Worldpay\Model\Recurring\Subscription $subscriptionOrder)
    {
        return $subscriptionOrder->getTransactionData();
    }
}
