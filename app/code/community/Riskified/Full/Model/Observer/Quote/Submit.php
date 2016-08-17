<?php

/**
 * Riskified Full quote submit observer.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
class Riskified_Full_Model_Observer_Quote_Submit
{
    /**
     * Default failed transaction handler.
     *
     * @param Varien_Event_Observer $observer Observer object.
     *
     * @return Riskified_Full_Model_Observer_Quote_Submit
     */
    public function handleQuoteSubmit(
        Varien_Event_Observer $observer
    ) {
        $quote = $observer->getEvent()->getQuote();

        $payload = array(
            'checkout' => array(
                'id' => (int)$quote->getId(),
            ),
        );

        $request = Mage::getModel('full/request_quote_submit');
        $response = $request->sendRequest($payload);

        return $this;
    }
}
