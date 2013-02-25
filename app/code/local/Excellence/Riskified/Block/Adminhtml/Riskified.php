<?php
class Excellence_Riskified_Block_Adminhtml_Riskified extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_riskified';
    $this->_blockGroup = 'riskified';
    $this->_headerText = Mage::helper('riskified')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('riskified')->__('Add Item');
    parent::__construct();
  }
}