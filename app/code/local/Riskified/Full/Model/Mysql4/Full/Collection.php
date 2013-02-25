<?php

class Riskified_Full_Model_Mysql4_Full_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('full/full');
    }
}