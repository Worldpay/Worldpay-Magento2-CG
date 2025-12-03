<?php

namespace Sapient\Worldpay\Cron;

use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\SenderBuilder;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Psr\Log\LoggerInterface;
use Sapient\Worldpay\Model\Order\Email\Sender\OrderSender;
use Sapient\Worldpay\Model\Order\Service;
use Sapient\Worldpay\Model\ResourceModel\AuthorisedOrderEmail;

class SendAuthorisedOrderEmails
{
    private AuthorisedOrderEmail $authorisedOrderEmail;
    private OrderIdentity $identityContainer;
    private Template $templateContainer;
    private LoggerInterface $logger;
    private Service $orderService;
    private SenderBuilderFactory $senderBuilderFactory;
    private OrderSender $orderSender;

    public function __construct(
        AuthorisedOrderEmail $authorisedOrderEmailFactory,
        OrderIdentity $identityContainer,
        Template $templateContainer,
        LoggerInterface $logger,
        Service $orderService,
        SenderBuilderFactory $senderBuilderFactory,
        OrderSender $orderSender
    ) {
        $this->authorisedOrderEmail = $authorisedOrderEmailFactory;
        $this->identityContainer = $identityContainer;
        $this->templateContainer = $templateContainer;
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->senderBuilderFactory = $senderBuilderFactory;
        $this->orderSender = $orderSender;
    }

    public function execute(): void
    {
        $lastEntityId = 0;

        do {
            $pendingEmails = $this->authorisedOrderEmail->getPendingEmails(fromEntityId: $lastEntityId);

            if (empty($pendingEmails)) {
                break;
            }

            $this->sendEmails($pendingEmails);
            $lastEntityId = end($pendingEmails)['entity_id'] ?? 0;
        } while (count($pendingEmails) > 0);
    }

    public function sendEmails(array $pendingEmails): void
    {
        $idsToDelete = [];

        foreach ($pendingEmails as $email) {
            try {
                $order = $this->orderService->getByIncrementId($email['order_increment_id'])->getOrder();
                $this->identityContainer->setStore($order->getStore());
                if (!$this->identityContainer->isEnabled()) {
                    continue;
                }
                $this->orderSender->prepareTemplateForAuthorisedOrder($order, $email['success_flag']);

                $sender = $this->getSender();
                $sender->send();

                $idsToDelete[] = $email['entity_id'];
            } catch (\Exception $e) {
                $this->authorisedOrderEmail->incrementSendAttemptCount($email['entity_id']);
                $this->logger->error($e->getMessage());
            }
        }

        if ($idsToDelete){
            $this->authorisedOrderEmail->deleteByIds($idsToDelete);
        }
    }

    private function getSender(): SenderBuilder
    {
        return $this->senderBuilderFactory->create(
            [
                'templateContainer' => $this->templateContainer,
                'identityContainer' => $this->identityContainer,
            ]
        );
    }
}
