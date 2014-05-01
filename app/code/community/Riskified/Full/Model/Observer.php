<?php

require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'riskified_php_sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Riskified' . DIRECTORY_SEPARATOR . 'autoloader.php');
use Riskified\Common\Riskified;
use Riskified\Common\Env;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

$authToken = "1388add8a99252fc1a4974de471e73cd";
Riskified::init(Mage::helper('full')->getShopDomain(), $authToken, Env::SANDBOX);

class Riskified_Full_Model_Observer{

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

    public function saveOrderAfter($evt) {
        //        $version = Mage::helper('full')->getExtensionVersion();

        $transport = new Transport\CurlTransport(new Signature\HttpDataSignature());
        $transport->timeout = 15;
        Mage::log("Entering saveOrderAfter, transport: ".$transport->full_path());

        if (is_object($evt)) {
            $submit_now = false;
            $order_ids[] = $evt->getOrder()->getId();
        } else {
            $submit_now = true;
            $order_ids = (is_array($evt)) ? $evt : array($evt);
        }

        foreach ($order_ids as $order_id) {
            Mage::log("Building request, order_id: " . $order_id);

            $model = Mage::getModel('sales/order')->load($order_id);
            $order = $this->getOrder($model);

            Mage::log("Posting request, submit_now : $submit_now, data : ".PHP_EOL.json_encode(json_decode($order->toJson())));

            if ($submit_now)
                $response = $transport->submitOrder($order);
            else
                $response = $transport->createOrUpdateOrder($order);

            Mage::log("Riskified response, data: :".PHP_EOL.json_encode($response));

            if(isset($response->order)){
                $orderId = $response->order->id;
                $status = $response->order->status;
                if($orderId && $status){
                    $state = $this->mapStatus($status);

                    Mage::log("$state: ". json_encode($state));

                    $model->setState($state["state"],$state["mage_status"], $state["comment"]);
                    $model->save();
                }
            }
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


    private function getOrder($model) {
        $order = new Model\Order(array_filter(array(
            'id' => $model->getId(),
            'name' => $model->getIncrementId(),
            'email' => $model->getCustomerEmail(),
            'total_spent' => $model->getGrandTotal(),
            'created_at' => $model->getCreatedAt(),
            'currency' => $model->getBaseCurrencyCode(),
            'updated_at' => $model->getUpdatedAt(),
            'gateway' => $model->getPayment()->getMethod(),
            'browser_ip' => $model->getRemoteIp(),
            'cart_token' => Mage::helper('full')->getSessionId(),
            'note' => $model->getCustomerNote(),
            'total_price' => $model->getGrandTotal(),
            'total_discounts' => $model->getDiscountAmount(),
            'subtotal_price' => $model->getBaseSubtotalInclTax(),
            'discount_codes' => $model->getDiscountDescription(),
            'taxes_included' => true,
            'total_tax' => $model->getBaseTaxAmount(),
            'total_weight' => $model->getWeight()
            //            'cancel_reason' => null,
//            'cancelled_at' => null,
//            'closed_at' => null,
//            'referring_site' => 'null',
        ),'strlen'));

        $order->customer = $this->getCustomer($model);
        $order->shipping_address = $this->getShippingAddress($model);
        $order->billing_address = $this->getBillingAddress($model);
        $order->payment_details = $this->getPaymentDetails($model);
        $order->line_items = $this->getLineItems($model);
        $order->shipping_lines = $this->getShippingLines($model);
        $order->client_details = $this->getClientDetails($model);

        return $order;
    }

    private function getCustomer($model) {
        $customer_id = $model->getCustomerId();
        $customer_details = Mage::getModel('customer/customer')->load($customer_id);
        $customer_order_details = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('customer_id', array('eq' => $customer_id))
            ->addFieldToSelect('entity_id')
            ->addFieldToSelect('base_grand_total');
        $total_spent = 0;
        $orders_count = 0;
        $last_order_id = -1;
        foreach ($customer_order_details as $orders_count => $entity_id){
            $last_order_id = $entity_id->getData('entity_id');
            $total_spent = $total_spent+$entity_id->getData('base_grand_total');
        }
        $orders_count++;

        return new Model\Customer(array_filter(array(
            'created_at' => $customer_details->getCreatedAt(),
            'updated_at' => $customer_details->getUpdatedAt(),
            'email' => $customer_details->getEmail(),
            'first_name' => $customer_details->getFirstname(),
            'last_name' => $customer_details->getLastname(),
            'id' => $customer_details->getEntityId(),
            'orders_count' => $orders_count,
            'verified_email' => true,
            'last_order_id' => $last_order_id,
            'total_spent' => $total_spent
//            'note' => null, // $model->getCustomerNote(),
//            'state' => null,
//            'tags' => null,
//            'last_order_name' => null,
//            'accepts_marketing' => null
        ),'strlen'));
    }

    private function getShippingAddress($model) {
        return new Model\Address($this->getAddressArray($model->getShippingAddress()));
    }

    private function getBillingAddress($model) {
        return new Model\Address($this->getAddressArray($model->getBillingAddress()));
    }

    private function getPaymentDetails($model) {
        $payment = $model->getPayment();

        switch ($payment->getMethod()) {
            case 'authorizenet':
                $cards_data = array_values($payment->getAdditionalInformation('authorize_cards'));
                $card_data = $cards_data[0];
//                ob_start();
//                var_dump(get_class_methods(get_class($payment->getMethodInstance())));
//                $str = ob_get_contents();
//                ob_end_clean();
//                Mage::log('authorizenet $payment: ' . $str);
                $avs_result_code = $card_data['cc_avs_result_code']; // getAvsResultCode
                $cvv_result_code = $card_data['cc_response_code'];  // getCardCodeResponseCode
                $credit_card_number  = "XXXX-XXXX-".$card_data['cc_last4'];
                $credit_card_company = $card_data['cc_type'];
                break;

            case 'paypal_direct':
                $avs_result_code = $payment->getAdditionalInformation('paypal_avs_code');
                $cvv_result_code = $payment->getAdditionalInformation('paypal_cvv2_match');
                $credit_card_number = "XXXX-XXXX-".$payment->getCcLast4();
                $credit_card_company = $payment->getCcType();
                break;

            case 'sagepaydirectpro':
                $sage = $model->getSagepayInfo();
                $avs_result_code = $sage->getData('address_result');
                $cvv_result_code = $sage->getData('cv2result');
                $credit_card_number = "XXXX-XXXX-".$sage->getData('last_four_digits');
                $credit_card_company = $sage->getData('card_type');
                break;

            default:
                $avs_result_code = $payment->getCcAvsStatus();
                $cvv_result_code = $payment->getCcCidStatus();
                $credit_card_number = "XXXX-XXXX-".$payment->getCcLast4();
                $credit_card_company = $payment->getCcType();
                break;
        }

        $credit_card_bin = $payment->getAdditionalInformation('riskified_cc_bin');

        return new Model\PaymentDetails(array_filter(array(
            'avs_result_code' => $avs_result_code,
            'cvv_result_code' => $cvv_result_code,
            'credit_card_number' => $credit_card_number,
            'credit_card_company' => $credit_card_company,
            'credit_card_bin' => $credit_card_bin,
        ),'strlen'));
    }

    private function getLineItems($model) {
        $line_items = array();
        foreach ($model->getItemsCollection() as $key => $val) {
            $line_items[] = new Model\LineItem(array_filter(array(
                'price' => $val->getPrice(),
                'quantity' => intval($val->getQtyOrdered()),
                'title' => $val->getName(),
                'sku' => $val->getSku(),
                'product_id' => $val->getItemId(),
                'grams' => $val->getWeight(),
                'vendor' => $model->getStoreName()
            ),'strlen'));
        }
        return $line_items;
    }

    private function getShippingLines($model) {
        return new Model\ShippingLine(array_filter(array(
            'price' => $model->getShippingAmount(),
            'title' => $model->getShippingDescription(),
            'code' => $model->getShippingMethod()
        ),'strlen'));
    }

    private function getClientDetails($model) {
        return new Model\ClientDetails(array_filter(array(
//            'accept_language' => null,
            'browser_ip' => $model->getRemoteIp(),
//            'session_hash' => null,
            'user_agent' => Mage::helper('core/http')->getHttpUserAgent()
        ),'strlen'));
    }

    private function getAddressArray($address) {
        $street = $address->getStreet();
        $address_1 = (!is_null($street) && array_key_exists('0', $street)) ? $street['0'] : null;
        $address_2 = (!is_null($street) && array_key_exists('1', $street)) ? $street['1'] : null;

        return array_filter(array(
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'name' => $address->getFirstname() . " " . $address->getLastname(),
            'company' => $address->getCompany(),
            'address1' => $address_1,
            'address2' => $address_2,
            'city' => $address->getCity(),
            'country_code' => $address->getCountryId(),
            'country' => Mage::getModel('directory/country')->load($address->getCountryId())->getName(),
            'province' => $address->getRegion(),
            'zip' => $address->getPostcode(),
            'phone' => $address->getTelephone(),
        ), 'strlen');

        // 'province_code'
    }

    private function mapStatus($status){
        Mage::log("Riskified mapStatus : $status");
        $state = null;
        $mage_status = true;
        $comment = null;
        if($status != 'captured'){
            switch ($status) {
                case 'approved':
                    //change order status to 'Processing'
                    $comment = 'Reviewed and approved by Riskified';
                    $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                    break;

                case 'declined':
                    // change order status to 'On Hold'
                    $comment = 'Reviewed and declined by Riskified';
                    $isCustomerNotified = false;
                    $mage_status = Mage_Sales_Model_Order::STATUS_FRAUD;
                    $state = Mage_Sales_Model_Order::STATE_CANCELED;
                    break;

                case 'submitted':
                    // change order status to 'Pending'
                    $comment = 'Under review by Riskified';
                    $state = Mage_Sales_Model_Order::STATE_HOLDED;
                    break;

            }
            Mage::log("mapStatus state = $state");
        }
        return array("state" => $state, "mage_status" => $mage_status, "comment" => $comment );
    }

}
