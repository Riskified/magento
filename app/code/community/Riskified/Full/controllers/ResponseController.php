<?php

class Riskified_Full_ResponseController extends Mage_Core_Controller_Front_Action
{
    public function getresponseAction()
    {
        $request = $this->getRequest();
        $helper = Mage::helper('full/order');
        $notification = $helper->parseRequest($request);

	    Mage::helper('full/log')->log("Notification received: " , serialize($notification));

        $order = Mage::getModel('sales/order')->load($notification->id);

	    // Make sure we have a valid order to work with
	    if (!$order || !$order->getId()) {
		    Mage::helper('full/log')->log("ERROR: Unable to load order (" . $notification->id . ")");
		    return;
	    }
        $order->riskifiedInSave = true;
        $helper->updateOrder($order, $notification->status, $notification->description);
    }
}
    