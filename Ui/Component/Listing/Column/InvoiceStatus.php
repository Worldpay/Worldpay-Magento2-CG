<?php

namespace Sapient\Worldpay\Ui\Component\Listing\Column;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of InvoiceStatus
 */
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use Sapient\Worldpay\Model\Worldpayment;
use Sapient\Worldpay\Helper\Data;

class InvoiceStatus extends Column
{
    /**
     * Worldpay payments
     *
     * @var \Sapient\Worldpay\Model\Payment\WorldPayPayment
     */
    protected $_worldpaypayment;
    /**
     * Search criteria builder API
     *
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteria;
    /**
     * Order repository interface
     *
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     *
     * @var Data
     */
    protected $helper;
    
    /**
     * Invoice Status constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $criteria
     * @param Data $helper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->helper = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if(!in_array($item['payment_method'],$this->helper->getWpPaymentMethods())){
                    continue;
                }
                $order = $this->_orderRepository->get($item['entity_id']);
                if ($order->hasInvoices()) {
                    if ($order->getBaseTotalDue() == 0) {
                        $invoiceStatus = 'Captured';
                    } else {
                        $invoiceStatus = 'Partial Capture';
                    }
                } else {
                    $invoiceStatus = 'Not Captured';
                }
                $item[$this->getData('name')] = strtoupper($invoiceStatus);
            }
        }

        return $dataSource;
    }
    
    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        if ($this->helper->isWorldPayEnable()) {
            $this->_data['config']['componentDisabled'] = false;
        } else {
            $this->_data['config']['componentDisabled'] = true;
        }
    }
}
