<?php

class Excellence_Riskified_Block_Adminhtml_Riskified_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('riskified_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('riskified')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('riskified')->__('Item Information'),
          'title'     => Mage::helper('riskified')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('riskified/adminhtml_riskified_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}