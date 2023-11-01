<?php

/**
 * Mamis - https://www.mamis.com.au
 * Copyright © Mamis 2023-present. All rights reserved.
 * See https://www.mamis.com.au/license
 */

class Mamis_Shippit_Api
{
    const API_ENDPOINT_LIVE = 'https://app.shippit.com/api/3';
    const API_ENDPOINT_STAGING = 'https://app.staging.shippit.com/api/3';
    const API_TIMEOUT = 30;

    protected $apiKey = null;
    protected $debug = false;
    protected $environment;

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
        $this->log = new Mamis_Shippit_Log(['area' => 'api']);

        $this->apiKey = (empty($apiKey) ? get_option('wc_settings_shippit_api_key') : $apiKey);
        $this->environment = (empty($environment) ? get_option('wc_settings_shippit_environment') : $environment);
        $this->debug = (empty($debug) ? get_option('wc_settings_shippit_debug') : $debug);
    }

    /**
     * Retrieve the Shippit Merchant API key for the HTTP Client
     *
     * @return string
     */
    private function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Set the Shippit Merchant API Key for the HTTP Client
     *
     * @param string $apiKey
     * @return this
     */
    public function setApiKey(string $apiKey): string
    {
        return $this->apiKey = $apiKey;
    }

    /**
     * Set the environment for the HTTP client
     *
     * @param string $environment
     * @return this
     */
    public function setEnvironment(string $environment): string
    {
        return $this->environment = $environment;
    }

    /**
     * Retrieve the Shippit API url, based on the currently set environment
     *
     * @param string $path
     * @return string
     */
    public function getApiUrl(string $path): string
    {
        if ($this->environment == 'sandbox' ) {
            return self::API_ENDPOINT_STAGING . '/' . $path;
        }

        return self::API_ENDPOINT_LIVE . '/' . $path;
    }

    /**
     * Retrieve the HTTP Client arguments
     *
     * @param string $requestMethod
     * @param array|null $requestData
     * @return void
     */
    public function getApiArgs(string $requestMethod, $requestData = null)
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

    /**
     * Perform a remote HTTP API call using the HTTP Client
     *
     * @param string $requestMethod
     * @param string $uri
     * @param array|null $requestData
     * @param boolean $throwExceptionOnResponseError
     * @return object|bool
     */
    public function call(string $requestMethod, string $uri, $requestData = null, $throwExceptionOnResponseError = true)
    {
        $url = $this->getApiUrl($uri);
        $args = $this->getApiArgs($requestMethod, $requestData);

        try {
            $response = wp_remote_request(
                $url,
                $args
            );

            $responseCode = wp_remote_retrieve_response_code($response);

            if ($throwExceptionOnResponseError) {
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
     * Retrieves the user agent to be used for all outbound API calls for the HTTP Client
     *
     * @return void
     */
    public function getUserAgent(): string
    {
        return sprintf(
            'Shippit_WooCommerce/%s WooCommerce/%s PHP/%s',
            MAMIS_SHIPPIT_VERSION,
            WC()->version,
            phpversion()
        );
    }

    /**
     * Perform a Quote with the Shippit API
     *
     * @param array $quoteData
     * @return object|bool
     */
    public function getQuote(array $quoteData)
    {
        $requestData = array(
            'quote' => $quoteData
        );

        try {
            $quote = $this->call('POST', 'quotes', $requestData);
        }
        catch (Exception $e) {
            return false;
        }

        return $quote->response;
    }

    /**
     * Create an order with the Shippit API
     *
     * @param array $orderData
     * @return object|bool
     */
    public function createOrder($orderData)
    {
        $requestData = array(
            'order' => $orderData
        );

        try {
            $order = $this->call('POST', 'orders', $requestData, false);
        }
        catch (Exception $e) {
            return false;
        }

        return $order->response;
    }

    /**
     * Retrieve the merchant details
     *
     * @return void
     */
    public function getMerchant()
    {
        return $this->call('GET', 'merchant');
    }

    /**
     * Update the merchant
     *
     * @param array $merchantData
     * @return object|bool
     */
    public function updateMerchant(array $merchantData)
    {
        $requestData = array(
            'merchant' => $merchantData
        );

        return $this->call('PUT', 'merchant', $requestData);
    }
}
