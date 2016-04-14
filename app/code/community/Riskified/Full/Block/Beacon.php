<?php

class Riskified_Full_Block_Beacon extends Mage_Core_Block_Template
{
    protected $sessionId, $shopDomain, $extensionVersion, $beaconUrl, $time;

    protected function _construct()
    {
        $this->setTemplate('full/beacon.phtml');
        $helper = Mage::helper('full');
        $this->sessionId = $helper->getSessionId();
        $this->shopDomain = $helper->getShopDomain();
        $this->extensionVersion = $helper->getExtensionVersion();
        $this->beaconUrl = $helper->getConfigBeaconUrl();
    }
}