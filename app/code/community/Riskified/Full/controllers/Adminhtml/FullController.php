<?php

class Riskified_Full_Adminhtml_FullController extends Mage_Adminhtml_Controller_action
{

	/*
     *  BGB
     *
    public function showAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('full');
        $this->_addBreadcrumb(Mage::helper('full')->__('Riskified'), Mage::helper('full')->__('Riskified'));
        
        $this->renderLayout();
    }
    
    public function showapiAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('full');
        $this->_addBreadcrumb(Mage::helper('full')->__('RiskifiedApi'), Mage::helper('full')->__('RiskifiedApi'));
        
        $this->renderLayout();
    }

    /****/// \\\***/

	public function riskiAction()
	{
		$id = $this->getRequest()->getParam('order_id');
		try
		{
            $call = Mage::getModel('Riskified_Full_Model_Observer');
		    $call->saveOrderAfter($id);
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Order was submited successfully'));
		}
		catch (Exception $e)
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			Mage::logException($e);
		}
		Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index", array('id'=>$id)));
	}

	public function riskimassAction()
	{
		$order_ids = $this->getRequest()->getParam('order_ids');
		try
		{
            $call = Mage::getModel('Riskified_Full_Model_Observer');
		    $call->saveOrderAfter($order_ids);
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Orders were submited successfully'));
		}
		catch (Exception $e)
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			Mage::logException($e);
		}
		Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index"));

	}
}