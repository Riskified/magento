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

        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$id)));
    }

    public function massSendAction()
    {
        $order_ids = $this->getRequest()->getParam('order_ids');
        $call = Mage::getModel('full/observer');
        $call->postOrderIds($order_ids);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index"));
    }

    public function sendHistoricalOrdersAction() {
        ignore_user_abort(true);
        set_time_limit(0);

        $batch_size = 10;
        $page = intval(1);
        if (!$page) {
            $page = 1;
        }
        $orders = Mage::getModel('sales/order')->getCollection();

        $total_count = $orders->getSize();

        $orders_collection = Mage::getModel('sales/order')
            ->getCollection()
            ->setPageSize($batch_size)
            ->setCurPage($page);

        $total_uploaded = 0;

        while ($total_uploaded < $total_count) {
            try {
                Mage::helper('full/order')->postHistoricalOrders($orders_collection);
                $total_uploaded += $orders_collection->count();
                $page++;
                $orders_collection = Mage::getModel('sales/order')
                    ->getCollection()
                    ->setPageSize($batch_size)
                    ->setCurPage($page);
                Mage::log($total_uploaded, null, 'riskified_full.log');
            } catch (Exception $e) {
                Mage::logException($e);
                exit(1);
            }
        }
    }
}