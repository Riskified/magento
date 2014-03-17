<?php
class Riskified_Full_Model_Source
{     
    public function toOptionArray()
    {
        return array(
            array('value' => 'https://app.riskified.com/', 'label' => Mage::helper('full')->__('https://app.riskified.com')),
            array('value' => 'https://sandbox.riskified.com/', 'label' => Mage::helper('full')->__('https://sandbox.riskified.com')),
        );
    }
}