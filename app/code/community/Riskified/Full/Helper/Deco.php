<?php

require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'riskified_php_sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Riskified' . DIRECTORY_SEPARATOR . 'autoloader.php');
use Riskified\Common\Riskified;
use Riskified\Common\Signature;
use Riskified\Common\Validations;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

class Riskified_Full_Helper_Deco extends Mage_Core_Helper_Abstract
{
    const ACTION_ELIGIBLE = 'eligible';
    const ACTION_OPT_IN = 'opt_in';
    const STATUS_ELIGIBLE = 'eligible';
    const STATUS_NOT_ELIGIBLE = 'not_eligible';

    private $_customer = array();
    protected $requestData = array();

    public function __construct()
    {
        $this->initSdk();
    }

    /**
     * Submit an order to Riskified.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $action - one of self::ACTION_*
     * @return stdClass
     * @throws Exception
     */
    public function post($quote, $action)
    {
        $transport = $this->getTransport();
        $headers = $this->getHeaders();

        Mage::helper('full/log')->log('Deco post ' . serialize($headers) . ' - ' . $action);

        $eventData = array(
            'order' => $quote,
            'action' => $action
        );

        try {
            switch ($action) {
                case self::ACTION_ELIGIBLE:
                    $orderForTransport = $this->load($quote);
                    $response = $transport->isEligible($orderForTransport);
                    break;

                case self::ACTION_OPT_IN:
                    $orderForTransport = $this->load($quote);
                    $response = $transport->optIn($orderForTransport);
                    break;
            }

            Mage::helper('full/log')->log('Deco posted successfully - invoking post order event');
        } catch (\Riskified\OrderWebhook\Exception\CurlException $curlException) {
            Mage::helper('full/log')->logException($curlException);

            throw $curlException;
        } catch (\Riskified\OrderWebhook\Exception\MalformedJsonException $e) {
            throw $e;
        } catch (Exception $e) {
            Mage::helper('full/log')->logException($e);
            Mage::getSingleton('adminhtml/session')->addError('Riskified extension: ' . $e->getMessage());
            throw $e;
        }

        return $response;
    }

    /**
     * @param $quote
     * @return Model\Order
     */
    private function load($quote)
    {
        $order_array = array(
            'id' => $quote->getId()
        );

        $order = new Model\Checkout(array_filter($order_array, 'strlen'));

        return $order;
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
        $transport = new Transport\CurlTransport(new Signature\HttpDataSignature(), Mage::helper('full')->getDecoEnv());
        $transport->timeout = 15;

        $transport->requestData = &$this->requestData;

        return $transport;
    }
}
