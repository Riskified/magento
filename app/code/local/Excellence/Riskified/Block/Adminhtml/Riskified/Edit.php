<?php

class Excellence_Riskified_Block_Adminhtml_Riskified_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'riskified';
        $this->_controller = 'adminhtml_riskified';
        
        $this->_updateButton('save', 'label', Mage::helper('riskified')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('riskified')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('riskified_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'riskified_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'riskified_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('riskified_data') && Mage::registry('riskified_data')->getId() ) {
            return Mage::helper('riskified')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('riskified_data')->getTitle()));
        } else {
            return Mage::helper('riskified')->__('Add Item');
        }
    }
}