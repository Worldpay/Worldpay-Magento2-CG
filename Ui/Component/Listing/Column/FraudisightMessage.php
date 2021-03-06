<?php

namespace Sapient\Worldpay\Ui\Component\Listing\Column;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FraudisightMessage
 *
 * @author aatrai
 */
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use Sapient\Worldpay\Model\Worldpayment;
use Sapient\Worldpay\Helper\Data;

class FraudisightMessage extends Column
{
    protected $_worldpaypayment;
    protected $_searchCriteria;
    protected $_orderRepository;
    
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        Worldpayment $_worldpaypayment,
        SearchCriteriaBuilder $criteria,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        $this->_worldpaypayment = $_worldpaypayment;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->helper = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                $worldpaypayment=$this->_worldpaypayment->loadByPaymentId($item["increment_id"]);
                $fraudsightMessage = $worldpaypayment->getFraudsightMessage();
                if (strtolower($fraudsightMessage) === 'review') {
                    $fraudsightMessage=  '<font color="red">'.$fraudsightMessage.'</font>';
                }
                $item[$this->getData('name')] = strtoupper($fraudsightMessage);
            }
        }

        return $dataSource;
    }
    
    /**
     * Prepare component configuration
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
