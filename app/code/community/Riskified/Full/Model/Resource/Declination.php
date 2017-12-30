<?php
class Riskified_Full_Model_Resource_Declination extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('full/declination_sent', 'entity_id');
    }
}