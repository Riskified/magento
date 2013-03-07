<?php

class Riskified_Full_Adminhtml_FullController extends Mage_Adminhtml_Controller_action
{


	
	/*****mycode******/
	Private function fireCurl($ids)
	{
		$end='';
		if(is_array($ids))
		{
			foreach ($ids as $id)
			{
				$end = $end.'&ids[]='.$id;
			}
		}
		else
		{
			$end = '&id='.$ids;
		}
		
		$domain = Mage::getStoreConfig('fullsection/full/domain',Mage::app()->getStore());
		$link = "http://public-beta.herokuapp.com/shopify_links/submit_order?shop=".$domain.$end;
		$ch = curl_init($link);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X_RISKIFIED_SHOP_DOMAIN:'.$domain,
			'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',
			'Accept-Encoding: gzip,deflate,sdch',
			'Accept-Language: en-US,en;q=0.8'
				)
		);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);
		return $result;
	}
	
	
	public function riskiAction()
	{
		$id = $this->getRequest()->getParam('order_id');
		try 
		{
			$result = $this->fireCurl($id);
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Order was submited successfully'));
		} 
		catch (Exception $e) 
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			Mage::logException($e);
		}
		$this->_redirectReferer();
	}
	
	public function riskimassAction()
	{
		$order_ids = $this->getRequest()->getParam('order_ids');
		try 
		{
			$result = $this->fireCurl($order_ids);
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Orders were submited successfully'));
		}
		catch (Exception $e) 
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			Mage::logException($e);
		}
		$this->_redirectReferer();
	}
	
	/*****mycode******/
 
	
    
}