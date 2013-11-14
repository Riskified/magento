<?php

class Riskified_Full_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfigUrl()
    {
        return Mage::getStoreConfig('fullsection/full/url');
    }
}