<?php

/**
 * Riskified Full order creditmemo save observer.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
class Riskified_Full_Model_Observer_Order_Creditmemo_Save
{
    /**
     * Handle credit memo save.
     *
     * @param Varien_Event_Observer $observer Observer object.
     *
     * @return Riskified_Full_Model_Observer_Order_Creditmemo_Save
     */
    public function handleCreditmemoSave(
        Varien_Event_Observer $observer
    ) {
        $creditmemo = $observer->getEvent()->getCreditmemo();

        $reason = '';
        $commentsCollection = $creditmemo->getCommentsCollection();
        foreach ($commentsCollection as $commentModel) {
            $comment = trim($commentModel->getComment());
            $comment = ucfirst($comment);
            if (substr($comment, -1) !== '.') {
                $comment .= '.';
            }

            $reason .= $comment . ' ';
        }
        $reason = trim($reason);

        $payload = array(
            'id' => (int)$creditmemo->getOrderId(),
            'refunds' => array(
                array(
                    'refund_id' => $creditmemo->getId(),
                    'amount' => $creditmemo->getGrandTotal(),
                    'refunded_at' => Mage::helper('full')->getDateTime(
                        $creditmemo->getCreatedAt()
                    ),
                    'currency' => $creditmemo->getOrderCurrencyCode(),
                    'reason' => $reason,
                ),
            ),
        );

        unset(
            $comment,
            $commentModel,
            $commentsCollection,
            $creditmemo,
            $reason
        );

        $helper = Mage::helper('full/order');
        $helper->postOrder(
            $payload,
            Riskified_Full_Helper_Order::ACTION_REFUND
        );

        return $this;
    }
}
