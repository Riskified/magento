<?php

class Riskified_Full_Model_System_Config_Source_CaptureCase
{
	/**
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE, 'label' => Mage::helper('full')->__('Capture Online')),
			array('value' => Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE, 'label' => Mage::helper('full')->__('Capture Offline'))
		);
	}
}