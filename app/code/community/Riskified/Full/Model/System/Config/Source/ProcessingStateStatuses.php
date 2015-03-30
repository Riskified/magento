<?php

class Riskified_Full_Model_System_Config_Source_ProcessingStateStatuses
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr = Mage::getSingleton('sales/order_config')->getStateStatuses(Mage_Sales_Model_Order::STATE_PROCESSING);
        return array_map(function($status) { return array('value' => $status, 'label' => Mage::helper('full')->__($status)); }, $arr);
    }
}