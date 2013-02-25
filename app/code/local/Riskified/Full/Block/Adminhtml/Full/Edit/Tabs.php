<?php

class Riskified_Full_Block_Adminhtml_Full_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('full_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('full')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('full')->__('Item Information'),
          'title'     => Mage::helper('full')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('full/adminhtml_full_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}