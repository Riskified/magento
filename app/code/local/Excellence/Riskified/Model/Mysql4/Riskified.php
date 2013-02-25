<?php

class Excellence_Riskified_Model_Mysql4_Riskified extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the riskified_id refers to the key field in your database table.
        $this->_init('riskified/riskified', 'riskified_id');
    }
}