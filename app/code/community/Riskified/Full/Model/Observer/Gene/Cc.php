<?php
class Riskified_Full_Model_Observer_Gene_Cc {
    public function handleSuccessPayment($event)
    {
        /**
         * @var Braintree\Transaction\CreditCardDetails $result
         * @var Mage_Sales_Model_Order_Payment $payment
        */
        $result = $event->getResult();
        $payment = $event->getPayment();

        $cc = $result->transaction->creditCard;

        if (isset($cc['bin']) && strlen($cc['bin']) > 0) {
            $payment->setAdditionalInformation('bin', $cc['bin']);

            Mage::unregister("cc_bin");
            Mage::register("cc_bin", $cc['bin']);
        }
    }
}