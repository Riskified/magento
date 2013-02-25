<?php
class Riskified_Full_Block_Full extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getFull()     
     { 
        if (!$this->hasData('full')) {
            $this->setData('full', Mage::registry('full'));
        }
        return $this->getData('full');
        
    }
}