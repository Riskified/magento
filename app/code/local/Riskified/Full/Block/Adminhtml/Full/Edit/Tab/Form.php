<?php

class Riskified_Full_Block_Adminhtml_Full_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('full_form', array('legend'=>Mage::helper('full')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('full')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('full')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('full')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('full')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('full')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('full')->__('Content'),
          'title'     => Mage::helper('full')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getFullData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getFullData());
          Mage::getSingleton('adminhtml/session')->setFullData(null);
      } elseif ( Mage::registry('full_data') ) {
          $form->setValues(Mage::registry('full_data')->getData());
      }
      return parent::_prepareForm();
  }
}