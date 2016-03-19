<?php

require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'riskified_php_sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Riskified' . DIRECTORY_SEPARATOR . 'autoloader.php');
use Riskified\Common\Riskified;
use Riskified\Common\Signature;
use Riskified\Common\Validations;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;
use Riskified\DecisionNotification\Model\Notification as DecisionNotification;


class Riskified_Full_Helper_Order extends Mage_Core_Helper_Abstract {
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_SUBMIT = 'submit';
    const ACTION_CANCEL = 'cancel';

    public function __construct()  {
        $this->initSdk();
    }

    /**
     * Update the merchan't settings
     * @param settings hash
     * @return stdClass
     * @throws Exception
     */
    public function updateMerchantSettings($settings) {
        $transport = $this->getTransport();
        Mage::helper('full/log')->log('updateMerchantSettings');

        try {
            $response = $transport->updateMerchantSettings($settings);

            Mage::helper('full/log')->log('Merchant Settings posted successfully');
        } catch(\Riskified\OrderWebhook\Exception\UnsuccessfulActionException $uae) {
            if ($uae->statusCode == '401') {
                Mage::helper('full/log')->logException($uae);
                Mage::getSingleton('adminhtml/session')->addError('Make sure you have the correct Auth token as it appears in Riskified advanced settings.');
            }
            throw $uae;
        } catch(\Riskified\OrderWebhook\Exception\CurlException $curlException) {
            Mage::helper('full/log')->logException($curlException);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $curlException->getMessage());

            throw $curlException;
        }
        catch (Exception $e) {
            Mage::helper('full/log')->logException($e);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $e->getMessage());

