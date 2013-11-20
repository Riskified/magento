<?php
class Riskified_Full_Model_Observer{
    
    Private function fireCurl($data_string,$hash_code){
        $domain = Mage::getStoreConfig('fullsection/full/domain',Mage::app()->getStore());
        $ch = curl_init(Mage::helper('full')->getConfigUrl().'/webhooks/merchant_order_created');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string),
        'X_RISKIFIED_SHOP_DOMAIN:'.$domain,
        'X_RISKIFIED_HMAC_SHA256:'.$hash_code)
        );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // echo "from here:<pre>";
        // print_r($data_string);echo"teeeeeeeeeeeeeeeeest<br>";
        // echo "</pre>";
        // print_r(strlen($data_string));echo"<br>";
        // print_r($domain);echo"<br>";
        // print_r($hash_code);echo"<br>";
        // die; 
        $result = curl_exec($ch);
        
        //change order status if no error message
        $orderId = $status = false;
        $decodedResponse = json_decode($result);
        
        if(isset($decodedResponse->order))
        {
            $orderId = $decodedResponse->order->id;
            $status = $decodedResponse->order->status;
             
            if($orderId && $status && $status != 'captured')
            {
                 $mapresponse = $this->mapStatus($orderId,$status);
            }
        }
       
        return $result;
    }
    
    /*
     * BGB
     */
    private function mapStatus($orderId, $status)
    {
        
        if(empty($orderId) && empty($status))
            $this->_redirect();
        
        if(!empty($orderId))
        {
            $orders = Mage::getModel('sales/order')
                     ->load($orderId);
            
            switch ($status) {
                case 'approved':
                    //change order status to 'Processing'
                    $orders->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
                    break;
                    
                case 'declined':
                    // change order status to 'On Hold'
                    $comment = 'Verified and declined by Riskified';
                    $isCustomerNotified = false;
                    $orders->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, $comment)->save();
                    break;
                    
                case 'submited':
                    // change order status to 'Pending'
                    $orders->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
                    break;    
                
            }
            
        }
    }
    /* *** /// end BGB \\\ *** */
    

    public function saveOrderBefore($evt)
    {
        Mage::log("Entering saveOrderBefore");
        $payment = $evt->getPayment();
        $payment->setAdditionalInformation('riskified_cc_bin', substr($payment->getCcNumber(),0,6));
        Mage::log("Exiting saveOrderBefore");
    }

    public function salesOrderPlaceEnd($evt)
    {
    }

    public function saveOrderAfter($evt)
    {
        if(is_object($evt)){
            $order = $evt->getOrder();
            $order_ids[] = $order->getId();
        }elseif (is_array($evt)){
            $order_ids = $evt;
        }else{
            $order_ids[] = $evt;
        }
        foreach ($order_ids as $order_id) {
            Mage::log("Entering saveOrderAfter");
            $order = Mage::getModel('sales/order');
            $order_model = $order->load($order_id);
            $billing_address = $order_model->getBillingAddress();
            $shipping_address = $order_model->getShippingAddress();
            $customer_id = $order_model->getCustomerId();
            $customer_details = Mage::getModel('customer/customer')->load($customer_id);
            $payment_details = $order_model->getPayment();
            $add = $billing_address->getStreet();
            $sadd = $shipping_address->getStreet();
            
            // gathering data
            $data = array();
            $data['id']             = $order_model->getId();
            $data['name']           = $order_model->getIncrementId();
            $data['email']          = $order_model->getCustomerEmail();
            $data['total_spent']    = $order_model->getGrandTotal();
            $data['created_at']     = $order_model->getCreatedAt();
            $data['updated_at']     = $order_model->getUpdatedAt();
            $data['gateway']        = $payment_details->getMethod();
            $data['browser_ip']     = $order_model->getRemoteIp();
            $data['buyer_accepts_marketing']    =NULL;
            $data['cancel_reason']  =NULL;
            $data['cancelled_at']   =NULL;
            $data['cart_token']     =NULL;
            $data['closed_at']      =NULL;
            $data['currency']       =$order_model->getBaseCurrencyCode();
            $data['financial_status']=NULL;
            $data['fulfillment_status'] =NULL;
            $data['landing_site']   ="/";
            $data['note']           = $order_model->getCustomerNote();
            $data['number']         =NULL;
            $data['reference']      =NULL;
            $data['referring_site'] =NULL;
            $data['source']         =NULL;
            $data['subtotal_price'] = $order_model->getBaseSubtotalInclTax();
            $data['taxes_included'] =TRUE;
            $data['token']          =NULL;
            $data['total_discounts']= $order_model->getDiscountAmount();
            $data['total_line_items_price'] = $order_model->getGrandTotal();
            $data['total_price']    =$order_model->getGrandTotal();
            $data['total_price_usd']=$order_model->getGrandTotal();
            $data['total_tax']      =$order_model->getBaseTaxAmount();
            $data['total_weight']   = $order_model->getWeight();
            $data['user_id']        =$order_model->getCustomerId();
            $data['landing_site_ref']=NULL;
            $data['order_number']   =$order_model->getId();
            $data['discount_codes'] =$order_model->getDiscountDescription();
            $data['note_attributes'] = NULL;
            $data['processing_method'] = NULL;
            $data['checkout_id']    =NULL;
            
            //forlast products
            foreach ($order_model->getItemsCollection() as $key => $val)
            {
                $data['line_items'][]['fulfillment_service']    =NULL;
                $data['line_items'][]['fulfillment_status'] =NULL;
                $data['line_items'][]['grams']  = $val->getWeight();
                $data['line_items'][]['id'] = $val->getItemId();
                $data['line_items'][]['price']  = $val->getPrice();
                $data['line_items'][]['product_id'] = $val->getItemId();
                $data['line_items'][]['quantity']   = $val->getQtyOrdered();
                $data['line_items'][]['requires_shipping']  =NULL;
                $data['line_items'][]['sku']    = $val->getSku();
                $data['line_items'][]['title']  = $val->getName();
                $data['line_items'][]['variant_id'] =NULL;
                $data['line_items'][]['variant_title']  =NULL;
                $data['line_items'][]['vendor'] = $order_model->getStoreName();
                $data['line_items'][]['name']   = $val->getName();
                $data['line_items'][]['variant_inventory_management']   =NULL;
                $data['line_items'][]['properties'] =NULL;
            }
                    
            //shipping details
            $data ['shipping_lines'][]['code']  = $order_model->getShippingMethod();
            $data ['shipping_lines'][]['price'] = $order_model->getShippingAmount();
            $data ['shipping_lines'][]['source']    =NULL;
            $data ['shipping_lines'][]['title'] = $order_model->getShippingDescription();
            $data['tax_lines']  =NULL;
            
            // payment details
    
            $bin_number = $payment_details->getAdditionalInformation('riskified_cc_bin');
    
            if($payment_details->getMethod() == 'authorizenet')
            {
                // payment details if authorize
                foreach ($payment_details->getAdditionalInformation() as $additional_data){
                    foreach ($additional_data as $key => $trans_data){
                        $data['payment_details']['credit_card_bin'] = $bin_number;
                        $data['payment_details']['avs_result_code'] = $trans_data['cc_avs_result_code'];
                        $data['payment_details']['cvv_result_code'] = $trans_data['cc_response_code'];
                        #$data['payment_details']['cvv_result_code']    = $payment_details->getAdditionalInformation('paypal_cvv2_match');
                        $data['payment_details']['credit_card_number']  = "XXXX-XXXX-".$trans_data['cc_last4'];
                        $data['payment_details']['credit_card_company']= $trans_data['cc_type'];
                    }
                }
            }elseif ($payment_details->getMethod() == 'paypal_direct'){
                // payment details if paypal
                $data['payment_details']['avs_result_code'] = $payment_details->getAdditionalInformation('paypal_avs_code');
                $data['payment_details']['credit_card_bin'] = $bin_number;
                $data['payment_details']['cvv_result_code'] = $payment_details->getAdditionalInformation('paypal_cvv2_match');
                $data['payment_details']['credit_card_number']  = "XXXX-XXXX-".$payment_details->getCcLast4();
                $data['payment_details']['credit_card_company'] = $payment_details->getCcType();
            }elseif ($payment_details->getMethod() == 'sagepaydirectpro'){
                // payment details if sagepaydirectpro
    
                $sage = $order_model->getSagepayInfo(); 
                $data['payment_details']['avs_result_code'] = $sage->getData('address_result');
                $data['payment_details']['credit_card_bin'] = $bin_number;
                $data['payment_details']['cvv_result_code'] = $sage->getData('cv2result');
                $data['payment_details']['credit_card_number']  = "XXXX-XXXX-".$sage->getData('last_four_digits');
                $data['payment_details']['credit_card_company'] = $sage->getData('card_type');
            }else{
                // payment details if anything else
                $data['payment_details']['avs_result_code']     = $payment_details->getCcAvsStatus();
                $data['payment_details']['credit_card_bin']     = $bin_number;
                $data['payment_details']['cvv_result_code']     = $payment_details->getCcCidStatus();
                $data['payment_details']['credit_card_number']  = "XXXX-XXXX-".$payment_details->getCcLast4();
                $data['payment_details']['credit_card_company'] = $payment_details->getCcType();
            }
            
            
            // payment details
             
            $data['fulfillments']   =NULL;
            
            // client details
            $data['client_details']['accept_language']  =NULL;
            $data['client_details']['browser_ip']   = $order_model->getRemoteIp();;
            $data['client_details']['session_hash'] =NULL;
            $data['client_details']['user_agent']   = Mage::helper('core/http')->getHttpUserAgent();
            
            
            $data['customer']['accepts_marketing']  =NULL;
            $data['customer']['created_at'] = $customer_details->getCreatedAt();
            $data['customer']['email']  = $customer_details->getEmail();
            $data['customer']['first_name'] =$customer_details->getFirstname();
            $data['customer']['id'] = $customer_details->getEntityId();
            $data['customer']['last_name']  = $customer_details->getLastname();
            
            $customer_order_details = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('customer_id', array('eq' => $customer_id))
            ->addFieldToSelect('entity_id')
            ->addFieldToSelect('base_grand_total');
            $total = 0;
            foreach ($customer_order_details as $num => $entity_id){
                $last_id = $entity_id->getData('entity_id');
                $total = $total+$entity_id->getData('base_grand_total');
            }
            
            $data['customer']['last_order_id']  =$last_id;
            $data['customer']['note']   =NULL;
            $data['customer']['orders_count']   = ++$num;
            $data['customer']['state']  =NULL;
            $data['customer']['total_spent']    = $total;
            $data['customer']['updated_at'] = $customer_details->getUpdatedAt();
            $data['customer']['tags']   =NULL;
            $data['customer']['last_order_name']    =NULL;
    
            //$data[NULL]   =NULL;
            //billing info
            $data['billing_address']['first_name']  = $billing_address->getFirstname();
            $data['billing_address']['last_name']   = $billing_address->getLastname();
            $data['billing_address']['name']        = $data['billing_address']['first_name'] . " " . $data['billing_address']['last_name'];
            $data['billing_address']['address1']    = $add['0'];
            $data['billing_address']['address2']    = $add['1'];
            $data['billing_address']['city']        = $billing_address->getCity();
            $data['billing_address']['company']     = $billing_address->getCompany();
            $data['billing_address']['country']     = Mage::getModel('directory/country')->load($billing_address->getCountryId())->getName();
            $data['billing_address']['country_code']= $billing_address->getCountryId();
            $data['billing_address']['phone']       = $billing_address->getTelephone();
            $data['billing_address']['province']    = $billing_address->getRegion();
            $data['billing_address']['zip']         = $billing_address->getPostcode();
            $data['billing_address']['province']    = NULL;
            //shipping info
            $data['shipping_address']['first_name'] = $shipping_address->getFirstname();
            $data['shipping_address']['last_name']  = $shipping_address->getLastname();
            $data['shipping_address']['name']       = $data['shipping_address']['first_name'] . " " . $data['shipping_address']['last_name'];
            $data['shipping_address']['address1']   = $sadd['0'];
            $data['shipping_address']['address2']   = $sadd['1'];
            $data['shipping_address']['city']       = $shipping_address->getCity();
            $data['shipping_address']['company']    = $shipping_address->getCompany();
            $data['shipping_address']['country']    = Mage::getModel('directory/country')->load($shipping_address->getCountryId())->getName();
            $data['shipping_address']['country_code']=$shipping_address->getCountryId();
            $data['shipping_address']['phone']      = $shipping_address->getTelephone();
            $data['shipping_address']['province']   = $shipping_address->getRegion();
            $data['shipping_address']['zip']        = $shipping_address->getPostcode();
            $data['shipping_address']['province_code'] = NULL;  
            // json encode
            $data_string = json_encode($data);
            Mage::log($data_string,null,"json_string.log");
            //generating hash 
            $s_key = Mage::getStoreConfig('fullsection/full/key',Mage::app()->getStore());
            $hash_code = hash_hmac('sha256', $data_string, $s_key);
            
            //firing curl
            $result = $this->fireCurl($data_string,$hash_code);
            
        }
        return;
    }
    
    public function addMassAction($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
                && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('full', array(
                    'label' => 'Submit to Riskified',
                    'url' => Mage::app()->getStore()->getUrl('full/adminhtml_full/riskimass'),
            ));
        }
    }

}
