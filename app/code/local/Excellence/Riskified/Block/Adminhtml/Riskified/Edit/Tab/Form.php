<?php

class Excellence_Riskified_Block_Adminhtml_Riskified_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('riskified_form', array('legend'=>Mage::helper('riskified')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('riskified')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('riskified')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('riskified')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('riskified')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('riskified')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('riskified')->__('Content'),
          'title'     => Mage::helper('riskified')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getRiskifiedData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getRiskifiedData());
          Mage::getSingleton('adminhtml/session')->setRiskifiedData(null);
      } elseif ( Mage::registry('riskified_data') ) {
          $form->setValues(Mage::registry('riskified_data')->getData());
      }
      return parent::_prepareForm();
  }
}