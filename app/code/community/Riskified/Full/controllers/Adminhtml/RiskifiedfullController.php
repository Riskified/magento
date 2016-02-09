<?php

class Riskified_Full_Adminhtml_RiskifiedfullController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order');
    }

    public function sendAction()
    {
        $id = $this->getRequest()->getParam('order_id');
        $call = Mage::getModel('full/observer');
        $call->postOrderIds(array($id));

        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $id)));
    }

    public function massSendAction()
    {
        $order_ids = $this->getRequest()->getParam('order_ids');
        $call = Mage::getModel('full/observer');
        $call->postOrderIds($order_ids);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index"));
    }
}