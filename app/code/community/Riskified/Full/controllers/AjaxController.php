<?php

class Riskified_Full_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function checkoutDeniedAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            try {
                $quoteId = $quote->getId();
                $quote->setQuoteId($quoteId);

                $helper = Mage::helper('full/order');
                $helper->postOrder(
                    $quote,
                    Riskified_Full_Helper_Order::ACTION_CHECKOUT_DENIED
                );
            } catch (Exception $e) {
                $logger = Mage::helper('full/log');
                $logger->log("ERROR: while processing checkout denied with " . $quote->getId());
                $logger->logException($e);
            }
        }
    }

    public function isEligibleAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            try {
                $quoteId = $quote->getId();
                $quote->setQuoteId($quoteId);

                /** @var Riskified_Full_Helper_Deco $helper */
                $helper = Mage::helper('full/deco');
                $response = $helper->post(
                    $quote,
                    Riskified_Full_Helper_Deco::ACTION_ELIGIBLE
                );
                $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode(array(
                        'success' => true,
                        'status' => $response->order->status,
                        'message' => $response->order->description
                    ))
                );
            } catch (Exception $e) {
                $logger = Mage::helper('full/log');
                $logger->logException($e);

                $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode(array(
                        'success' => false,
                        'status' => 'not_eligible',
                        'message' => $e->getMessage()
                    ))
                );
            }
        }
    }

    public function optInAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            try {
                $quoteId = $quote->getId();
                $quote->setQuoteId($quoteId);

                /** @var Riskified_Full_Helper_Deco $helper */
                $helper = Mage::helper('full/deco');
                $response = $helper->post(
                    $quote,
                    Riskified_Full_Helper_Deco::ACTION_OPT_IN
                );

                $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode(array(
                        'success' => true,
                        'status' => $response->order->status,
                        'message' => $response->order->description
                    ))
                );
            } catch (Exception $e) {
                $logger = Mage::helper('full/log');
                $logger->logException($e);

                $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode(array(
                        'success' => false,
                        'status' => 'not_opt',
                        'message' => $e->getMessage()
                    ))
                );
            }
        }
    }

    /**
     * Return customer quote
     *
     * @param string $paymentMethod
     *
     * @return void
     */
    protected function processOrder($paymentMethod)
    {
        switch ($paymentMethod) {
            case 'authorizenet_directpost':
                $incrementId = $this->_getDirectPostSession()->getLastOrderIncrementId();
                if ($incrementId) {
                    /* @var $order Mage_Sales_Model_Order */
                    $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
                    if ($order->getId()) {
                        $order->getPayment()->setMethod('deco')->save();
                        $order->save();
                        $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                        if (Mage::helper('full')->getConfigStatusControlActive()) {
                            $status = Mage::helper('full/order_status')->getOnHoldStatusCode();
                        } else {
                            $status = Mage_Sales_Model_Order::STATE_PROCESSING;
                        }

                        $order->setState($state)->setStatus($status);
                        $order->addStatusHistoryComment('Order submitted to Riskified', false);
                        $order->save();

                        $quote = Mage::getModel('sales/quote')
                            ->load($order->getQuoteId());
                        if ($quote->getId()) {
                            $quote->setIsActive(0)
                                ->save();
                        }
                    }
                }
        }
    }

    /**
     * Get session model

     * @return Mage_Authorizenet_Model_Directpost_Session
     */
    protected function _getDirectPostSession()
    {
        return Mage::getSingleton('authorizenet/directpost_session');
    }
}
