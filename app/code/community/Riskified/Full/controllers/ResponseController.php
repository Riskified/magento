<?php
class Riskified_Full_ResponseController extends Mage_Core_Controller_Front_Action
{
    public function getresponseAction()
    {
        $request = $this->getRequest();
        $helper = Mage::helper('full/order');
        $notification = $helper->parseRequest($request);
        Mage::log("notification " , serialize($notification));

        $order = Mage::getModel('sales/order')->load($notification->id);
        $helper->updateOrder($order, $notification->status, $notification->description);
    }
}
    