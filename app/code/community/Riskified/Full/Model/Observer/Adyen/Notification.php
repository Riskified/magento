<?php
class Riskified_Full_Model_Observer_Adyen_Notification {
    /**
     * @param $event
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function handleProcessNotificationBefore($event)
    {
        $order = $event->getOrder();

        if (!Mage::registry('riskified-order')) {
            Mage::register("riskified-order", $order);
        }

        return $this;
    }

    /**
     * @param $event
     * @return $this
     */
    public function handleProcessNotificationAfter($event)
    {
        Mage::unregister("riskified-order");

        return $this;
    }
}