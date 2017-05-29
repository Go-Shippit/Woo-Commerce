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
    public function add($errorType, $message, $metaData = null, $severity = 'info')
    {
        // If debug mode is active, log all info serverities, otherwise log only errors
        if (get_option('wc_settings_shippit_debug') == 'yes' || $severity == 'error') {
            error_log('-- ' . $errorType . ' --');
            error_log($message);

            if (!is_null($metaData)) {
                error_log(json_encode($metaData));
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
        error_log($exception->getMessage());
    }
}