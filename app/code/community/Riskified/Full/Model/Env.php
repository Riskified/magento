<?php
class Riskified_Full_Model_Env
{     
    public function toOptionArray()
    {
        return array(
            array('value' => 'PROD', 'label' => Mage::helper('full')->__('Production')),
            array('value' => 'SANDBOX', 'label' => Mage::helper('full')->__('Sandbox'))
        );
    }
}