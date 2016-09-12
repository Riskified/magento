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
            'id' => (int)$quote->getId(),
        );

        $helper = Mage::helper('full/order');
        $helper->postOrder(
            $payload,
            Riskified_Full_Helper_Order::ACTION_CHECKOUT_CREATE
        );

        return $this;
    }
}
