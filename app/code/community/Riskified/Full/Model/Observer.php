<?php

class Riskified_Full_Model_Observer{

    public function saveOrderBefore($evt) {
        Mage::log("Entering saveOrderBefore");
        $payment = $evt->getPayment();
        $payment->setAdditionalInformation('riskified_cc_bin', substr($payment->getCcNumber(),0,6));
    }

    public function salesOrderPlaceEnd($evt)
    {
    }

    public function saveOrderAfter($evt) {
        if (is_object($evt)) {
            $submit_now = false;
            $order_ids[] = $evt->getOrder()->getId();
        } else {
            $submit_now = true;
            $order_ids = (is_array($evt)) ? $evt : array($evt);
        }

        foreach ($order_ids as $order_id) {
            $order = Mage::getModel('sales/order')->load($order_id);

            $response = Mage::helper('full/order')->postOrder($order, $submit_now);
            Mage::log("Riskified response, data: :".PHP_EOL.json_encode($response));

            if (isset($response->order)){
                $orderId = $response->order->id;
                $status = $response->order->status;
                $description = $response->order->description;

                if ($orderId && $status){
                    $state = Mage::helper('full')->stateFromStatus($status);
                    $mageStatus = ($state == Mage_Sales_Model_Order::STATE_CANCELED) ? Mage_Sales_Model_Order::STATUS_FRAUD : true;
                    $order->setState($state, $mageStatus, $description);
                    $order->save();
                }
            }
        }
    }

    public function salesOrderCancel($evt) {
        $order = $evt->getOrder();
        Mage::helper('full/order')->postOrder($order);
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

}