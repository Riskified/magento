<?php
class Riskified_Full_Block_Adminhtml_Full extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_full';
    $this->_blockGroup = 'full';
    $this->_headerText = Mage::helper('full')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('full')->__('Add Item');
    parent::__construct();
  }
}