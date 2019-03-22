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
 * @copyright  Copyright (c) by Mamis.IT Pty Ltd (http://www.mamis.com.au)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.mamis.com.au/licencing
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
    public function convertDimension($dimension, $unit = 'm')
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

        return $dimension;
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
    public function convertWeight($weight, $unit = 'kg')
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
            }

            // Output desired unit
            switch ($unit) {
                case 'g':
                    $weight *= 1000;
                    break;
                case 'lbs':
                    $weight *= 2.204;
                    break;
            }
        }

        return $weight;
    }
}