<?php

class Riskified_Full_Adminhtml_FullController extends Mage_Adminhtml_Controller_Action
{


	
	/*****mycode******/
	Private function getSubmitUrlByIds($ids)
	{
		$call = Mage::getModel('Riskified_Full_Model_Observer');
		$call->saveOrderAfter($ids);
		$end='';
		if(is_array($ids))
		{
			foreach ($ids as $id)
			{
				$end = $end.'&ids[]='.$id;
			}
			$action = "submit_orders";
		}
		else
		{
			$end = '&id='.$ids;
		        $action = "submit_order";	
		}
		
		$domain = Mage::getStoreConfig('fullsection/full/domain',Mage::app()->getStore());
		$link = "http://app.riskified.com/shopify_links/".$action."?shop=".$domain.$end;
		return $link;
	}
	
	
	public function riskiAction()
	{
		$id = $this->getRequest()->getParam('order_id');
		try 
		{
			$url = $this->getSubmitUrlByIds($id);
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Order was submited successfully'));
		} 
		catch (Exception $e) 
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			Mage::logException($e);
		}
		$this->_redirectUrl($url);
	}
	
	public function riskimassAction()
	{
		$order_ids = $this->getRequest()->getParam('order_ids');
		try 
		{
			$url = $this->getSubmitUrlByIds($order_ids);
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Orders were submited successfully'));
		}
		catch (Exception $e) 
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			Mage::logException($e);
		}
		$this->_redirectUrl($url);
	}
	
	/*****mycode******/
 
	
    
}
