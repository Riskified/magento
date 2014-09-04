<?php

class Riskified_Full_Helper_Order_Status extends Mage_Core_Helper_Abstract
{
	/**
	 * Get Riskified's custom "on hold for review" status code
	 *
	 * @return string
	 */
	public function getOnHoldStatusCode()
	{
		return 'riskified_holded';
	}

	/**
	 * Get Riskified's custom "on hold for review" status label
	 *
	 * @return string
	 */
	public function getOnHoldStatusLabel()
	{
		return 'On Hold (Riskified)';
	}

    /**
     * Get Riskified's custom "on hold due to transport error" status code
     *
     * @return string
     */
    public function getTransportErrorStatusCode()
    {
        return 'riskified_trans_error';
    }

    /**
     * Get Riskified's custom "on hold due to transport error" status label
     *
     * @return string
     */
    public function getTransportErrorStatusLabel()
    {
        return 'On Hold (Riskified Transport Error)';
    }
}