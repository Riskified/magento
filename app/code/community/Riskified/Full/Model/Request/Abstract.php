<?php

require_once
    Mage::getBaseDir('lib')
    . DS . 'riskified_php_sdk'
    . DS . 'src'
    . DS . 'Riskified'
    . DS . 'autoloader.php'
;

use Riskified\Common\Riskified;
use Riskified\Common\Signature;
use Riskified\Common\Validations;
use Riskified\OrderWebhook\Exception;

/**
 * Riskified Full abstract request model.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
abstract class Riskified_Full_Model_Request_Abstract
{
    protected $timeout = 10;
    protected $dnsCache = true;
    protected $userAgent;
    protected $endpoint;
    protected $useHttps = true;

    /**
     * Curl resource handle.
     *
     * @var resource
     */
    protected $resource;

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $helper = Mage::helper('full');

        $authToken = $helper->getAuthToken();
        $env = constant($helper->getConfigEnv());
        $shopDomain = $helper->getShopDomain();
        $sdkVersion = Riskified::VERSION;

        Riskified::init($shopDomain, $authToken, $env, Validations::SKIP);

        $this->resource = curl_init();

        $this->userAgent = 'riskified_php_sdk/' . Riskified::VERSION;

        $this->endpoint = sprintf(
            '%s://%s/api/',
            $this->useHttps === true ? 'https' : 'http',
            Riskified::getHostByEnv()
        );
    }

    /**
     * Object destructor.
     */
    public function __destruct()
    {
        curl_close($this->resource);
        unset(
            $this->resource,
            $this->crawler
        );
    }

    /**
     * Return curl resource handler.
     *
     * @return resource
     */
    protected function getResource()
    {
        return $this->resource;
    }

    /**
     * Endpoint getter.
     *
     * @return string
     * @throws \Exception
     */
    protected function getEndpoint()
    {
        return $this->endpoint . $this->getEndpointAction();
    }

    /**
     * Endpoint action setter.
     *
     * @return string
     */
    abstract protected function getEndpointAction();

    /**
     * Prepare and return request headers.
     *
     * @param string $jsonData Json data.
     *
     * @return array
     */
    protected function getHeaders($jsonData)
    {
        $signature = new Signature\HttpDataSignature();

        $headers = array(
            'Content-Type: application/json',
            'Content-Length: '. strlen($jsonData),
            $signature::SHOP_DOMAIN_HEADER_NAME.':' . Riskified::$domain,
            $signature::HMAC_HEADER_NAME.':' . $signature->calc_hmac($jsonData),
            'Accept: application/vnd.riskified.com; version='
            . Riskified::API_VERSION
        );

        return $headers;
    }

    /**
     * Prepare curl resource handler.
     *
     * @param string $jsonData Json data.
     *
     * @return Riskified_Full_Model_Request_Abstract
     */
    protected function prepareResource($jsonData)
    {
        $ch = $this->getResource();

        $options = array(
            CURLOPT_URL => $this->getEndpoint(),
            CURLOPT_HTTPHEADER => $this->getHeaders($jsonData),
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_DNS_USE_GLOBAL_CACHE => $this->dnsCache,
            CURLOPT_FAILONERROR => false,
        );
        curl_setopt_array($ch, $options);

        return $this;
    }

    /**
     * Send request.
     *
     * @param array $data Data array.
     *
     * @return array
     */
    abstract public function sendRequest(array $data);

    /**
     * Execute request.
     *
     * @param array $data Data array.
     *
     * @return string
     * @throws Exception\CurlException
     */
    protected function executeRequest(array $data)
    {
        $data = array_merge($data, $this->getClientDetails());
        $jsonData = json_encode($data);
        $this->prepareResource($jsonData);

        $ch = $this->getResource();

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            throw new Exception\CurlException(
                curl_error($ch),
                curl_errno($ch)
            );
        }

        $responseStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $this->processResponse($responseStatus, $responseBody);
    }

    protected function getClientDetails()
    {
        $clientDetails = array(
            'client_details' => array(
                'accept_language' => Mage::helper('full')->getAcceptLanguage(),
            ),
        );

        return $clientDetails;
    }

    /**
     * Process request response.
     *
     * @param int    $responseStatus Response status.
     * @param string $responseBody   Response body.
     *
     * @return array
     * @throws Exception\MalformedJsonException
     * @throws Exception\UnsuccessfulActionException
     */
    protected function processResponse($responseStatus, $responseBody)
    {
        $responseData = json_decode($responseBody);

        if (!$responseBody) {
            throw new Exception\MalformedJsonException(
                $responseBody,
                $responseStatus
            );
        }

        if ($responseStatus !== 200) {
            throw new Exception\UnsuccessfulActionException(
                $responseBody,
                $responseStatus
            );
        }

        return $responseData;
    }

    /**
     * Return current date time with timezone.
     *
     * @param string $time Time.
     *
     * @return string
     */
    protected function getDateTime($time = 'now')
    {
        $timezone = Mage::getStoreConfig('general/locale/timezone');
        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($time, $dateTimeZone);

        return $dateTime->format($dateTime::ATOM);
    }
}
