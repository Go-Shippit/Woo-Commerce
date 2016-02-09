<?php
/**
*  Mamis.IT
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the EULA
*  that is available through the world-wide-web at this URL:
*  http://www.mamis.com.au/licencing
*
*  @category   Mamis
*  @copyright  Copyright (c) 2015 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.mamis.com.au/licencing
*/

class Mamis_Shippit_Api
{
    const API_ENDPOINT = 'http://goshippit.herokuapp.com/api/3';
    const API_TIMEOUT = 5;
    const API_USER_AGENT = 'Mamis_Shippit for WooCommerce';

    private $apiKey = null;
    public $debug = false;

    public function __construct()
    {
        $this->settings = new Mamis_Shippit_Settings();
        $this->log = new Mamis_Shippit_Log();
        $this->apiKey = $this->settings->getSetting('api_key');
        $this->debug = $this->settings->getSetting('debug');
    }

    private function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey)
    {
        return $this->apiKey = $apiKey;
    }

    public function getApiUrl($path, $apiKey)
    {
        return self::API_ENDPOINT . '/' . $path . '?auth_token=' . $apiKey;
    }

    public function getApiArgs($requestData, $requestMethod)
    {
        $apiArgs = array(
            'blocking'     => true,
            'method'       => $requestMethod,
            'timeout'      => self::API_TIMEOUT,
            'user-agent'   => self::API_USER_AGENT,
            'headers'      => array(
                'content-type' => 'application/json',
            ),
        );

        if (!empty($requestData)) {
            $apiArgs['body'] = json_encode($requestData);
        }

        return $apiArgs;
    }

    public function call($uri, $requestData, $requestMethod = 'POST', $exceptionOnResponseError = true)
    {
        $apiKey = $this->getApiKey();

        $url = $this->getApiUrl($uri, $apiKey);
        $args = $this->getApiArgs($requestData, $requestMethod);

        $this->log->add(
            'SHIPPIT - API REQUEST',
            $uri,
            array(
                'url' => $url,
                'requestData' => $requestData
            )
        );

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
            $this->log->exception($e);

            return false;
        }

        $jsonResponseData = wp_remote_retrieve_body($response);

        $responseData = json_decode($jsonResponseData);

        $this->log->add(
            'SHIPPIT - API RESPONSE',
            $uri,
            array(
                'url' => $url,
                'requestData' => $responseData
            )
        );

        return $responseData;
    }

    public function getQuote($quoteData)
    {
        $requestData = array(
            'quote' => $quoteData
        );

        return $this->call('quotes', $requestData)
            ->response;
    }

    public function sendOrder($orderData)
    {
        $requestData = array(
            'order' => $orderData
        );
        
        return $this->call('orders', $requestData)
            ->response;
    }

    public function getMerchant()
    {
        return $this->call('merchant', null, $requestMethod = 'GET', false);
    }

    public function putMerchant($merchantData)
    {
        $requestData = array(
            'merchant' => $merchantData
        );

        return $this->call('merchant', $requestData, $requestMethod = 'PUT');
    }
}