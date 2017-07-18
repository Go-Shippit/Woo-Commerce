<?php

class Mamis_Shippit_Upgrade
{
    const NEW_OPTIONS_PREFIX = 'wc_settings_shippit_';

    public function run()
    {
        $dbVersion = get_option('wc_shippit_version', 0);

        // If an upgrade is not required, stop here
        if (version_compare(MAMIS_SHIPPIT_VERSION, $dbVersion, '<')) {
            return;
        }

        // code to upgrade to 1.3.0
        $this->upgrade_1_3_0($dbVersion);
    }

    protected function upgrade_1_3_0($dbVersion)
    {
        // Avoid running this update if it's not required
        if (version_compare('1.3.0', $dbVersion, '<=')) {
            return;
        }

        // Migrate the core module settings to the new "Shippit Tab"
        $oldOptions = get_option('woocommerce_mamis_shippit_settings');

        $newOptions = array(
            'enabled',
            'api_key',
            'debug',
            'environment',
            'send_all_orders',
            'standard_shipping_methods',
            'express_shipping_methods',
            'international_shipping_methods'
        );

        if (!empty($oldOptions)) {
            foreach ($oldOptions as $key => $value) {
                if (in_array($key, $newOptions)) {
                    update_option(self::NEW_OPTIONS_PREFIX . $key, $value);
                }
            }
        }

        // Migrate the shipping method settings to "legacy"
        update_option('woocommerce_mamis_shippit_legacy_settings', $oldOptions);

        // Update version
        update_option('wc_shippit_version', '1.3.0');
    }
}
