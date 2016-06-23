<?php

class Riskified_Full_Model_Sent extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('full/sent');
    }

    protected function _beforeSave() {
        parent::_beforeSave();

        if($this->isObjectNew()) {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        }
    }
}