<?php

class Riskified_Full_Model_Authorizenet extends Mage_Paygate_Model_Authorizenet
{
    /**
     * it sets card`s data into additional information of payment model
     *
     * @param mage_paygate_model_authorizenet_result $response
     * @param mage_sales_model_order_payment $payment
     * @return varien_object
     */
    protected function _registercard(varien_object $response, mage_sales_model_order_payment $payment)
    {
        Mage::helper('full/log')->log("in inherited _registercard.");
        $card = parent::_registercard($response, $payment);
        $card->setCcAvsResultCode($response->getAvsResultCode());
        $card->setCcResponseCode($response->getCardCodeResponseCode());
        $payment->setCcAvsStatus($response->getAvsResultCode());
        $payment->setCcCidStatus($response->getCardCodeResponseCode());
        return $card;
    }
}
