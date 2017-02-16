<?php

class Riskified_Full_Model_Observer_Order_Shipment_Save
{
    public function handleShipmentSave(
        Varien_Event_Observer $observer
    ) {
        $shipment = $observer->getEvent()->getShipment();

        if (!$shipment->isObjectNew()) {
//            return $this;
        }
        $order = $shipment->getOrder();
        $helper = Mage::helper('full/order');
        $helper->postOrder(
            $order,
            Riskified_Full_Helper_Order::ACTION_FULFILL
        );
    }
}
