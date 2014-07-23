<?php

class Riskified_Full_Helper_Order_Status extends Mage_Core_Helper_Abstract
{
	/**
	 * Get Riskified's custom on hold order status code
	 *
	 * @return string
	 */
	public function getOnHoldStatusCode()
	{
		return 'riskified_holded';
	}

	/**
	 * Get Riskified's custom on hold order status label
	 *
	 * @return string
	 */
	public function getOnHoldStatusLabel()
	{
		return 'On Hold (Riskified)';
	}
}