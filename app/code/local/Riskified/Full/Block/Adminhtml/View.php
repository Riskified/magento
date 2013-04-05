<?php
class Riskified_Full_Block_Adminhtml_View extends Mage_Adminhtml_Block_Sales_Order_View
{
	public function __construct()
	{
		parent::__construct();
		$message = Mage::helper('sales')->__('Are you sure you want to submit this order to Riskified ?');
		$this->_addButton('riski_submit', array(
				'label'     => Mage::helper('sales')->__('Submit to Riskified'),
				'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getRiskiUrl() . '\')',
		));
	}
	
	public function getRiskiUrl()
	{
		return $this->getUrl('full/adminhtml_full/riski');
	}
}
?>