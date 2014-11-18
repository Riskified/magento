<?php

class Riskified_Full_Model_System_Config_Source_DeclinedState
{
	/**
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => Mage_Sales_Model_Order::STATE_CANCELED, 'label' => Mage::helper('full')->__(Mage_Sales_Model_Order::STATE_CANCELED)),
			array('value' => Mage_Sales_Model_Order::STATE_HOLDED, 'label' => Mage::helper('full')->__(Mage_Sales_Model_Order::STATE_HOLDED))
		);
	}
}