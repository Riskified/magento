<?php

class Riskified_Full_Block_Adminhtml_Full_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'full';
        $this->_controller = 'adminhtml_full';
        
        $this->_updateButton('save', 'label', Mage::helper('full')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('full')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('full_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'full_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'full_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('full_data') && Mage::registry('full_data')->getId() ) {
            return Mage::helper('full')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('full_data')->getTitle()));
        } else {
            return Mage::helper('full')->__('Add Item');
        }
    }
}