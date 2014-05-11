<?php
class Riskified_Full_ResponseController extends Mage_Core_Controller_Front_Action
{
    public function getresponseAction()
    {
        $request = $this->getRequest();
        $orderId = $request->get('id');
        $status = $request->get('status');
        $description = $request->get('description');
        Mage::log("Processing notification for id : $orderId, status : $status, description: $description");
        if (empty($orderId) && empty($status)) {
          Mage::app()->getResponse()->setRedirect(Mage::getBaseUrl());
          Mage::app()->getResponse()->sendResponse();
          exit;
        }
        
        //generating local hash
        $raw_body = $request->getRawBody();
        $s_key = Mage::helper('full')->getAuthToken();
        $localHash = hash_hmac('sha256', $raw_body, $s_key);

        //generating hash 
        $riskiHash = $request->getHeader('X-RISKIFIED-HMAC-SHA256');

        if ($localHash != $riskiHash) {
          Mage::log("Hashes mismatch localHash : $localHash, riskiHash : $riskiHash");
          Mage::app()->getResponse()->setRedirect(Mage::getBaseUrl());
          Mage::app()->getResponse()->sendResponse();
          exit;
        }

        $status_control_active = Mage::helper('full')->getConfigStatusControlActive();
        if ($status_control_active){
            $order = Mage::getModel('sales/order')->load($orderId);
            Mage::helper('full/order')->updateOrder($order, $status, $description);
        }else{
          Mage::log("Ignoring notification status_control_active : $status_control_active");
        }
    }
}
    