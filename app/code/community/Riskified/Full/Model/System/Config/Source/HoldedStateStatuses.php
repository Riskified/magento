<?php

class Riskified_Full_Model_System_Config_Source_HoldedStateStatuses
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr = Mage::getSingleton('sales/order_config')->getStateStatuses(Mage_Sales_Model_Order::STATE_HOLDED);
        return array_map(function($status_code,$status_label) {
            return array('value' => $status_code, 'label' => Mage::helper('full')->__($status_label));
        }, array_keys($arr),$arr);
    }
}