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

class Mamis_Shippit_Log
{
    public function add($errorType, $message = null, $metaData = null, $severity = 'info')
    {
        // If debug mode is active, log all info severities, otherwise log only errors
        if (get_option('wc_settings_shippit_debug') == 'yes' || $severity == 'error') {
            
            // Updated to use WC_Logger
            $logger = wc_get_logger();
            $loggerContext = array( 'source' => 'shippit' );

            if ($severity == 'error') {
                $logger->error('-- ' . $errorType . ' --', $loggerContext );
            
                if (!is_null($message)) {
                    $logger->error($message, $loggerContext );
                }
    
                if (!is_null($metaData)) {
                    $logger->error(json_encode($metaData), $loggerContext );
                }
            } else {
                $logger->info('-- ' . $errorType . ' --', $loggerContext );
            
                if (!is_null($message)) {
                    $logger->info($message, $loggerContext );
                }
    
                if (!is_null($metaData)) {
                    $logger->info(json_encode($metaData), $loggerContext );
                }
            }

        }
    }

    /**
    * add function.
    *
    * Uses the build in logging method in WooCommerce.
    * Logs are available inside the System status tab
    *
    * @access public
    * @param  string|array|object
    * @return void
    */
    public function exception($exception)
    {
                    // Updated to use WC_Logger
                    $logger = wc_get_logger();
                    $loggerContext = array( 'source' => 'shippit' );
                    $logger->error($exception->getMessage());
    }
}