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
            'order' => array(
                'id' => (int)$creditmemo->getOrderId(),
                'refunds' => array(
                    array(
                        'refund_id' => $creditmemo->getId(),
                        'amount' => $creditmemo->getGrandTotal(),
                        'refunded_at' => $creditmemo->getCreatedAt(),
                        'currency' => $creditmemo->getOrderCurrencyCode(),
                        'reason' => $reason,
                    ),
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

        $request = Mage::getModel('full/request_order_creditmemo_save');

        try {
            $response = $request->sendRequest($payload);
        } catch (\Exception $e) {
            Mage::helper('full/log')->log(
                'handleCreditmemoSave(): ' . PHP_EOL . $e->getMessage()
            );
        }

        return $this;
    }
}
