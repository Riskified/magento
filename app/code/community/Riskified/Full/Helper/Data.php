<?php

class Riskified_Full_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfigUrl(){
        return Mage::getStoreConfig('fullsection/full/url');
    }

    public function getConfigStatusControlActive(){
        return Mage::getStoreConfig('fullsection/full/status_control_active');
    }

    public function getConfigBeaconUrl(){
        return Mage::getStoreConfig('fullsection/full/beaconurl');
    }

    public function getShopDomain(){
      return Mage::getStoreConfig('fullsection/full/domain');
    }
}