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

class Mamis_Shippit_Log
{
    public $s;
    public $bugsnag;

    public function __construct()
    {
        $this->s = new Mamis_Shippit_Settings();
        $this->bugsnag = new Bugsnag_Client('b2873ea2ae95a3c9f2cb63ca1557abb5');
        $this->bugnsag->setAppVersion(MAMIS_SHIPPIT_VERSION);
    }
    
    public function add($errorType, $message, $metaData = null, $severity = 'info')
    {
        // If debug mode is active, log all info serverities, otherwise log only errors
        if ($this->s->getSetting('debug') == 'yes' || $severity == 'error') {
            error_log('-- ' . $errorType . ' --');
            error_log($message);

            if (!is_null($metaData)) {
                error_log(json_encode($metaData));
            }

            $this->bugsnag->notifyError($errorType, $message, $metaData, $severity);
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
        $this->bugsnag->notifyException($exception);
    }
}