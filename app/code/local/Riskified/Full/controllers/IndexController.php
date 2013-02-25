<?php
class Riskified_Full_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/full?id=15 
    	 *  or
    	 * http://site.com/full/id/15 	
    	 */
    	/* 
		$full_id = $this->getRequest()->getParam('id');

  		if($full_id != null && $full_id != '')	{
			$full = Mage::getModel('full/full')->load($full_id)->getData();
		} else {
			$full = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($full == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$fullTable = $resource->getTableName('full');
			
			$select = $read->select()
			   ->from($fullTable,array('full_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$full = $read->fetchRow($select);
		}
		Mage::register('full', $full);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}