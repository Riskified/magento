<?php

class Riskified_Full_Model_Mysql4_Full extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the full_id refers to the key field in your database table.
        $this->_init('full/full', 'full_id');
    }
}