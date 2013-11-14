<?php
class Riskified_Full_ResponseController extends Mage_Core_Controller_Front_Action
{
    public function getresponseAction()
    {
        $response = $_REQUEST;    
        //Mage::log($response, null, 'riskified_response.log');
        
        $orderId = $_REQUEST['id'];
        $status = $_REQUEST['status'];
        if($orderId)
        {
            $order = Mage::getModel('sales/order')
                     ->load($orderId);
            
            if($status)
            {
                switch ($status) {
                    case 'approved':
                        //change order status to 'Processing'
                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
                        break;
                        
                    case 'declined':
                        // change order status to 'On Hold'
                        $comment = 'Verified and declined by Riskified';
                        $isCustomerNotified = false;
                        $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, true, $comment)->save();
                        break;
                        
                    default:
                        // change order status to 'Pending'
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
                        break;
                }
                /* available order statuses
                    
                    $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save(); //completed
                    $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save(); //pending
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true)->save(); //panding paypall
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save(); //processing
                    $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save(); //completed
                    $order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true)->save();//closed
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();//canceled
                    $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, true)->save();//holded
                    
                    //Cancel the order
                    if($order->canCancel()) {
                        $order->cancel()->save();
                    }
                    //Hold an order
                    if($order->canHold()) {
                        $order->hold()->save();
                    }
                   
                    //Unhold an order
                    if($order->canUnhold()) {
                        $order->unhold()->save();
                    }
                 
                 */   
            }
            
        }
    }
}
    