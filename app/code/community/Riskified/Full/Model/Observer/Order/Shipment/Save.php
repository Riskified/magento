<?php

class Riskified_Full_Model_Observer_Order_Shipment_Save
{
    public function handleShipmentSave(
        Varien_Event_Observer $observer
    ) {
        $shipment = $observer->getEvent()->getShipment();

        $order = $shipment->getOrder();
        $helper = Mage::helper('full/order');
        try {
            $helper->postOrder(
                $order,
                Riskified_Full_Helper_Order::ACTION_FULFILL
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('Riskified shipment error save: ' . $e->getMessage());
            Mage::helper('full/log')->logException($e);
        }
    }
}
