<?php

class Riskified_Full_Block_Checkout_Deco extends Mage_Core_Block_Template
{
    /**
     * @var Riskified_Full_Helper_Data
     */
    public $helper;

    protected function _construct()
    {
        $this->helper = Mage::helper('full');
    }

    public function isEnabled()
    {
        return $this->helper->isModuleEnabled();
    }

    public function isDecoEnabled()
    {
        return $this->helper->isDecoEnabled();
    }

    public function getShopDomain()
    {
        return $this->helper->getShopDomain();
    }

    /**
     * @return string
     */
    public function getButtonColor()
    {
        return $this->helper->getDecoButtonColor();
    }

    /**
     * @return string
     */
    public function getButtonTextColor()
    {
        return $this->helper->getDecoButtonTextColor();
    }

    /**
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->helper->getDecoLogoUrl();
    }

    /**
     * @return string
     */
    public function getCheckoutDeniedUrl()
    {
        return $this->getUrl('full/ajax/checkoutDenied');
    }

    /**
     * @return string
     */
    public function getIsEligibleUrl()
    {
        return $this->getUrl('full/ajax/isEligible');
    }

    /**
     * @return string
     */
    public function getOptInUrl()
    {
        return $this->getUrl('full/ajax/optIn');
    }
}
