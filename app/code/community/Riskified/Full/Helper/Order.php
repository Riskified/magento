<?php

require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'riskified_php_sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Riskified' . DIRECTORY_SEPARATOR . 'autoloader.php');
use Riskified\Common\Riskified;
use Riskified\Common\Signature;
use Riskified\Common\Validations;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;
use Riskified\DecisionNotification\Model\Notification as DecisionNotification;


class Riskified_Full_Helper_Order extends Mage_Core_Helper_Abstract
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_SUBMIT = 'submit';
    const ACTION_CANCEL = 'cancel';
    const ACTION_REFUND = 'refund';
    const ACTION_CHECKOUT_CREATE = 'checkout_create';
    const ACTION_CHECKOUT_DENIED = 'checkout_denied';

    private $_customer = array();
    protected $requestData = array();

    public function __construct()
    {
        $this->initSdk();
    }

    /**
     * Update the merchan't settings
     * @param settings hash
     * @return stdClass
     * @throws Exception
     */
    public function updateMerchantSettings($settings)
    {
        $transport = $this->getTransport();
        Mage::helper('full/log')->log('updateMerchantSettings');

        try {
            $response = $transport->updateMerchantSettings($settings);

            Mage::helper('full/log')->log('Merchant Settings posted successfully');
        } catch (\Riskified\OrderWebhook\Exception\UnsuccessfulActionException $uae) {
            if ($uae->statusCode == '401') {
                Mage::helper('full/log')->logException($uae);
                Mage::getSingleton('adminhtml/session')->addError('Make sure you have the correct Auth token as it appears in Riskified advanced settings.');
            }
            throw $uae;
        } catch (\Riskified\OrderWebhook\Exception\CurlException $curlException) {
            Mage::helper('full/log')->logException($curlException);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $curlException->getMessage());

            throw $curlException;
        } catch (Exception $e) {
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
    public function postOrder($order, $action)
    {
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
                    $order->setIsSentToRiskfied(1);
                    $order->save();
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
                case self::ACTION_REFUND:
                    $orderForTransport = $this->getOrderRefund($order);
                    $response = $transport->refundOrder($orderForTransport);
                    break;
                case self::ACTION_CHECKOUT_CREATE:
                    $checkoutForTransport = new Model\Checkout($order);
                    $response = $transport->createCheckout($checkoutForTransport);
                    Mage::log(var_export($this->requestData, true), null, 'riskified-request-data.log');
                    break;
                case self::ACTION_CHECKOUT_DENIED:
                    $checkoutForTransport = new Model\Checkout($order);
                    $response = $transport->deniedCheckout($checkoutForTransport);
                    Mage::log(var_export($this->requestData, true), null, 'riskified-request-data.log');
                    break;
            }

            Mage::helper('full/log')->log('Order posted successfully - invoking post order event');

            $eventData['response'] = $response;

            Mage::dispatchEvent(
                'riskified_full_post_order_success',
                $eventData
            );
        } catch (\Riskified\OrderWebhook\Exception\CurlException $curlException) {
            Mage::helper('full/log')->logException($curlException);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $curlException->getMessage());

            $this->updateOrder($order, 'error', null, 'Error transferring order data to Riskified');
            $this->scheduleSubmissionRetry($order, $action);

            Mage::dispatchEvent(
                'riskified_full_post_order_error',
                $eventData
            );

            throw $curlException;
        }
        catch (\Riskified\OrderWebhook\Exception\MalformedJsonException $e) {
            if (strstr($e->getMessage(), "504") && strstr($e->getMessage(), "Status Code:")) {
                $this->updateOrder($order, 'error', null, 'Error transferring order data to Riskified');
                $this->scheduleSubmissionRetry($order, $action);
            }
            Mage::dispatchEvent(
                'riskified_decider_post_order_error',
                $eventData
            );
            throw $e;
        }
        catch (Exception $e) {
            Mage::log(var_export($this->requestData, true), null, 'riskified-request-data.log');
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

    public function postHistoricalOrders($models)
    {
        $orders = array();
        foreach ($models as $model) {
            $order = $this->getOrder($model);
            Mage::getModel('full/sent')->setOrderId($model->getId())->save();
            $orders[] = $order;
        }

        $msgs = $this->getTransport()->sendHistoricalOrders($orders);
        return "Successfully uploaded " . count($msgs) . " orders." . PHP_EOL;
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
    public function updateOrder($order, $status, $oldStatus, $description)
    {
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

    public function getRiskifiedDomain()
    {
        return Riskified::getHostByEnv();
    }

    public function getRiskifiedApp()
    {
        return str_replace("wh", "app", $this->getRiskifiedDomain());
    }

    public function parseRequest($request)
    {
        $header_name = Signature\HttpDataSignature::HMAC_HEADER_NAME;
        $headers = array($header_name => $request->getHeader($header_name));
        $body = $request->getRawBody();
        Mage::helper('full/log')->log("Received new notification request with headers: " . json_encode($headers) . " and body: $body. Trying to parse.");
        return new DecisionNotification(new Signature\HttpDataSignature(), $headers, $body);
    }

    public function getOrderOrigId($order)
    {
        if (!$order) {
            return null;
        }
        return $order->getId() . '_' . $order->getIncrementId();
    }

    // duplicate of method in ResponseController.php for now
    private function loadOrderByOrigId($full_orig_id)
    {
        if (!$full_orig_id) {
            return null;
        }
        $magento_ids = explode("_", $full_orig_id);
        $order_id = $magento_ids[0];
        $increment_id = $magento_ids[1];
        if ($order_id && $increment_id) {
            return Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('entity_id', $order_id)
                ->addFieldToFilter('increment_id', $increment_id)
                ->getFirstItem();
        }
        return Mage::getModel('sales/order')->load($order_id);
    }

    private $version;

    private function initSdk()
    {
        $helper = Mage::helper('full');
        $authToken = $helper->getAuthToken();
        $env = constant($helper->getConfigEnv());
        $shopDomain = $helper->getShopDomain();
        $this->version = $helper->getExtensionVersion();
        $sdkVersion = Riskified::VERSION;

        Mage::helper('full/log')->log("Riskified initSdk() - shop: $shopDomain, env: $env, token: $authToken, extension_version: $this->version, sdk_version: $sdkVersion");
        Riskified::init($shopDomain, $authToken, $env, Validations::SKIP);
    }

    private function getHeaders()
    {
        return array('headers' => array('X_RISKIFIED_VERSION:' . $this->version));
    }

    private function getTransport()
    {
        $transport = new Transport\CurlTransport(new Signature\HttpDataSignature());
        $transport->timeout = 15;

        $transport->requestData = &$this->requestData;

        return $transport;
    }

    private function getOrderRefund($model)
    {
        $order = $this->loadOrderByOrigId($model['id']);
        $orig_id = $this->getOrderOrigId($order);
        if (!$orig_id) {
            return null;
        }

        $orderRefund = new Model\Refund(array_filter(array(
            'id' => $orig_id,
            'refunds' => $model['refunds']
        )));

        Mage::helper('full/log')->log("getOrderRefund(): " . PHP_EOL . json_encode(json_decode($orderRefund->toJson())));

        return $orderRefund;
    }

    private function getOrderCancellation($model)
    {
        $orderCancellation = new Model\OrderCancellation(array_filter(array(
            'id' => $this->getOrderOrigId($model),
            'cancelled_at' => $this->formatDateAsIso8601($this->getCancelledAt($model)),
            'cancel_reason' => 'Cancelled by merchant'
        )));

        Mage::helper('full/log')->log("getOrderCancellation(): " . PHP_EOL . json_encode(json_decode($orderCancellation->toJson())));

        return $orderCancellation;
    }

    private function getOrder($model)
    {
        $gateway = 'unavailable';
        $currentStore = Mage::app()->getStore();

        if ($model->getPayment()) {
            $gateway = $model->getPayment()->getMethod();
        }

        $order_array = array(
            'id' => $this->getOrderOrigId($model),
            'checkout_id' => $model->getQuoteId(),
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
            'discount_codes' => $this->getDiscountCodes($model),
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

        if ($currentStore->isAdmin()) {
            $order_array['cart_token'] = null;
        }

        $order = new Model\Order(array_filter($order_array, 'strlen'));

        $order->customer = $this->getCustomer($model);
        $order->shipping_address = $this->getShippingAddress($model);
        $order->billing_address = $this->getBillingAddress($model);

        $orderPaymentHelper = Mage::helper('full/order_payment');
        $order->payment_details = $orderPaymentHelper->getPaymentDetails($model);
        $order->line_items = $this->getLineItems($model);
        $order->shipping_lines = $this->getShippingLines($model);

        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            $order->client_details = $this->getClientDetails($model);
        }

        Mage::helper('full/log')->log("getOrder(): " . PHP_EOL . json_encode(json_decode($order->toJson())));

        return $order;
    }

    private function getCustomer($model)
    {
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
            $customer_details = $this->_getCustomerObject($model->getCustomerId());
            $customer_props['created_at'] = $this->formatDateAsIso8601($customer_details->getCreatedAt());
            $customer_props['updated_at'] = $this->formatDateAsIso8601($customer_details->getUpdatedAt());

            try {
                $customer_data = Mage::helper('full/customer_order')->getCustomerOrders($model->getCustomerId());
                $customer_props['total_spent'] = $customer_data['total_spent'];
                $customer_props['orders_count'] = $customer_data['orders_count'];
                $customer_props['last_order_id'] = $customer_data['last_order_id'];
            } catch (Exception $e) {
                Mage::helper('full/log')->logException($e);
                Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $e->getMessage());
            }
        }

        return new Model\Customer(array_filter($customer_props, 'strlen'));
    }

    private function _getCustomerObject($customer_id) {
        if(!isset($this->_customer[$customer_id])) {
            $collection = Mage::getModel('customer/customer')->getCollection();
            $collection->addAttributeToFilter('entity_id', $customer_id);
            $this->_customer[$customer_id] = $collection->getFirstItem();
        }

        return $this->_customer[$customer_id];
    }

    private function getShippingAddress($model)
    {
        $mageAddr = $model->getShippingAddress();
        return $this->getAddress($mageAddr);
    }

    private function getBillingAddress($model)
    {
        $mageAddr = $model->getBillingAddress();
        return $this->getAddress($mageAddr);
    }

    private function getLineItems($model)
    {
        $lineItems = array();
        foreach ($model->getAllVisibleItems() as $key => $val) {
            $prodType = null;
            $category = null;
            $subCategories = null;
            $brand = null;
            $product = $val->getProduct();
            if ($product) {
                $prodType = $val->getProduct()->getTypeId();
                $categoryIds = $product->getCategoryIds();
                foreach ($categoryIds as $categoryId) {
                    $cat = Mage::getModel('catalog/category')->load($categoryId);
                    $catName = $cat->getName();
                    if (!empty($catName)) {
                        if (empty($category)) {
                            $category = $catName;
                        } else if (empty($subCategories)) {
                            $subCategories = $catName;
                        } else {
                            $subCategories = $subCategories . '|' . $catName;
                        }

                    }
                }


                if ($product->getManufacturer()) {
                    $brand = $product->getAttributeText('manufacturer');
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
                'brand' => $brand,
                //'sub_category' => $subCategories
            ), 'strlen'));
        }

        return $lineItems;
    }

    private function getShippingLines($model)
    {
        return new Model\ShippingLine(array_filter(array(
            'price' => $model->getShippingAmount(),
            'title' => $model->getShippingDescription(),
            'code' => $model->getShippingMethod()
        ), 'strlen'));
    }

    private function getClientDetails($model)
    {
        return new Model\ClientDetails(array_filter(array(
            'accept_language' => Mage::helper('full')->getAcceptLanguage(),
            //'browser_ip' => $this->getRemoteIp($model),
            'user_agent' => Mage::helper('core/http')->getHttpUserAgent()
        ), 'strlen'));
    }

    private function getAddress($address)
    {
        if (!$address) {
            return null;
        }

        $street = $address->getStreet();
        $address_1 = (!is_null($street) && array_key_exists('0', $street)) ? $street['0'] : null;
        $address_2 = (!is_null($street) && array_key_exists('1', $street)) ? $street['1'] : null;

        $addrArray = array_filter(array(
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

        if (!$addrArray) {
            return null;
        }
        return new Model\Address($addrArray);
    }

    private function getDiscountCodes($model)
    {
        $code = $model->getDiscountDescription();
        $amount = $model->getDiscountAmount();
        if ($amount && $code)
            return new Model\DiscountCode(array_filter(array(
                'code' => $code,
                'amount' => $amount
            )));
        return null;
    }

    private function getCancelledAt($model)
    {
        $commentCollection = $model->getStatusHistoryCollection();
        foreach ($commentCollection as $comment) {
            if ($comment->getStatus() == Mage_Sales_Model_Order::STATE_CANCELED) {
                return 'now';
            }
        }
        return null;
    }

    private function formatDateAsIso8601($dateStr)
    {
        return ($dateStr == NULL) ? NULL : date('c', strtotime($dateStr));
    }

    private function getRemoteIp($model)
    {
        Mage::helper('full/log')->log("remote ip: " . $model->getRemoteIp() . ", x-forwarded-ip: " . $model->getXForwardedFor());

        $forwardedIp = $model->getXForwardedFor();
        $forwardeds = preg_split("/,/", $forwardedIp, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($forwardeds)) {
            return trim($forwardeds[0]);
        }
        $remoteIp = $model->getRemoteIp();
        $remotes = preg_split("/,/", $remoteIp, -1, PREG_SPLIT_NO_EMPTY);
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
            if ($existingRetries->getSize() == 0) {
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
