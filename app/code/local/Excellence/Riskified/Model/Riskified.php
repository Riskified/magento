<?php

class Excellence_Riskified_Model_Riskified extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('riskified/riskified');
    }
}