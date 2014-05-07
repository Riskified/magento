<?php
class Riskified_Full_ResponseController extends Mage_Core_Controller_Front_Action
{
    public function getresponseAction()
    {
        $orderId = $_REQUEST['id'];
        $status = $_REQUEST['status'];
        $description = $_REQUEST['description'];
        Mage::log("Processing notification for id : $orderId, status : $status");
        if (empty($orderId) && empty($status)) {
          Mage::app()->getResponse()->setRedirect(Mage::getBaseUrl());
          Mage::app()->getResponse()->sendResponse();
          exit;
        }
        
        //generating local hash
        $data['status'] = $status;
        $data_string = 'id='.$orderId.'&status='.$status;
        $s_key = Mage::helper('full')->getAuthToken();
        $localHash = hash_hmac('sha256', $data_string, $s_key);
            
        //generating hash 
        $headers = getallheaders();
        $riskiHash = $headers['X-Riskified-Hmac-Sha256'];
        
        if ($localHash != $riskiHash) {
          Mage::log("Hashes mismatch localHash : $localHash, riskiHash : $riskiHash");
          Mage::app()->getResponse()->setRedirect(Mage::getBaseUrl());
          Mage::app()->getResponse()->sendResponse();
          exit;
        }

        $status_control_active = Mage::helper('full')->getConfigStatusControlActive();
        if ($status_control_active){
            $order = Mage::getModel('sales/order');
            Mage::helper('full/order')->updateOrder($order, $status, $description);
        }else{
          Mage::log("Ignoring notification status_control_active : $status_control_active");
        }
    }
}
    