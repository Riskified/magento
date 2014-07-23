<?php

class Riskified_Full_Helper_Order_Invoice extends Mage_Core_Helper_Abstract
{

	/**
	 * Has this user enabled auto-invoicing when orders are approved?
	 *
	 * @return bool
	 */
	public function isAutoInvoiceEnabled()
	{
		return (bool) Mage::getStoreConfig('fullsection/auto_invoice/enabled');
	}

	/**
	 * Get the capture case to be used during auto-invoicing
	 *
	 * @return string
	 */
	public function getCaptureCase()
	{
		$case = Mage::getStoreConfig('fullsection/auto_invoice/capture_case');

		if (!in_array($case, array(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE, Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE))) {
			$case = Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE;
		}

		return $case;
	}
}