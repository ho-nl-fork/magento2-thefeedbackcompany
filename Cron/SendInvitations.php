<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\TheFeedbackCompany\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magmodules\TheFeedbackCompany\Helper\Invitation;
use Magmodules\TheFeedbackCompany\Model\Api as ApiModel;
use Psr\Log\LoggerInterface;

class SendInvitations
{
    /** @var ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var OrderRepositoryInterface $orderRepository */
    private $orderRepository;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var ApiModel $apiModel */
    private $apiModel;

    /** @var HistoryFactory $historyFactory */
    private $historyFactory;

    /** @var OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository */
    private $orderStatusHistoryRepository;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param ScopeConfigInterface                  $scopeConfig
     * @param OrderRepositoryInterface              $orderRepository
     * @param SearchCriteriaBuilder                 $searchCriteriaBuilder
     * @param ApiModel                              $apiModel
     * @param HistoryFactory                        $historyFactory
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param LoggerInterface                       $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ApiModel $apiModel,
        HistoryFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->apiModel = $apiModel;
        $this->historyFactory = $historyFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $orders = $this->orderRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('feedback_invitation_send', 0)
                ->addFilter('status', $this->scopeConfig->getValue(Invitation::XML_PATH_INVITATION_STATUS))
                ->create()
        );

        foreach ($orders->getItems() as $order) {
            try {
                /** @noinspection PhpParamsInspection */
                $response = $this->apiModel->sendInvitation($order);

                if ($response !== false) {
                    $this->addCommentToHistory(
                        (int) $order->getEntityId(),
                        __('Successfully pushed feedbackcompany invitation.')
                    );

                    $order->setData('feedback_invitation_send', 1);
                    $this->orderRepository->save($order);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @param int    $orderId
     * @param Phrase $comment
     *
     * @throws CouldNotSaveException
     */
    private function addCommentToHistory(int $orderId, Phrase $comment): void
    {
        $history = $this->historyFactory->create();
        $history->setParentId($orderId)->setComment($comment)->setEntityName('order');

        $this->orderStatusHistoryRepository->save($history);
    }
}
