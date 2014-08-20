<?php

require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'riskified_php_sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Riskified' . DIRECTORY_SEPARATOR . 'autoloader.php');
use Riskified\Common\Riskified;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;
use Riskified\DecisionNotification\Model\Notification as DecisionNotification;


class Riskified_Full_Helper_Order extends Mage_Core_Helper_Abstract {

    public function __construct()  {
        $this->initSdk();
    }

    public function postOrder($model, $submit=false) {
        $transport = $this->getTransport();
        $order = $this->getOrder($model);
        $headers = $this->getHeaders();

	    Mage::helper('full/log')->log('postOrder ' . serialize($headers) . ' - ' . $submit);

        return ($submit) ? $transport->submitOrder($order, $headers)
                         : $transport->createOrUpdateOrder($order, $headers);
    }

	/**
	 * Dispatch events for order update handling
	 *
	 * Possible events are:
	 *      - riskified_order_update
	 *      - riskified_order_update_approved
	 *      - riskified_order_update_declined
	 *      - riskified_order_update_submitted
	 *      - riskified_order_update_captured
	 *      - riskified_order_update_?
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @param string $status
	 * @param string $description
	 * @return void
	 */
	public function updateOrder($order, $status, $description) {
		Mage::helper('full/log')->log('Dispatching event for order ' . $order->getId() . ' with status "' . $status . '" and description "' . $description . '"');

		$eventData = array(
			'order' => $order,
			'status' => $status,
			'description' => $description
		);

		// A generic event for all updates
		Mage::dispatchEvent(
			'riskified_full_order_update',
			$eventData
		);

		// A status-specific event
		$eventIdentifier = preg_replace("/[^a-z]/", '_', strtolower($status));

		Mage::dispatchEvent(
			'riskified_full_order_update_' . $eventIdentifier,
			$eventData
		);

		return;
    }

    public function getRiskifiedDomain() {
        return Riskified::getHostByEnv();
    }

    public function parseRequest($request) {
        $header_name = Signature\HttpDataSignature::HMAC_HEADER_NAME;
        $headers = array($header_name.':'.$request->getHeader($header_name));
        $body = $request->getRawBody();
        return new DecisionNotification(new Signature\HttpDataSignature(), $headers, $body);
    }

    private $version;

    private function initSdk() {
        $helper = Mage::helper('full');
        $authToken = $helper->getAuthToken();
        $env = constant($helper->getConfigEnv());
        $shopDomain = $helper->getShopDomain();
        $this->version = $helper->getExtensionVersion();

	    Mage::helper('full/log')->log("Riskified initSdk() - shop: $shopDomain, env: $env, token: $authToken");
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

	    Mage::helper('full/log')->log("getOrder(): ".PHP_EOL.json_encode(json_decode($order->toJson())));

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
            $customer_details = Mage::getModel('customer/customer')->load($customer_id);
            $customer_props['created_at'] = $this->formatDateAsIso8601($customer_details->getCreatedAt());
            $customer_props['updated_at'] = $this->formatDateAsIso8601($customer_details->getUpdatedAt());

            $customer_orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $customer_id);
            $customer_orders_count = $customer_orders->count();

            $customer_props['orders_count'] = $customer_orders_count;
            if ($customer_orders_count) {
                $customer_props['last_order_id'] = $customer_orders->getLastItem()->getId();
                $customer_props['total_spent'] = array_sum($customer_orders->getColumnValues('base_grand_total'));
            }
        }

        return new Model\Customer(array_filter($customer_props,'strlen'));
    }

    private function getShippingAddress($model) {
        $mageAddr = $model->getShippingAddress();
        return $this->getAddress($mageAddr);
    }

    private function getBillingAddress($model) {
        $mageAddr = $model->getBillingAddress();
        return $this->getAddress($mageAddr);
    }

    private function getPaymentDetails($model) {
        $payment = $model->getPayment();
        if(!$payment) {
            return null;
        }

        switch ($payment->getMethod()) {
            case 'authorizenet':
                $cards_data = array_values($payment->getAdditionalInformation('authorize_cards'));
                $card_data = $cards_data[0];
                $avs_result_code = $card_data['cc_avs_result_code']; // getAvsResultCode
                $cvv_result_code = $card_data['cc_response_code'];  // getCardCodeResponseCode
                $credit_card_number  = "XXXX-XXXX-XXXX-".$card_data['cc_last4'];
                $credit_card_company = $card_data['cc_type'];
                break;
            case 'paypal_express':
            case 'paypaluk_express':
                $payer_email = $payment->getAdditionalInformation('paypal_payer_email');
                $payer_status = $payment->getAdditionalInformation('paypal_payer_status');
                $payer_address_status = $payment->getAdditionalInformation('paypal_address_status');
                $protection_eligibility = $payment->getAdditionalInformation('paypal_protection_eligibility');
                $payment_status = $payment->getAdditionalInformation('paypal_payment_status');
                $pending_reason = $payment->getAdditionalInformation('paypal_pending_reason');

                return new Model\PaymentDetails(array_filter(array(
                    'payer_email' => $payer_email,
                    'payer_status' => $payer_status,
                    'payer_address_status' => $payer_address_status,
                    'protection_eligibility' => $protection_eligibility,
                    'payment_status' => $payment_status,
                    'pending_reason' => $pending_reason
                ),'strlen'));

            case 'paypal_direct':
                $avs_result_code = $payment->getAdditionalInformation('paypal_avs_code');
                $cvv_result_code = $payment->getAdditionalInformation('paypal_cvv2_match');
                $credit_card_number = "XXXX-XXXX-XXXX-".$payment->getCcLast4();
                $credit_card_company = $payment->getCcType();
                break;

            case 'sagepaydirectpro':
                $sage = $model->getSagepayInfo();
                $avs_result_code = $sage->getData('address_result');
                $cvv_result_code = $sage->getData('cv2result');
                $credit_card_number = "XXXX-XXXX-XXXX-".$sage->getData('last_four_digits');
                $credit_card_company = $sage->getData('card_type');
                break;

            default:
                $avs_result_code = $payment->getCcAvsStatus();
                $cvv_result_code = $payment->getCcCidStatus();
                $credit_card_number = "XXXX-XXXX-XXXX-".$payment->getCcLast4();
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

    private function getAddress($address) {
        if(!$address) {
            return null;
        }

        $street = $address->getStreet();
        $address_1 = (!is_null($street) && array_key_exists('0', $street)) ? $street['0'] : null;
        $address_2 = (!is_null($street) && array_key_exists('1', $street)) ? $street['1'] : null;

        $addrArray =  array_filter(array(
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

        if(!$addrArray) {
            return null;
        }
        return new Model\Address($addrArray);
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