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
		return 'Under Review (Riskified)';
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
        return 'Transport Error (Riskified)';
    }

    /**
     * Get Riskified's custom "declined" status code
     *
     * @return string
     */
    public function getRiskifiedDeclinedStatusCode()
    {
        return 'riskified_declined';
    }

    /**
     * Get Riskified's custom "declined" status label
     *
     * @return string
     */
    public function getRiskifiedDeclinedStatusLabel()
    {
        return 'Declined (Riskified)';
    }

    /**
     * Get Riskified's custom "declined" status code
     *
     * @return string
     */
    public function getRiskifiedApprovedStatusCode()
    {
        return 'riskified_approved';
    }

    /**
     * Get Riskified's custom "approved" status label
     *
     * @return string
     */
    public function getRiskifiedApprovedStatusLabel()
    {
        return 'Approved (Riskified)';
    }

    /**
     * Get the current approved state from the configuration
     *
     * @return string
     */
    public function getSelectedApprovedState()
    {
        $state = Mage::getStoreConfig('fullsection/full/approved_state');

        if (!in_array($state, array(Mage_Sales_Model_Order::STATE_PROCESSING,Mage_Sales_Model_Order::STATE_HOLDED))) {
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
        }

        return $state;
    }

    /**
     * Get the current declined state from the configuration
     *
     * @return string
     */
    public function getSelectedDeclinedState()
    {
        $state = Mage::getStoreConfig('fullsection/full/declined_state');

        if (!in_array($state, array(Mage_Sales_Model_Order::STATE_CANCELED,Mage_Sales_Model_Order::STATE_HOLDED))) {
            $state = Mage_Sales_Model_Order::STATE_CANCELED;
        }

        return $state;
    }
}