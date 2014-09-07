<?php

class Riskified_Full_Adminhtml_FullController extends Mage_Adminhtml_Controller_Action
{

    public function riskiAction()
    {
        $id = $this->getRequest()->getParam('order_id');
        $call = Mage::getModel('full/observer');
        $call->postOrderIds(array($id));

        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index", array('id'=>$id)));
    }

    public function riskimassAction()
    {
        $order_ids = $this->getRequest()->getParam('order_ids');
        $call = Mage::getModel('full/observer');
        $call->postOrderIds($order_ids);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index"));
    }
}