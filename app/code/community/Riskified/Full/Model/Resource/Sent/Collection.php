<?php

class Riskified_Full_Model_Resource_Sent_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('full/sent');
    }
}