<?php

class Upgrade_Data
{
	const OLD_OPTIONS_PREFIX = 'woocommerce_mamis_shippit_';
	const NEW_OPTIONS_PREFIX = 'wc_settings_shippit_global_';
	public function upgrade()
	{
		$old_version = get_option('wc_shippit_version', 0);
		if (version_compare($old_version, MAMIS_SHIPPIT_VERSION) < 0) {
            //code to upgrade to 1.2.12
            $this->upgrade_1212($installer);
        }
        else
        {
        	return;
        }
	}

	protected function upgrade_1212()
	{
		//Add/update version
		update_option('wc_shippit_version', MAMIS_SHIPPIT_VERSION);

		$old_options = get_option('woocommerce_mamis_shippit_settings');

		$options = [
						'enabled',
						'api_key',
						'debug',
						'environment',
						'send_all_orders',
						'standard_shipping_methods',
						'express_shipping_methods',
						'international_shipping_methods'
					];

		if ($old_options != 0) {
			foreach ($old_options as $key => $value) {
				if(in_array($key, $options))
				update_option(self::NEW_OPTIONS_PREFIX . $key, $value);
			}
		}
	}
}
