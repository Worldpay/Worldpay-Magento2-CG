<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Controller\Button;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InstantPurchase\Model\InstantPurchaseOptionLoadingFactory;
use Magento\InstantPurchase\Model\PlaceOrder as PlaceOrderModel;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Instant Purchase order placement.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrder extends Action
{
    /**
     * List of request params that handled by the controller.
     *
     * @var array
     */
    private static $knownRequestParams = [
        'form_key',
        'product',
        'instant_purchase_payment_token',
        'instant_purchase_shipping_address',
        'instant_purchase_billing_address',
    ];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var InstantPurchaseOptionLoadingFactory
     */
    private $instantPurchaseOptionLoadingFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var PlaceOrderModel
     */
    private $placeOrder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param InstantPurchaseOptionLoadingFactory $instantPurchaseOptionLoadingFactory
     * @param ProductRepositoryInterface $productRepository
     * @param PlaceOrderModel $placeOrder
     * @param Magento\Checkout\Model\Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Magento\Framework\App\Response\RedirectInterface $redirect
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        InstantPurchaseOptionLoadingFactory $instantPurchaseOptionLoadingFactory,
        ProductRepositoryInterface $productRepository,
        PlaceOrderModel $placeOrder,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->instantPurchaseOptionLoadingFactory = $instantPurchaseOptionLoadingFactory;
        $this->productRepository = $productRepository;
        $this->placeOrder = $placeOrder;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->redirect = $redirect;
    }

    /**
     * Place an order for a customer.
     *
     * @return JsonResult
     */
    public function execute()
    {
        $request = $this->getRequest();
        if (!$this->doesRequestContainAllKnowParams($request)) {
            return $this->createResponse($this->createGenericErrorMessage(), false);
        }
        if (!$this->formKeyValidator->validate($request)) {
            return $this->createResponse($this->createGenericErrorMessage(), false);
        }

        $paymentTokenPublicHash = (string)$request->getParam('instant_purchase_payment_token');
        $shippingAddressId = (int)$request->getParam('instant_purchase_shipping_address');
        $billingAddressId = (int)$request->getParam('instant_purchase_billing_address');
        $carrierCode = (string)$request->getParam('instant_purchase_carrier');
        $shippingMethodCode = (string)$request->getParam('instant_purchase_shipping');
        $browserScreenHeight = (string)$request->getParam('browserScreenHeight');
        $browserScreenWidth = (string)$request->getParam('browserScreenWidth');
        $browserColorDepth = (string)$request->getParam('browserColorDepth');
        $productId = (int)$request->getParam('product');
        $productRequest = $this->getRequestUnknownParams($request);
        if (!($request->getParam('instant_purchase_dfreference') === null)) {
            $dfReferenceId = (string)$request->getParam('instant_purchase_dfreference');
            if ($dfReferenceId) {
                $this->checkoutSession->setDfReferenceId($dfReferenceId);
                $this->checkoutSession->setInstantPurchaseOrder(true);
                $this->checkoutSession->setBrowserScreenHeight($browserScreenHeight);
                $this->checkoutSession->setBrowserScreenWidth($browserScreenWidth);
                $this->checkoutSession->setBrowserColorDepth($browserColorDepth);
                $this->checkoutSession->setInstantPurchaseRedirectUrl($this->redirect->getRefererUrl());
            }
        }
        try {
            $customer = $this->customerSession->getCustomer();
            $instantPurchaseOption = $this->instantPurchaseOptionLoadingFactory->create(
                $customer->getId(),
                $paymentTokenPublicHash,
                $shippingAddressId,
                $billingAddressId,
                $carrierCode,
                $shippingMethodCode
            );
            $store = $this->storeManager->getStore();
            $product = $this->productRepository->getById(
                $productId,
                false,
                $store->getId(),
                false
            );
            $orderId = $this->placeOrder->placeOrder(
                $store,
                $customer,
                $instantPurchaseOption,
                $product,
                $productRequest
            );
        } catch (NoSuchEntityException $e) {
            return $this->createResponse($this->createGenericErrorMessage(), false);
        } catch (Exception $e) {
            return $this->createResponse(
                $e instanceof LocalizedException ? $e->getMessage() : $this->createGenericErrorMessage(),
                false
            );
        }
        $order = $this->orderRepository->get($orderId);
        $message = __('Your order number is: %1.', $order->getIncrementId());
        $threeDSecureChallengeParams = $this->checkoutSession->get3Ds2Params();
        $this->messageManager->getMessages(true);
        if ($threeDSecureChallengeParams || ($redirectData = $this->checkoutSession->get3DSecureParams())) {
            $this->checkoutSession->setInstantPurchaseRedirectUrl($this->redirect->getRefererUrl());
            $this->checkoutSession->setInstantPurchaseMessage($message);
            $message = __('');
            return $this->createResponse($message, true);
        } else {
            return $this->createResponse($message, true);
        }
    }

    /**
     * Creates error message without exposing error details
     *
     * @return string
     */
    private function createGenericErrorMessage(): string
    {
        return (string)__('Something went wrong while processing your order. Please try again later.');
    }

    /**
     * Checks if all parameters that should be handled are passed.
     *
     * @param RequestInterface $request
     * @return bool
     */
    private function doesRequestContainAllKnowParams(RequestInterface $request): bool
    {
        foreach (self::$knownRequestParams as $knownRequestParam) {
            if ($request->getParam($knownRequestParam) === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Filters out parameters that handled by controller.
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getRequestUnknownParams(RequestInterface $request): array
    {
        $requestParams = $request->getParams();
        $unknownParams = [];
        foreach ($requestParams as $param => $value) {
            if (!isset(self::$knownRequestParams[$param])) {
                $unknownParams[$param] = $value;
            }
        }
        return $unknownParams;
    }

    /**
     * Creates response with a operation status message.
     *
     * @param string $message
     * @param bool $successMessage
     * @return JsonResult
     */
    private function createResponse(string $message, bool $successMessage): JsonResult
    {
        /** @var JsonResult $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData([
            'response' => $message
        ]);
        if ($successMessage) {
            $this->messageManager->addSuccessMessage($message);
        } else {
            $this->messageManager->addErrorMessage($message);
        }

        return $result;
    }
}
