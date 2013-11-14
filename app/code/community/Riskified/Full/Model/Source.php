<?php
class Riskified_Full_Model_Source
{     
    public function toOptionArray()
    {
        return array(
            array('value' => 'http://app.riskified.com/', 'label' => Mage::helper('full')->__('http://app.riskified.com')),
            array('value' => 'http://sandbox.riskified.com/', 'label' => Mage::helper('full')->__('http://sandbox.riskified.com')),
        );
    }
}