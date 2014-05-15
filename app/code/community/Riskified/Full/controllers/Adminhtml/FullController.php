<?php

class Riskified_Full_Adminhtml_FullController extends Mage_Adminhtml_Controller_action
{

    public function riskiAction()
    {
        $id = $this->getRequest()->getParam('order_id');
        $call = Mage::getModel('Riskified_Full_Model_Observer');
        $call->postOrderIds(array($id));
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        Mage::logException($e);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index", array('id'=>$id)));
    }

    public function riskimassAction()
    {
        $order_ids = $this->getRequest()->getParam('order_ids');
        $call = Mage::getModel('Riskified_Full_Model_Observer');
        $call->postOrderIds($order_ids);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index"));
    }
}