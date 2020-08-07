<?php

class Mamis_Shippit_Upgrade
{
    const OPTIONS_PREFIX = 'wc_settings_shippit_';

    public function run()
    {
        $dbVersion = get_option('wc_shippit_version', 0);

        // If an upgrade is not required, stop here
        if (version_compare(MAMIS_SHIPPIT_VERSION, $dbVersion, '<=')) {
            return;
        }

        $this->upgrade_1_3_0($dbVersion);
        $this->upgrade_1_4_0($dbVersion);
        $this->upgrade_1_5_5($dbVersion);

        // Mark the upgrade as complete
        $this->upgrade_complete($dbVersion);
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
                    update_option(self::OPTIONS_PREFIX . $key, $value);
                }
            }
        }

        // Migrate the shipping method settings to "legacy"
        update_option('woocommerce_mamis_shippit_legacy_settings', $oldOptions);

        // Update version
        update_option('wc_shippit_version', '1.3.0');
    }

    protected function upgrade_1_4_0($dbVersion)
    {
        // Avoid running this update if it's not required
        if (version_compare('1.4.0', $dbVersion, '<=')) {
            return;
        }

        // Migrate the core module settings to the new "Shippit Tab"
        $shippingMethodsStandard = get_option(self::OPTIONS_PREFIX . 'standard_shipping_methods');
        $shippingMethodsExpress = get_option(self::OPTIONS_PREFIX . 'express_shipping_methods');

        $shippingMethodsStandardMigrate = (array) $shippingMethodsStandard;
        $shippingMethodsExpressMigrate = (array) $shippingMethodsExpress;

        $zones = WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            $shippingMethods = $zone['shipping_methods'];

            foreach ($shippingMethods as $shippingMethod) {
                // If the standard shipping method is currently mapped,
                // update the mapping to include zone mapped methods
                if (in_array($shippingMethod->id, $shippingMethodsStandard)) {
                    // determine the mapping key
                    $shippingMethodKey = $shippingMethod->id . ':' . $shippingMethod->instance_id;
                    $shippingMethodsStandardMigrate[] = $shippingMethodKey;
                }

                // If the standard shipping method is currently mapped,
                // update the mapping to include zone mapped methods
                if (in_array($shippingMethod->id, $shippingMethodsExpress)) {
                    // determine the mapping key
                    $shippingMethodKey = $shippingMethod->id . ':' . $shippingMethod->instance_id;
                    $shippingMethodsExpressMigrate[] = $shippingMethodKey;
                }
            }
        }

        update_option(self::OPTIONS_PREFIX . 'standard_shipping_methods', $shippingMethodsStandardMigrate);
        update_option(self::OPTIONS_PREFIX . 'express_shipping_methods', $shippingMethodsExpressMigrate);

        // Update version
        update_option('wc_shippit_version', '1.4.0');
    }

    protected function upgrade_1_5_5($dbVersion)
    {
        // Avoid running this update if it's not required
        if (version_compare('1.5.5', $dbVersion, '<=')) {
            return;
        }

        $sendAllOrders = get_option(self::OPTIONS_PREFIX . 'send_all_orders');

        if ($sendAllOrders == 'yes') {
            $value = 'all';
        }
        else {
            $value = 'no';
        }

        update_option(self::OPTIONS_PREFIX . 'auto_sync_orders', $value);

        // Update version
        update_option('wc_shippit_version', '1.5.5');
    }

    protected function upgrade_complete($dbVersion)
    {
        // Update DB version to latest code release version
        update_option('wc_shippit_version', MAMIS_SHIPPIT_VERSION);
    }
}
