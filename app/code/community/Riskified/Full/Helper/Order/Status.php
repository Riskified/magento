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
        $state = Mage::helper('full')->getApprovedState();

        if (!in_array($state, array(Mage_Sales_Model_Order::STATE_PROCESSING,Mage_Sales_Model_Order::STATE_HOLDED))) {
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
        }

        return $state;
    }

    /**
     * Get the current approved status from the configuration
     *
     * @return string
     */
    public function getSelectedApprovedStatus()
    {
        $status = Mage::helper('full')->getApprovedStatus();

        $allowedStatuses = Mage::getSingleton('sales/order_config')->getStateStatuses($this->getSelectedApprovedState());
        if (!in_array($status, array_keys($allowedStatuses))) {
            $status = $this->getRiskifiedApprovedStatusCode();
            Mage::helper('full/log')->log("approved status: ".$status." not found in: ".var_export($allowedStatuses,1));
        }

        return $status;
    }

    /**
     * Get the current declined status from the configuration
     *
     * @return string
     */
    public function getSelectedDeclinedState()
    {
        $state = Mage::helper('full')->getDeclinedState();

        if (!in_array($state, array(Mage_Sales_Model_Order::STATE_CANCELED,Mage_Sales_Model_Order::STATE_HOLDED))) {
            $state = Mage_Sales_Model_Order::STATE_CANCELED;
        }

        return $state;
    }

    /**
     * Get the current declined status from the configuration
     *
     * @return string
     */
    public function getSelectedDeclinedStatus()
    {
        $status = Mage::helper('full')->getDeclinedStatus();

        $allowedStatuses = Mage::getSingleton('sales/order_config')->getStateStatuses($this->getSelectedDeclinedState());
        if (!in_array($status, array_keys($allowedStatuses))) {
            $status = $this->getRiskifiedDeclinedStatusCode();
            Mage::helper('full/log')->log("declined status: ".$status." not found in: ".var_export($allowedStatuses,1));
        }

        return $status;
    }
}