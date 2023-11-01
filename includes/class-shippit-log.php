<?php

/**
* Mamis - https://www.mamis.com.au
* Copyright © Mamis 2023-present. All rights reserved.
* See https://www.mamis.com.au/license
*/

class Mamis_Shippit_Log
{
    /**
     * @var WC_Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $context = [];

    public function __construct(array $context = [])
    {
        $handlers = [];

        // Ensure to pass through any handlers that may have been defined by users
        if (defined('WC_LOG_HANDLER')) {
            $logHandler = WC_LOG_HANDLER;

            $handlers[] = new $logHandler();
        }
        else {
            $handlers[] = new Mamis_Shippit_Log_Handler();
        }

        $this->logger = new WC_Logger(
            $handlers,
            // If debug is enabled, log all events
            // otherwise, only log for levels of warning and above
            (
                !defined(WC_LOG_THRESHOLD)
                && get_option('wc_settings_shippit_debug') === 'yes'
                    ? WC_Log_Levels::DEBUG
                    : WC_Log_Levels::ERROR
            )
        );

        $this->context = array_merge(
            [
                'source' => 'shippit',
            ],
            $context
        );
    }

    /**
     * Adds an emergency level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = [])
    {
        $this->logger->emergency(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Adds an alert level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = [])
    {
        $this->logger->alert(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Adds an critical level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = [])
    {
        $this->logger->critical(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Adds an error level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = [])
    {
        $this->logger->error(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Adds an warning level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = [])
    {
        $this->logger->warning(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Adds an notice level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = [])
    {
        $this->logger->notice(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Adds an info level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = [])
    {
        $this->logger->info(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Adds an debug level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, $context = [])
    {
        $this->logger->debug(
            $message,
            array_merge(
                $context,
                $this->context
            )
        );
    }

    /**
     * Log an exception
     *
     * @param Exception $exception
     * @param array $context
     * @return void
     */
    public function exception(Exception $exception, array $context = [])
    {
        $this->error(
            $exception->getMessage(),
            $context
        );
    }
}