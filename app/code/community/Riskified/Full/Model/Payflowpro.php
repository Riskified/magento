<?php
class Riskified_Full_Model_Payflowpro extends Mage_Paypal_Model_Payflowpro {
    public function authorize(Varien_Object $payment, $amount)
    {
        $request = $this->_buildPlaceRequest($payment, $amount);
        $request->setTrxtype(self::TRXTYPE_AUTH_ONLY);
        $this->_setReferenceTransaction($payment, $request);
        $response = $this->_postRequest($request);
        $this->_processErrors($response);
        switch ($response->getResultCode()) {
            case self::RESPONSE_CODE_APPROVED:
                $payment->setCcCidStatus($response->getProccvv2());
                $payment->setCcAvsStatus($response->getProcavs());
                $payment->setTransactionId($response->getPnref())->setIsTransactionClosed(0);
                break;
            case self::RESPONSE_CODE_FRAUDSERVICE_FILTER:
                $payment->setCcCidStatus($response->getProccvv2());
                $payment->setCcAvsStatus($response->getProcavs());

                $payment->setTransactionId($response->getPnref())->setIsTransactionClosed(0);
                $payment->setIsTransactionPending(true);
                $payment->setIsFraudDetected(true);
                break;
        }
        return $this;
    }
}
