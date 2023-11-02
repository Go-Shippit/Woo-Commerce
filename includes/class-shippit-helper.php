<?php

/**
* Mamis - https://www.mamis.com.au
* Copyright © Mamis 2023-present. All rights reserved.
* See https://www.mamis.com.au/license
*/

class Mamis_Shippit_Helper
{
    /**
     * Convert the dimension to a different unit size
     *
     * based on https://gist.github.com/mbrennan-afa/1812521
     *
     * @param  float $dimension  The dimension to be converted
     * @param  string $unit      The unit to be converted to
     * @return float             The converted dimension
     */
    public function convertDimension($dimension, $unit = 'm'): float
    {
        $dimensionCurrentUnit = get_option('woocommerce_dimension_unit');
        $dimensionCurrentUnit = strtolower($dimensionCurrentUnit);
        $unit = strtolower($unit);

        if ($dimensionCurrentUnit !== $unit) {
            // Unify all units to cm first
            switch ($dimensionCurrentUnit) {
                case 'inch':
                    $dimension *= 2.54;
                    break;
                case 'm':
                    $dimension *= 100;
                    break;
                case 'mm':
                    $dimension *= 0.1;
                    break;
            }

            // Output desired unit
            switch ($unit) {
                case 'inch':
                    $dimension *= 0.3937;
                    break;
                case 'm':
                    $dimension *= 0.01;
                    break;
                case 'mm':
                    $dimension *= 10;
                    break;
            }
        }

        return (float) $dimension;
    }

    /**
     * Convert the weight to a different unit size
     *
     * based on https://gist.github.com/mbrennan-afa/1812521
     *
     * @param  float $weight     The weight to be converted
     * @param  string $unit      The unit to be converted to
     * @return float             The converted weight
     */
    public function convertWeight($weight, $unit = 'kg'): float
    {
        $weightCurrentUnit = get_option('woocommerce_weight_unit');
        $weightCurrentUnit = strtolower($weightCurrentUnit);
        $unit = strtolower($unit);

        if ($weightCurrentUnit !== $unit) {
            // Unify all units to kg first
            switch ($weightCurrentUnit) {
                case 'g':
                    $weight *= 0.001;
                    break;
                case 'lbs':
                    $weight *= 0.4535;
                    break;
                case 'oz':
                    $weight *= 0.0279798545;
                    break;
            }

            // Output desired unit
            switch ($unit) {
                case 'g':
                    $weight *= 1000;
                    break;
                case 'lbs':
                    $weight *= 2.204;
                    break;
                case 'oz':
                    $weight *= 35.274;
                    break;
            }
        }

        return (float) $weight;
    }

    /**
     * Determines if the order uses a Shippit Live quote shipping method
     *
     * @param WC_Order $order
     * @return boolean
     */
    public function isShippitLiveQuote(WC_Order $order): bool
    {
        $shippingMethods = $order->get_shipping_methods();

        foreach ($shippingMethods as $shippingMethod) {
            // If the method is a shippit live quote, return the title of the method
            if ($shippingMethod->get_method_id() === 'mamis_shipit') {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the metadata attached to the live quote if it's a Shippit live quote
     *
     * @param WC_Order $order
     * @param string $metaAttribute
     * @return string|null|void
     */
    public function getShippitLiveQuoteMetaAttributeValue(WC_Order $order, $metaAttribute)
    {
        $shippingMethods = $order->get_shipping_methods();

        foreach ($shippingMethods as $shippingMethod) {
            // If the method is a shippit live quote, return the title of the method
            if ($shippingMethod->get_method_id() === 'mamis_shipit') {
                return $shippingMethod->get_meta($metaAttribute);
            }
        }
    }

    /**
     * Retrieve the mapped shipping method for the order
     *
     * @param WC_Order $order
     * @return string|false
     */
    public function getMappedShippingMethod(WC_Order $order)
    {
        $shippingMethods = $order->get_shipping_methods();
        $mappingsStandard = get_option('wc_settings_shippit_standard_shipping_methods', array());
        $mappingsExpress = get_option('wc_settings_shippit_express_shipping_methods', array());
        $mappingsClickAndCollect = get_option('wc_settings_shippit_clickandcollect_shipping_methods', array());
        $mappingsPlainLabel = get_option('wc_settings_shippit_plainlabel_shipping_methods', array());

        foreach ($shippingMethods as $shippingMethod) {
            $shippingMethodId = $this->getShippingMethodId($shippingMethod);

            // If the method is a shippit live quote, return the title of the method
            // @TODO: use get_method_id() and get_meta() when support for 2.6 deprecated
            if ($shippingMethod['method_id'] == 'mamis_shippit') {
                // $serviceLevel = $shippingMethod->get_meta('service_level');
                $serviceLevel = $shippingMethod['service_level'];

                if (!empty($serviceLevel)) {
                    return $serviceLevel;
                }
            }

            // Otherwise, attempt to locate a suitable method based on shipping method mappings
            if (in_array($shippingMethodId, $mappingsStandard)) {
                return 'standard';
            }
            elseif (in_array($shippingMethodId, $mappingsExpress)) {
                return 'express';
            }
            elseif (in_array($shippingMethodId, $mappingsClickAndCollect)) {
                return 'click_and_collect';
            }
            elseif (in_array($shippingMethodId, $mappingsPlainLabel)) {
                return 'plainlabel';
            }
        }

        return false;
    }

    /**
     * Retrieve the Shipping Method Id for a shipping method
     *
     * @param WC_Order_Item_Shipping $shippingMethod
     * @return string
     */
    protected function getShippingMethodId(WC_Order_Item_Shipping $shippingMethod): string
    {
        // Since Woocommerce v3.4.0, the instance_id is saved in a seperate property of the shipping method
        // To add support for v3.4.0, we'll append the instance_id, as this is how we store a mapping in Shippit
        if (!empty($shippingMethod['instance_id'])) {
            $shippingMethodId = sprintf(
                '%s:%s',
                $shippingMethod['method_id'],
                $shippingMethod['instance_id']
            );
        }
        else {
            $shippingMethodId = $shippingMethod['method_id'];
        }

        // If the shipping method id has more than 1 ":" occarance,
        // we only want the {method_id}:{instance_id}
        // — stripping all other data
        if (substr_count($shippingMethodId, ':') > 1) {
            $shippingMethodId = sprintf(
                '%s:%s',
                strtok($shippingMethodId, ':'),
                strtok(':')
            );
        }

        return $shippingMethodId;
    }
}
