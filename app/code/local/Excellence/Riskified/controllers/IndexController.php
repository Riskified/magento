<?php
class Excellence_Riskified_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/riskified?id=15 
    	 *  or
    	 * http://site.com/riskified/id/15 	
    	 */
    	/* 
		$riskified_id = $this->getRequest()->getParam('id');

  		if($riskified_id != null && $riskified_id != '')	{
			$riskified = Mage::getModel('riskified/riskified')->load($riskified_id)->getData();
		} else {
			$riskified = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($riskified == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$riskifiedTable = $resource->getTableName('riskified');
			
			$select = $read->select()
			   ->from($riskifiedTable,array('riskified_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$riskified = $read->fetchRow($select);
		}
		Mage::register('riskified', $riskified);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}