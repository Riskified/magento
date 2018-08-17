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
}
