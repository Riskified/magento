<?php

class Riskified_Full_Model_Observer{

    public function saveOrderBefore($evt) {
        Mage::log("saveOrderBefore");
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

    public function saveOrderAfter($evt) {
        if (is_object($evt)) {
            $order = $evt->getOrder();
            $this->postOrder($order);
        } else {
            $order_ids = (is_array($evt)) ? $evt : array($evt);
            foreach ($order_ids as $order_id) {
                $order = Mage::getModel('sales/order')->load($order_id);
                $this->postOrder($order, true);
            }
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

    private function postOrder($order, $submit_now=false) {
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
        }
    }

}