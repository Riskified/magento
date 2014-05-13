<?php

require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'riskified_php_sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Riskified' . DIRECTORY_SEPARATOR . 'autoloader.php');
use Riskified\Common\Riskified;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

class Riskified_Full_Helper_Order extends Mage_Core_Helper_Abstract {

    public function __construct()  {
        $this->initSdk();
    }

    public function postOrder($model, $submit=false) {
        $transport = $this->getTransport();
        $order = $this->getOrder($model);
        $headers = $this->getHeaders();

        Mage::log('postOrder ' . serialize($headers) . ' - ' . $submit);

        return ($submit) ? $transport->submitOrder($order, $headers)
                         : $transport->createOrUpdateOrder($order, $headers);
    }

    public function updateOrder($order, $status, $description) {
        $state = null;
        switch ($status) {
            case 'approved':
                $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                break;
            case 'declined':
                $state = Mage_Sales_Model_Order::STATE_CANCELED;
                break;
            case 'submitted':
                $state = Mage_Sales_Model_Order::STATE_HOLDED;
                break;
        }
        if ($status && $description) {
            $mageStatus = ($state == Mage_Sales_Model_Order::STATE_CANCELED) ? Mage_Sales_Model_Order::STATUS_FRAUD : true;
            if ($state && Mage::helper('full')->getConfigStatusControlActive()) {
                $order->setState($state, $mageStatus, $description);
                Mage::log("Updated order state " . $order->getId() . " state: $state, mageStatus: $mageStatus, description: $description");
            } else {
                $order->addStatusHistoryComment($description);
                Mage::log("Updated order history comment  " . $order->getId() . " state: $state, mageStatus: $mageStatus, description: $description");

            }
            $order->save();
        }
    }

    public function getRiskifiedDomain() {
        return Riskified::getHostByEnv();
    }

    private $version;

    private function initSdk() {
        $helper = Mage::helper('full');
        $authToken = $helper->getAuthToken();
        $env = constant($helper->getConfigEnv());
        $shopDomain = $helper->getShopDomain();
        $this->version = $helper->getExtensionVersion();

        Mage::log("Riskified initSdk() - shop: $shopDomain, env: $env, token: $authToken");
        Riskified::init($shopDomain, $authToken, $env, true);
    }

    private function getHeaders() {
        return array('headers' => array('X_RISKIFIED_VERSION:'.$this->version));
    }

    private function getTransport() {
        $transport = new Transport\CurlTransport(new Signature\HttpDataSignature());
        $transport->timeout = 15;
        return $transport;
    }

    private function getOrder($model) {
        $order = new Model\Order(array_filter(array(
            'id' => $model->getId(),
            'name' => $model->getIncrementId(),
            'email' => $model->getCustomerEmail(),
            'total_spent' => $model->getGrandTotal(),
            'created_at' => $this->formatDateAsIso8601($model->getCreatedAt()),
            'currency' => $model->getBaseCurrencyCode(),
            'updated_at' => $this->formatDateAsIso8601($model->getUpdatedAt()),
            'gateway' => $model->getPayment()->getMethod(),
            'browser_ip' => $model->getRemoteIp(),
            'cart_token' => Mage::helper('full')->getSessionId(),
            'note' => $model->getCustomerNote(),
            'total_price' => $model->getGrandTotal(),
            'total_discounts' => $model->getDiscountAmount(),
            'subtotal_price' => $model->getBaseSubtotalInclTax(),
            'discount_codes' =>$this->getDiscountCodes($model),
            'taxes_included' => true,
            'total_tax' => $model->getBaseTaxAmount(),
            'total_weight' => $model->getWeight(),
            'cancelled_at' => $this->formatDateAsIso8601($this->getCancelledAt($model))
        ),'strlen'));

        $order->customer = $this->getCustomer($model);
        $order->shipping_address = $this->getShippingAddress($model);
        $order->billing_address = $this->getBillingAddress($model);
        $order->payment_details = $this->getPaymentDetails($model);
        $order->line_items = $this->getLineItems($model);
        $order->shipping_lines = $this->getShippingLines($model);
        $order->client_details = $this->getClientDetails($model);

        Mage::log("getOrder(): ".PHP_EOL.json_encode(json_decode($order->toJson())));

        return $order;
    }

    private function getCustomer($model) {
        $customer_id = $model->getCustomerId();
        $customer_props = array(
            'id' => $customer_id,
            'email' => $model->getCustomerEmail(),
            'first_name' => $model->getCustomerFirstname(),
            'last_name' => $model->getCustomerLastname(),
            'note' => $model->getCustomerNote(),
        );

        if ($customer_id) {
            $total_spent = 0;
            $orders_count = 0;
            $last_order_id = -1;

            $customer_details = Mage::getModel('customer/customer')->load($customer_id);
            $customer_props['created_at'] = $this->formatDateAsIso8601($customer_details->getCreatedAt());
            $customer_props['updated_at'] = $this->formatDateAsIso8601($customer_details->getUpdatedAt());

            $customer_order_details = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('customer_id', array('eq' => $customer_id))
                ->addFieldToSelect('entity_id')
                ->addFieldToSelect('base_grand_total');
            foreach ($customer_order_details as $orders_count => $entity_id){
                $last_order_id = $entity_id->getData('entity_id');
                $total_spent = $total_spent+$entity_id->getData('base_grand_total');
            }
            $orders_count++;

            $customer_props['orders_count'] = $orders_count;
            $customer_props['last_order_id'] = $last_order_id;
            $customer_props['total_spent'] = $total_spent;
        }

        return new Model\Customer(array_filter($customer_props,'strlen'));
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
            'accept_language' => Mage::app()->getLocale()->getLocaleCode(),
            'browser_ip' => $model->getRemoteIp(),
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
    }

    private function getDiscountCodes($model) {
        $code = $model->getDiscountDescription();
        $amount = $model->getDiscountAmount();
        if ($amount && $code)
            return new Model\DiscountCode(array_filter(array(
                'code' => $code,
                'amount' => $amount
            )));
        return null;
    }

    private function getCancelledAt($model) {
        $commentCollection = $model->getStatusHistoryCollection();
        foreach ($commentCollection as $comment) {
            if ($comment->getStatus() == Mage_Sales_Model_Order::STATE_CANCELED) {
                return 'now';
            }
        }
        return null;
    }

    private function formatDateAsIso8601($dateStr) {
        return ($dateStr==NULL) ? NULL : date('c',strtotime($dateStr));
    }
}