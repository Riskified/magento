<?php
class Riskified_Full_Model_Observer{
	
	Private function fireCurl($data_string){
		$ch = curl_init('http://public-beta.herokuapp.com/webhooks/order_created');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data_string),
		'X_RISKIFIED_SHOP_DOMAIN:magento.riskified.com')
		);
		$result = curl_exec($ch);
		return $result;
	}
	
	public function saveOrderAfter($evt)
	{
		$order = $evt->getOrder();
		$order_id = $order->getId();
		
		$order_model = Mage::getModel('sales/order')->load($order_id);
		$billing_address = $order_model->getBillingAddress();
		$shipping_address = $order_model->getShippingAddress();
		$customer_id = $order_model->getCustomerId();
    	$customer_details = Mage::getModel('customer/customer')->load($customer_id);
		$payment_details = $order_model->getPayment();
		$add = $billing_address->getStreet();
		$sadd = $shipping_address->getStreet();
		// generating data
		$data = array();
		$data['id'] 			= $order_model->getId();
		$data['name'] 			= $order_model->getId();
		$data['shipping_line'] 	= $order_model->getShippingDescription();
		$data['email']			= $order_model->getCustomerEmail();
		$data['total_spent']	= $order_model->getGrandTotal();
		$data['created_at']		= $order_model->getCreatedAt();
		$data['updated_at']		= $order_model->getUpdatedAt();
		$data['gateway']		= $payment_details->getMethod();
		$data['browser_ip']		= $order_model->getRemoteIp();
		$data['buyer_accepts_marketing']	='';
		$data['cancel_reason']	='';
		$data['cancelled_at']	='';
		$data['cart_token']	='';
		$data['closed_at']	='';
		$data['currency']	=$order_model->getBaseCurrencyCode();
		$data['financial_status']	='';
		$data['fulfillment_status']	='';
		$data['landing_site']	='/';
		$data['note']	= $order_model->getCustomerNote();
		$data['number']	='';
		$data['reference']	='';
		$data['referring_site']	='';
		$data['source']	='';
		$data['subtotal_price']	= $order_model->getBaseSubtotalInclTax();
		$data['taxes_included']	=TRUE;
		$data['token']	='';
		$data['total_discounts']	= $order_model->getDiscountAmount();
		$data['total_line_items_price']	= $order_model->getGrandTotal();
		$data['total_price']	=$order_model->getGrandTotal();
		$data['total_price_usd']	=$order_model->getGrandTotal();
		$data['total_tax']	=$order_model->getBaseTaxAmount();
		$data['total_weight']	= $order_model->getWeight();
		$data['user_id']	=$order_model->getCustomerId();
		$data['landing_site_ref']	='';
		$data['order_number']	=$order_model->getId();
		$data['discount_codes']	=$order_model->getDiscountDescription();
		$data['note_attributes'] = "";
		$data['processing_method'] = "";
		$data['checkout_id']	='';
		
		foreach ($order_model->getItemsCollection() as $key => $val)
		{
			$data['line_items'][$key]['fulfillment_service']	='';
			$data['line_items'][$key]['fulfillment_status']	='';
			$data['line_items'][$key]['grams']	= $val->getWeight();
			$data['line_items'][$key]['id']	= $val->getItemId();
			$data['line_items'][$key]['price']	= $val->getPrice;
			$data['line_items'][$key]['product_id']	= $val->getItemId();
			$data['line_items'][$key]['quantity']	= $val->getQtyOrdered();
			$data['line_items'][$key]['requires_shipping']	='';
			$data['line_items'][$key]['sku']	= $val->getSku();
			$data['line_items'][$key]['title']	= $val->getName();
			$data['line_items'][$key]['variant_id']	='';
			$data['line_items'][$key]['variant_title']	='';
			$data['line_items'][$key]['vendor']	= $order_model->getStoreName();
			$data['line_items'][$key]['name']	= $val->getName();
			$data['line_items'][$key]['variant_inventory_management']	='';
			$data['line_items'][$key]['properties']	='';
		}
		$data['shipping_lines']['']	='';
		$data['tax_lines']['']	='';
		$data['payment_details']['avs_result_code']	= $payment_details->getAVSCode();
		$data['payment_details']['credit_card_bin']	='';
		$data['payment_details']['cvv_result_code']	= $payment_details->getCVV2Code();
		$data['payment_details']['credit_card_number']	= $payment_details->getCreditCardNumber();
		$data['payment_details']['credit_card_company']	= $payment_details->getCreditCardType();
		$data['fulfillments']['']	='';
		$data['client_details']['']	='';
		$data['customer']['accepts_marketing']	='';
		$data['customer']['created_at']	= $customer_details->getCreatedAt();
		$data['customer']['email']	= $customer_details->getEmail();
		$data['customer']['first_name']	=$customer_details->getFirstName();
		$data['customer']['id']	= $customer_details->getEntityId();
		$data['customer']['last_name']	= $customer_details->getLastname();
		$data['customer']['last_order_id']	='';
		$data['customer']['note']	='';
		$data['customer']['orders_count']	='';
		$data['customer']['state']	='';
		$data['customer']['total_spent']	='';
		$data['customer']['updated_at']	= $customer_details->getUpdatedAt();
		$data['customer']['tags']	='';
		$data['customer']['last_order_name']	='';

		//$data['']	='';
		//billing info
		$data['billing_address']['first_name'] 	= $billing_address->getFirstname();
		$data['billing_address']['last_name']	= $billing_address->getLastname();
		$data['billing_address']['address1'] 	= $add['0'];
		$data['billing_address']['address2'] 	= $add['1'];
		$data['billing_address']['city'] 		= $billing_address->getCity();
		$data['billing_address']['company'] 	= $billing_address->getCompany();
		$data['billing_address']['country'] 	= Mage::getModel('directory/country')->load($billing_address->getCountryId())->getName();
		$data['billing_address']['country_code']= $billing_address->getCountryId();
		$data['billing_address']['phone'] 		= $billing_address->getTelephone();
		$data['billing_address']['province'] 	= $billing_address->getRegion();
		$data['billing_address']['zip'] 		= $billing_address->getPostcode();
		$data['billing_address']['province']	= '';
		//shipping info
		$data['shipping_address']['first_name'] = $shipping_address->getFirstname();
		$data['shipping_address']['last_name'] 	= $shipping_address->getLastname();
		$data['shipping_address']['address1'] 	= $sadd['0'];
		$data['shipping_address']['address2'] 	= $sadd['1'];
		$data['shipping_address']['city'] 		= $shipping_address->getCity();
		$data['shipping_address']['company'] 	= $shipping_address->getCompany();
		$data['shipping_address']['country'] 	= Mage::getModel('directory/country')->load($shipping_address->getCountryId())->getName();
		$data['shipping_address']['country_code']=$shipping_address->getCountryId();
		$data['shipping_address']['phone'] 		= $shipping_address->getTelephone();
		$data['shipping_address']['province'] 	= $shipping_address->getRegion();
		$data['shipping_address']['zip'] 		= $shipping_address->getPostcode();
		$data['shipping_address']['province_code'] ='';
		//firing curl
		$data_string = json_encode($data);
		$result = $this->fireCurl($data_string);
	}
	
	

}