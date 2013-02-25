<?php
class Excellence_Riskified_Block_Riskified extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getRiskified()     
     { 
        if (!$this->hasData('riskified')) {
            $this->setData('riskified', Mage::registry('riskified'));
        }
        return $this->getData('riskified');
        
    }
}