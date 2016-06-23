<?php

class Riskified_Full_Model_Resource_Sent extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('full/historical_order_sent', 'id');
    }
}