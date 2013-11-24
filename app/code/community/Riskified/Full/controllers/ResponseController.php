<?php
class Riskified_Full_ResponseController extends Mage_Core_Controller_Front_Action
{
    public function getresponseAction()
    {
        $orderId = $_REQUEST['id'];
        $status = $_REQUEST['status'];
        
        if(empty($orderId) && empty($status))
            $this->_redirect();
        
        //generating local hash
        $data['status'] = $_REQUEST['status'];
        $data_string = 'id='.$orderId.'&status='.$status;
        $s_key = Mage::getStoreConfig('fullsection/full/key',Mage::app()->getStore());
        $localHash = hash_hmac('sha256', $data_string, $s_key);
            
        //generating hash 
        $headers = getallheaders();
        $riskiHash = $headers['X-Riskified-Hmac-Sha256'];
        
        if($localHash != $riskiHash)
            $this->_redirect();
        
           
        $observer = Mage::getModel('full/observer');
        $riskified_result = $observer->mapStatus($status);
        $order = Mage::getModel('sales/order');
        $order_model = $order->load($orderId);
        $order_model->setState($riskified_result["state"],$riskified_result["mage_status"], $riskified_result["comment"]);
        $order_model->save();
    }
}
    