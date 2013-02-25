<?php

class Riskified_Full_Model_Full extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('full/full');
    }
}