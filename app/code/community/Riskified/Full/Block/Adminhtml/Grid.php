<?php
class Riskified_Full_Block_Adminhtml_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{

	protected function  _prepareLayout()
	{
		$this->setChild('riskified',
				$this->getLayout()->createBlock('adminhtml/widget_button')
				->setData(array(
						'label'     => Mage::helper('full')->__('Riskified'),
						'onclick'   => 'window.open(\'http://app.riskified.com/\')',
						'class' => ''
				))
		);
	
		return parent::_prepareLayout();
	}
	
	public function  getSearchButtonHtml()
	{
		return parent::getSearchButtonHtml() . $this->getChildHtml('riskified');
	}
}