            throw $e;
        }
        return $response;

    }
    /**
     * Submit an order to Riskified.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $action - one of self::ACTION_*
     * @return stdClass
     * @throws Exception
     */
    public function postOrder($order, $action) {
        $transport = $this->getTransport();
        $headers = $this->getHeaders();

	    Mage::helper('full/log')->log('postOrder ' . serialize($headers) . ' - ' . $action);

        $eventData = array(
            'order' => $order,
            'action' => $action
        );

        try {
            switch ($action) {
                case self::ACTION_CREATE:
                    $orderForTransport = $this->getOrder($order);
                    $response = $transport->createOrder($orderForTransport);

                    break;
                case self::ACTION_UPDATE:
                    $orderForTransport = $this->getOrder($order);
                    $response = $transport->updateOrder($orderForTransport);

                    break;
                case self::ACTION_SUBMIT:
                    $orderForTransport = $this->getOrder($order);
                    $response = $transport->submitOrder($orderForTransport);

                    break;
                case self::ACTION_CANCEL:
                    $orderForTransport = $this->getOrderCancellation($order);
                    $response = $transport->cancelOrder($orderForTransport);

                    break;
            }

            Mage::helper('full/log')->log('Order posted successfully - invoking post order event');

            $eventData['response'] = $response;

            Mage::dispatchEvent(
                'riskified_full_post_order_success',
                $eventData
            );
        } catch(\Riskified\OrderWebhook\Exception\CurlException $curlException) {
            Mage::helper('full/log')->logException($curlException);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $curlException->getMessage());

            $this->updateOrder($order, 'error',null, 'Error transferring order data to Riskified');
            $this->scheduleSubmissionRetry($order, $action);

            Mage::dispatchEvent(
                'riskified_full_post_order_error',
                $eventData
            );

            throw $curlException;
        }
        catch (Exception $e) {
            Mage::helper('full/log')->logException($e);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $e->getMessage());

            Mage::dispatchEvent(
                'riskified_full_post_order_error',
                $eventData
            );

            throw $e;
        }

        return $response;
    }

    public function postHistoricalOrders($models) {
        $orders = array();
        foreach ($models as $model) {
            $orders[] = $this->getOrder($model);
        }

        $msgs = $this->getTransport()->sendHistoricalOrders($orders);
        return "Successfully uploaded ".count($msgs)." orders.".PHP_EOL;
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
     *      - riskified_order_update_error
	 *      - riskified_order_update_?
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @param string $status
     * @param string $oldStatus
	 * @param string $description
	 * @return void
	 */
	public function updateOrder($order, $status, $oldStatus, $description) {
		Mage::helper('full/log')->log('Dispatching event for order ' . $order->getId() . ' with status "' . $status .
            '" old status "' . $oldStatus . '" and description "' . $description . '"');

		$eventData = array(
			'order' => $order,
			'status' => $status,
            'old_status' => $oldStatus,
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

    public function getRiskifiedApp() {
        return str_replace("wh", "app", $this->getRiskifiedDomain());
    }

    public function parseRequest($request) {
        $header_name = Signature\HttpDataSignature::HMAC_HEADER_NAME;
        $headers = array($header_name => $request->getHeader($header_name));
        $body = $request->getRawBody();
        Mage::helper('full/log')->log("Received new notification request with headers: " . json_encode($headers) . " and body: $body. Trying to parse.");
        return new DecisionNotification(new Signature\HttpDataSignature(), $headers, $body);
    }

    public function getOrderOrigId($order) {
        if(!$order) {
            return null;
        }
        return $order->getId() . '_' . $order->getIncrementId();
    }

    private $version;

    private function initSdk() {
        $helper = Mage::helper('full');
        $authToken = $helper->getAuthToken();
        $env = constant($helper->getConfigEnv());
        $shopDomain = $helper->getShopDomain();
        $this->version = $helper->getExtensionVersion();
        $sdkVersion = Riskified::VERSION;

	    Mage::helper('full/log')->log("Riskified initSdk() - shop: $shopDomain, env: $env, token: $authToken, extension_version: $this->version, sdk_version: $sdkVersion");
        Riskified::init($shopDomain, $authToken, $env, Validations::SKIP);
    }

    private function getHeaders() {
        return array('headers' => array('X_RISKIFIED_VERSION:'.$this->version));
    }

    private function getTransport() {
        $transport = new Transport\CurlTransport(new Signature\HttpDataSignature());
        $transport->timeout = 15;
        return $transport;
    }

    private function getOrderCancellation($model) {
        $orderCancellation = new Model\OrderCancellation(array_filter(array(
            'id' => $this->getOrderOrigId($model),
            'cancelled_at' => $this->formatDateAsIso8601($this->getCancelledAt($model)),
            'cancel_reason' => 'Cancelled by merchant'
        )));

        Mage::helper('full/log')->log("getOrderCancellation(): ".PHP_EOL.json_encode(json_decode($orderCancellation->toJson())));

        return $orderCancellation;
    }

    private function getOrder($model) {
        $gateway = 'unavailable';
        if ($model->getPayment()) {
            $gateway = $model->getPayment()->getMethod();
        }

        $order_array = array(
            'id' => $this->getOrderOrigId($model),
            'name' => $model->getIncrementId(),
            'email' => $model->getCustomerEmail(),
            'created_at' => $this->formatDateAsIso8601($model->getCreatedAt()),
            'currency' => $model->getOrderCurrencyCode(),  // was getBaseCurrencyCode() before by mistake
            'updated_at' => $this->formatDateAsIso8601($model->getUpdatedAt()),
            'gateway' => $gateway,
            'browser_ip' => $this->getRemoteIp($model),
            'cart_token' => Mage::helper('full')->getSessionId(),
            'note' => $model->getCustomerNote(),
            'total_price' => $model->getGrandTotal(),
            'total_discounts' => $model->getDiscountAmount(),
            'subtotal_price' => $model->getBaseSubtotalInclTax(),
            'discount_codes' =>$this->getDiscountCodes($model),
            'taxes_included' => true,
            'total_tax' => $model->getBaseTaxAmount(),
            'total_weight' => $model->getWeight(),
            'cancelled_at' => $this->formatDateAsIso8601($this->getCancelledAt($model)),
            'financial_status' => $model->getState(),
            'fulfillment_status' => $model->getStatus(),
            'vendor_id' => $model->getStoreId(),
            'vendor_name' => $model->getStoreName()
        );

        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            unset($order_array['browser_ip']);
            unset($order_array['cart_token']);
        }

        $order = new Model\Order(array_filter($order_array,'strlen'));

        $order->customer = $this->getCustomer($model);
        $order->shipping_address = $this->getShippingAddress($model);
        $order->billing_address = $this->getBillingAddress($model);
        $order->payment_details = $this->getPaymentDetails($model);
        $order->line_items = $this->getLineItems($model);
        $order->shipping_lines = $this->getShippingLines($model);

        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            $order->client_details = $this->getClientDetails($model);
        }

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
            'group_name' => $model->getCustomerGroupId()
        );

        if ($customer_id) {
            $customer_details = Mage::getModel('customer/customer')->load($customer_id);
            $customer_props['created_at'] = $this->formatDateAsIso8601($customer_details->getCreatedAt());
            $customer_props['updated_at'] = $this->formatDateAsIso8601($customer_details->getUpdatedAt());

            try {
                $customer_orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $customer_id);
                $customer_orders_count = $customer_orders->getSize();

                $customer_props['orders_count'] = $customer_orders_count;
                if ($customer_orders_count) {
                    $customer_props['last_order_id'] = $customer_orders->getLastItem()->getId();
                    $total_spent = $customer_orders
                        ->addExpressionFieldToSelect('sum_total', 'SUM(base_grand_total)', 'base_grand_total')
                        ->fetchItem()->getSumTotal();
                    $customer_props['total_spent'] = $total_spent;
                }
            } catch (Exception $e) {
                Mage::helper('full/log')->logException($e);
                Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $e->getMessage());
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

    private function logPaymentData($model) {
        Mage::helper('full/log')->log("Payment info debug Logs:");
        try {
            $payment = $model->getPayment();
            $gatewayName = $payment->getMethod();
            Mage::helper('full/log')->log("Payment Gateway: ".$gatewayName);
            Mage::helper('full/log')->log("payment->getCcLast4(): ".$payment->getCcLast4());
            Mage::helper('full/log')->log("payment->getCcType(): ".$payment->getCcType());
            Mage::helper('full/log')->log("payment->getCcCidStatus(): ".$payment->getCcCidStatus());
            Mage::helper('full/log')->log("payment->getCcAvsStatus(): ".$payment->getCcAvsStatus());
            Mage::helper('full/log')->log("payment->getAdditionalInformation(): ".PHP_EOL.var_export($payment->getAdditionalInformation(), 1));


            Mage::helper('full/log')->log("payment->getAdyenPspReference(): ".$payment->getAdyenPspReference());
            Mage::helper('full/log')->log("payment->getAdyenKlarnaNumber(): ".$payment->getAdyenKlarnaNumber());
            Mage::helper('full/log')->log("payment->getAdyenAvsResult(): ".$payment->getAdyenAvsResult());
            Mage::helper('full/log')->log("payment->getAdyenCvcResult(): ".$payment->getAdyenCvcResult());
            Mage::helper('full/log')->log("payment->getAdyenBoletoPaidAmount(): ".$payment->getAdyenBoletoPaidAmount());
            Mage::helper('full/log')->log("payment->getAdyenTotalFraudScore(): ".$payment->getAdyenTotalFraudScore());
            Mage::helper('full/log')->log("payment->getAdyenRefusalReasonRaw(): ".$payment->getAdyenRefusalReasonRaw());
            Mage::helper('full/log')->log("payment->getAdyenAcquirerReference(): ".$payment->getAdyenAcquirerReference());
            Mage::helper('full/log')->log("(possibly BIN?) payment->getAdyenAuthCode(): ".$payment->getAdyenAuthCode());

            Mage::helper('full/log')->log("payment->getInfo(): ".PHP_EOL.var_export($payment->getInfo(), 1));

            # paypal_avs_code,paypal_cvv2_match,paypal_fraud_filters,avs_result,cvv2_check_result,address_verification,
            # postcode_verification,payment_status,pending_reason,payer_id,payer_status,email,credit_card_cvv2,
            # cc_avs_status,cc_approval,cc_last4,cc_owner,cc_exp_month,cc_exp_year,
            $sage = $model->getSagepayInfo();
            if(is_object($sage)) {
                #####,postcode_result,avscv2,address_status,payer_status
                Mage::helper('full/log')->log("sagepay->getLastFourDigits(): ".$sage->getLastFourDigits());
                Mage::helper('full/log')->log("sagepay->last_four_digits: ".$sage->getData('last_four_digits'));
                Mage::helper('full/log')->log("sagepay->getCardType(): ".$sage->getCardType());
                Mage::helper('full/log')->log("sagepay->card_type: ".$sage->getData('card_type'));
                Mage::helper('full/log')->log("sagepay->getAvsCv2Status: ".$sage->getAvsCv2Status());
                Mage::helper('full/log')->log("sagepay->address_result: ".$sage->getData('address_result'));
                Mage::helper('full/log')->log("sagepay->getCv2result: ".$sage->getCv2result());
                Mage::helper('full/log')->log("sagepay->cv2result: ".$sage->getData('cv2result'));
                Mage::helper('full/log')->log("sagepay->getAvscv2: ".$sage->getAvscv2());
                Mage::helper('full/log')->log("sagepay->getAddressResult: ".$sage->getAddressResult());
                Mage::helper('full/log')->log("sagepay->getPostcodeResult: ".$sage->getPostcodeResult());
                Mage::helper('full/log')->log("sagepay->getDeclineCode: ".$sage->getDeclineCode());
                Mage::helper('full/log')->log("sagepay->getBankAuthCode: ".$sage->getBankAuthCode());
                Mage::helper('full/log')->log("sagepay->getPayerStatus: ".$sage->getPayerStatus());
            }
            if($gatewayName == "optimal_hosted") {
                $optimalTransaction = unserialize($payment->getAdditionalInformation('transaction'));
                if($optimalTransaction) {
                    Mage::helper('full/log')->log("Optimal transaction: ");
                    Mage::helper('full/log')->log("transaction->cvdVerification: ".$optimalTransaction->cvdVerification);
                    Mage::helper('full/log')->log("transaction->houseNumberVerification: ".$optimalTransaction->houseNumberVerification);
                    Mage::helper('full/log')->log("transaction->zipVerification: ".$optimalTransaction->zipVerification);
                }
                else {
                    Mage::helper('full/log')->log("Optimal gateway but no transaction found");
                }
            }

        } catch(Exception $e) {
            Mage::helper('full/log')->logException($e);
        }
    }

    private function getPaymentDetails($model) {
        $payment = $model->getPayment();
        if(!$payment) {
            return null;
        }

        if(Mage::helper('full')->isDebugLogsEnabled()) {
            $this->logPaymentData($model);
        }

        $transactionId = $payment->getTransactionId();

        $gatewayName = $payment->getMethod();

        try {
            switch ($gatewayName) {
                case 'authorizenet':
                    $authorize_data = $payment->getAdditionalInformation('authorize_cards');
                    if($authorize_data && is_array($authorize_data)) {
                        $cards_data = array_values($authorize_data);
                        if ($cards_data && $cards_data[0]) {
                            $card_data = $cards_data[0];
                            if(isset($card_data['cc_last4'])) { $creditCardNumber = $card_data['cc_last4']; }
                            if(isset($card_data['cc_type'])) { $creditCardCompany = $card_data['cc_type']; }
                            if(isset($card_data['cc_avs_result_code'])) { $avsResultCode = $card_data['cc_avs_result_code']; }// getAvsResultCode
                            if(isset($card_data['cc_response_code'])) { $cvvResultCode = $card_data['cc_response_code']; } // getCardCodeResponseCode
                        }
                    }
                    break;
                case 'authnetcim':
                    $avsResultCode = $payment->getAdditionalInformation('avs_result_code');
                    $cvvResultCode = $payment->getAdditionalInformation('card_code_response_code');
                    #$cavv_result_code = $payment->getAdditionalInformation('cavv_response_code');
                    #$is_fraud = $payment->getAdditionalInformation('is_fraud');
                    break;
                case 'optimal_hosted':
                    try {
                        $optimalTransaction = unserialize($payment->getAdditionalInformation('transaction'));
                        $cvvResultCode = $optimalTransaction->cvdVerification;
                        $houseVerification = $optimalTransaction->houseNumberVerification;
                        $zipVerification = $optimalTransaction->zipVerification;
                        $avsResultCode = $houseVerification . ',' . $zipVerification;
                    } catch(Exception $e) {
                        Mage::helper('full/log')->log("optimal payment (".$gatewayName.") additional payment info failed to parse:".$e->getMessage());
                    }
                    break;
                case 'paypal_express':
                case 'paypaluk_express':
                case 'paypal_standard':
                    $payer_email = $payment->getAdditionalInformation('paypal_payer_email');
                    $payer_status = $payment->getAdditionalInformation('paypal_payer_status');
                    $payer_address_status = $payment->getAdditionalInformation('paypal_address_status');
                    $protection_eligibility = $payment->getAdditionalInformation('paypal_protection_eligibility');
                    $payment_status = $payment->getAdditionalInformation('paypal_payment_status');
                    $pending_reason = $payment->getAdditionalInformation('paypal_pending_reason');
                    return new Model\PaymentDetails(array_filter(array(
                        'authorization_id' => $transactionId,
                        'payer_email' => $payer_email,
                        'payer_status' => $payer_status,
                        'payer_address_status' => $payer_address_status,
                        'protection_eligibility' => $protection_eligibility,
                        'payment_status' => $payment_status,
                        'pending_reason' => $pending_reason
                    ), 'strlen'));
                case 'paypal_direct':
                case 'paypaluk_direct':
                    $avsResultCode = $payment->getAdditionalInformation('paypal_avs_code');
                    $cvvResultCode = $payment->getAdditionalInformation('paypal_cvv2_match');
                    $creditCardNumber = $payment->getCcLast4();
                    $creditCardCompany = $payment->getCcType();
                    break;
                case 'sagepaydirectpro':
                case 'sage_pay_form':
                case 'sagepayserver':
                    $sage = $model->getSagepayInfo();
                    if ($sage) {
                        $avsResultCode = $sage->getData('address_result');
                        $cvvResultCode = $sage->getData('cv2result');
                        $creditCardNumber = $sage->getData('last_four_digits');
                        $creditCardCompany = $sage->getData('card_type');
                        //Mage::helper('full/log')->log("sagepay payment (".$gatewayName.") additional info: ".PHP_EOL.var_export($sage->getAdditionalInformation(), 1));
                        Mage::helper('full/log')->log("sagepay payment (".$gatewayName.") additional info: ".PHP_EOL.var_export($payment->getAdditionalInformation(), 1));
                    }
                    else {
                        Mage::helper('full/log')->log("sagepay payment (".$gatewayName.") - getSagepayInfo returned null object");
                    }
                    break;

                case 'transarmor':
                    $avsResultCode = $payment->getAdditionalInformation('avs_response');
                    $cvvResultCode = $payment->getAdditionalInformation('cvv2_response');
                    Mage::helper('full/log')->log("transarmor payment additional info: ".PHP_EOL.var_export($payment->getAdditionalInformation(), 1));
                    break;

                case 'braintreevzero':
                    $cvvResultCode = $payment->getAdditionalInformation('cvvResponseCode');
                    $creditCardBin = $payment->getAdditionalInformation('bin');
                    $houseVerification = $payment->getAdditionalInformation('avsStreetAddressResponseCode');
                    $zipVerification = $payment->getAdditionalInformation('avsPostalCodeResponseCode');
                    $avsResultCode = $houseVerification . ',' . $zipVerification;
                    break;

                case 'adyen_cc':
                    $avsResultCode = $payment->getAdyenAvsResult();
                    $cvvResultCode = $payment->getAdyenCvcResult();
                    $transactionId = $payment->getAdyenPspReference();
                    $creditCardBin = $payment->getAdyenCardBin();
                    break;

                default:
                    Mage::helper('full/log')->log("unknown gateway:" . $gatewayName);
                    Mage::helper('full/log')->log("Gateway payment (".$gatewayName.") additional info: ".PHP_EOL.var_export($payment->getAdditionalInformation(), 1));
                    break;
            }
        } catch (Exception $e) {
            Mage::helper('full/log')->logException($e);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $e->getMessage());
        }

        if (!isset($cvvResultCode)) {
            $cvvResultCode = $payment->getCcCidStatus();
        }
        if (!isset($creditCardNumber)) {
            $creditCardNumber = $payment->getCcLast4();
        }
        if (!isset($creditCardCompany)) {
            $creditCardCompany = $payment->getCcType();
        }
        if (!isset($avsResultCode)) {
            $avsResultCode = $payment->getCcAvsStatus();
        }
        if (!isset($creditCardBin)) {
            $creditCardBin = $payment->getAdditionalInformation('riskified_cc_bin');
        }
        if (isset($creditCardNumber)) {
            $creditCardNumber = "XXXX-XXXX-XXXX-" . $creditCardNumber;
        }


        return new Model\PaymentDetails(array_filter(array(
            'authorization_id' => $transactionId,
            'avs_result_code' => $avsResultCode,
            'cvv_result_code' => $cvvResultCode,
            'credit_card_number' => $creditCardNumber,
            'credit_card_company' => $creditCardCompany,
            'credit_card_bin' => $creditCardBin
        ),'strlen'));
    }

    private function getLineItems($model) {
        $lineItems = array();
        foreach ($model->getAllVisibleItems() as $key => $val) {
            $prodType = null;
            $category = null;
            $subCategories = null;
            $product = $val->getProduct();
            if($product) {
                $prodType = $val->getProduct()->getTypeId();
                $categoryIds = $product->getCategoryIds();
                foreach ($categoryIds as $categoryId) {
                    $cat = Mage::getModel('catalog/category')->load($categoryId);
                    $catName = $cat->getName();
                    if (!empty($catName)) {
                        if(empty($category)) {
                            $category = $catName;
                        }
                        else if(empty($subCategories)) {
                            $subCategories = $catName;
                        }
                        else {
                            $subCategories = $subCategories . '|' . $catName;
                        }

                    }
                }
            }
            $lineItems[] = new Model\LineItem(array_filter(array(
                'price' => $val->getPrice(),
                'quantity' => intval($val->getQtyOrdered()),
                'title' => $val->getName(),
                'sku' => $val->getSku(),
                'product_id' => $val->getItemId(),
                'grams' => $val->getWeight(),
                'product_type' => $prodType,
                'category' => $category,
                //'sub_category' => $subCategories
            ),'strlen'));
        }

        return $lineItems;
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
            //'browser_ip' => $this->getRemoteIp($model),
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

    private function getRemoteIp($model) {
        Mage::helper('full/log')->log("remote ip: " . $model->getRemoteIp() . ", x-forwarded-ip: " . $model->getXForwardedFor());

        $forwardedIp = $model->getXForwardedFor();
        $forwardeds = preg_split("/,/",$forwardedIp, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($forwardeds)) {
            return trim($forwardeds[0]);
        }
        $remoteIp = $model->getRemoteIp();
        $remotes = preg_split("/,/",$remoteIp, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($remotes)) {
            return trim($remotes[0]);
        }

        return $remoteIp;
    }

    /**
     * Schedule an attempt to retry this order submission.  This should be called any time a submission attempt fails.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $action - one of self::ACTION_*
     * @return void
     */
    public function scheduleSubmissionRetry(Mage_Sales_Model_Order $order, $action)
    {
        Mage::helper('full/log')->log("Scheduling submission retry for order " . $order->getId());

        try {
            $existingRetries = Mage::getModel('full/retry')->getCollection()
                ->addfieldtofilter('order_id', $order->getId())
                ->addfieldtofilter('action', $action);

            // Only schedule a retry if one doesn't exist for this order/action combination.
            // If one already exists it will be updated in Riskified_Full_Model_Cron::retrySubmissions() so
            // there is no need to do anything here (eg update the existing retry).
            if ($existingRetries->count() == 0) {
                Mage::getModel('full/retry')
                    ->addData(array(
                        'order_id' => $order->getId(),
                        'action' => $action,
                        'updated_at' => Mage::getSingleton('core/date')->gmtDate()
                    ))
                    ->save();

                Mage::helper('full/log')->log("New retry scheduled successfully");
            }
        } catch (Exception $e) {
            Mage::helper('full/log')->logException($e);
        }
    }
}
