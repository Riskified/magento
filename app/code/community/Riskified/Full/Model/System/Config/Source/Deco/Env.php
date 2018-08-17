<?php

class Riskified_Full_Model_System_Config_Source_Deco_Env
{
    const SANDBOX = 'sandboxw.decopayments.com';
    const STAGING = 'stagingw.decopayments.com';
    const PRODUCTION = 'w.decopayments.com';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::SANDBOX,
                'label' => Mage::helper('full')->__('Sandbox')
            ),
            array(
                'value' => self::STAGING,
                'label' => Mage::helper('full')->__('Staging')
            ),
            array(
                'value' => self::PRODUCTION,
                'label' => Mage::helper('full')->__('Production')
            )
        );
    }
}