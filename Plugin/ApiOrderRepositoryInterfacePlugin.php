<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\TheFeedbackCompany\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\OrderRepository;

class ApiOrderRepositoryInterfacePlugin
{
    /**
     * @param OrderRepository $subject
     * @param OrderInterface  $order
     *
     * @return OrderInterface
     */
    public function afterGet(OrderRepository $subject, OrderInterface $order): OrderInterface
    {
        $extensionAttributes = $order->getExtensionAttributes();

        /** @noinspection NullPointerExceptionInspection */
        $extensionAttributes->setFeedbackInvitationSend($order->getData('feedback_invitation_send'));

        /** @noinspection NullPointerExceptionInspection */
        $order->setExtensionAttributes($extensionAttributes);

        return $order;
    }

    /**
     * @param OrderRepository            $subject
     * @param OrderSearchResultInterface $orderCollection
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepository $subject,
        OrderSearchResultInterface $orderCollection
    ): OrderSearchResultInterface {
        foreach ($orderCollection->getItems() as $order) {
            $this->afterGet($subject, $order);
        }

        return $orderCollection;
    }
}
