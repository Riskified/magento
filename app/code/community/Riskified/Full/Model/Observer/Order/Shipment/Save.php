<?php

class Riskified_Full_Model_Observer_Order_Shipment_Save
{
    const REGISTRY_INDEX_NAME = 'riskified-processed-shipments';

    public function handleShipmentSave(
        Varien_Event_Observer $observer
    ) {
        $shipment = $observer->getEvent()->getShipment();

        if (!$this->validate($shipment->getId())) {
            Mage::helper('full/log')->log('Shipment was already sent to the API in this application call. Stopping process.');
            return $this;
        }

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

    /**
     * Method validates if the shipment can be processed.
     * It prevents to send multiple fulfill call during shipment save.
     *
     * @param int $shipmentId Shipment ID created by magento backend.
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function validate($shipmentId)
    {
        $registeredShipments = Mage::registry(self::REGISTRY_INDEX_NAME);

        if (!is_array($registeredShipments)) {
            $registeredShipments = array();
            $registeredShipments[] = $shipmentId;

            Mage::unregister(self::REGISTRY_INDEX_NAME);
            Mage::register(self::REGISTRY_INDEX_NAME, $registeredShipments);

            return true;
        }

        if (!in_array($shipmentId, $registeredShipments)) {
            $registeredShipments[] = $shipmentId;
            Mage::unregister(self::REGISTRY_INDEX_NAME);
            Mage::register(self::REGISTRY_INDEX_NAME, $registeredShipments);

            return true;
        }

        return false;
    }
}
