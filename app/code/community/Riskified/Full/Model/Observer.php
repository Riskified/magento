<?php

class Riskified_Full_Model_Observer{

    public function saveOrderBefore($evt) {
        $payment = $evt->getPayment();
        $cc_bin = substr($payment->getCcNumber(),0,6);
        if ($cc_bin)
            $payment->setAdditionalInformation('riskified_cc_bin', $cc_bin);
    }

    public function salesOrderPaymentPlaceEnd($evt) {
        Mage::log("salesOrderPaymentPlaceEnd");
        $order = $evt->getPayment()->getOrder();
        $this->postOrder($order);
    }

    public function postOrderIds($order_ids) {
        foreach ($order_ids as $order_id) {
            $order = Mage::getModel('sales/order')->load($order_id);
            $this->postOrder($order, true);
        }
    }

    public function salesOrderCancel($evt) {
        $order = $evt->getOrder();
        $this->postOrder($order);
    }

    public function addMassAction($observer) {
        $block = $observer->getEvent()->getBlock();
        if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
            && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('full', array(
                'label' => 'Submit to Riskified',
                'url' => Mage::app()->getStore()->getUrl('full/adminhtml_full/riskimass'),
            ));
        }
    }

    public function blockHtmlBefore($observer) {
        $block = $observer->getEvent()->getBlock();
        if ($block->getType() == 'adminhtml/sales_order_view') {
            $message = Mage::helper('sales')->__('Are you sure you want to submit this order to Riskified?');
            $url = $block->getUrl('full/adminhtml_full/riski');
            $block->addButton('riski_submit', array(
                'label'     => Mage::helper('sales')->__('Submit to Riskified'),
                'onclick'   => "deleteConfirm('$message', '$url')",
            ));
        }
    }

    private function postOrder($order, $submit_now=false) {
        try {
            $helper = Mage::helper('full/order');
            $response = $helper->postOrder($order, $submit_now);
            Mage::log("Riskified response, data: :" . PHP_EOL . json_encode($response));

            if (isset($response->order)) {
                $orderId = $response->order->id;
                $status = $response->order->status;
                $description = $response->order->description;
                if (!$description)
                    $description = "Riskified Status: $status";

                if ($orderId && $status) {
                    $helper->updateOrder($order, $status, $description);
                }
                $origId = $order->getId();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("Order #$origId was successfully updated at Riskified"));
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("Malformed response from Riskified"));
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $e->getMessage());
            Mage::logException($e);
        }
    }

}