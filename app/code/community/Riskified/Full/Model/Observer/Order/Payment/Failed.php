<?php

/**
 * Riskified Full failed order payment observer.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
class Riskified_Full_Model_Observer_Order_Payment_Failed
{
    /**
     * Default failed transaction handler.
     *
     * @param Varien_Event_Observer $observer Observer object.
     *
     * @return Riskified_Full_Model_Observer_Order_Payment_Failed
     */
    public function handleDefaultFailedTransaction(
        Varien_Event_Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();

        $orderPaymentHelper = Mage::helper('full/order_payment');
        $paymentDetails = $orderPaymentHelper->getPaymentDetails($order);

        $paymentDetailsArray = json_decode($paymentDetails->toJson(), true);
        $payload = array(
            'checkout' => array(
                'id' => (int)$order->getQuoteId(),
                'payment_details' => array_merge(
                    $paymentDetailsArray,
                    array(
                        'authorization_error' => array(
                            'error_code' => 'magento_generic_auth_error',
                            'message' => 'General processing error',
                        )
                    )
                ),
            ),
        );

        unset(
            $orderPaymentHelper,
            $paymentDetails,
            $paymentDetailsArray
        );

        $request = Mage::getModel('full/request_order_payment_failed');
        $response = $request->sendRequest($payload);

        return $this;
    }
}
