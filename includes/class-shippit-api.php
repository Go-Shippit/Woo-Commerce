<?php
/**
 * Mamis.IT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is available through the world-wide-web at this URL:
 * http://www.mamis.com.au/licencing
 *
 * @category   Mamis
 * @copyright  Copyright (c) 2016 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.mamis.com.au/licencing
 */

class Mamis_Shippit_Api
{
    const API_ENDPOINT_LIVE = 'https://www.shippit.com/api/3';
    const API_ENDPOINT_STAGING = 'https://staging.shippit.com/api/3';
    const API_TIMEOUT = 30;

    private $apiKey = null;
    public $debug = false;

    /**
     * @var Mamis_Shippit_Log
     */
    protected $log;

    /**
     * Create a Shippit API Client
     *
     * @param string $apiKey        (Optional) The API key to be used for requests
     * @param string $environment   (Optional) The environment to be used for requests
     * @param bool $debug           (Optional) The debug mode of the client
     */
    public function __construct($apiKey = null, $environment = null, $debug = null)
    {
        $this->settings = new Mamis_Shippit_Settings();
        $this->log = new Mamis_Shippit_Log(['area' => 'api']);

        $this->apiKey = (empty($apiKey) ? get_option('wc_settings_shippit_api_key') : $apiKey);
        $this->environment = (empty($environment) ? get_option('wc_settings_shippit_environment') : $environment);
        $this->debug = (empty($debug) ? get_option('wc_settings_shippit_debug') : $debug);
    }

    private function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey)
    {
        return $this->apiKey = $apiKey;
    }

    public function setEnvironment($environment)
    {
        return $this->environment = $environment;
    }

    public function getApiUrl($path)
    {
        if ( $this->environment == 'sandbox' ) {
            return self::API_ENDPOINT_STAGING . '/' . $path;
        }
        else {
            return self::API_ENDPOINT_LIVE . '/' . $path;
        }
    }

    public function getApiArgs($requestData, $requestMethod)
    {
        $apiArgs = array(
            'blocking'     => true,
            'method'       => $requestMethod,
            'timeout'      => self::API_TIMEOUT,
            'user-agent'   => $this->getUserAgent(),
            'headers'      => array(
                'content-type' => 'application/json',
                'Authorization' => sprintf(
                    'Bearer %s',
                    $this->getApiKey()
                ),
            ),
        );

        if (!empty($requestData)) {
            $apiArgs['body'] = json_encode($requestData);
        }

        return $apiArgs;
    }

    public function call($uri, $requestData, $requestMethod = 'POST', $exceptionOnResponseError = true)
    {
        $url = $this->getApiUrl($uri);
        $args = $this->getApiArgs($requestData, $requestMethod);

        try {
            $response = wp_remote_request(
                $url,
                $args
            );

            $responseCode = wp_remote_retrieve_response_code($response);

            if ($exceptionOnResponseError) {
                if ($responseCode < 200 ||
                    $responseCode > 300) {
                    throw new Exception('An API Request Error Occured');
                }
            }
        }
        catch (Exception $e) {
            $this->log->exception(
                $e,
                [
                    'url' => $url,
                    'request' => $requestData,
                    'response' => wp_remote_retrieve_body($response)
                ]
            );

            return false;
        }

        $jsonResponseData = wp_remote_retrieve_body($response);

        $responseData = json_decode($jsonResponseData);

        $this->log->debug(
            'Shippit API Request',
            [
                'url' => $url,
                'request' => $requestData,
                'response' => $responseData,
            ]
        );

        return $responseData;
    }

    /**
     * Retrieves the user agent for outbound API calls
     */
    public function getUserAgent()
    {
        return sprintf(
            'Shippit_WooCommerce/%s WooCommerce/%s PHP/%s',
            MAMIS_SHIPPIT_VERSION,
            WC()->version,
            phpversion()
        );
    }

    public function getQuote($quoteData)
    {
        $requestData = array(
            'quote' => $quoteData
        );

        $quote = $this->call('quotes', $requestData);

        if (!$quote) {
            return false;
        }

        return $quote->response;
    }

    public function sendOrder($orderData)
    {
        $requestData = array(
            'order' => $orderData
        );

        $order = $this->call('orders', $requestData, 'POST', false);

        if (!$order) {
            return false;
        }

        return $order->response;
    }

    public function getMerchant()
    {
        return $this->call('merchant', null, 'GET', false);
    }

    public function putMerchant($merchantData)
    {
        $requestData = array(
            'merchant' => $merchantData
        );

        return $this->call('merchant', $requestData, 'PUT');
    }
}