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
     * Get products with id/name for a multiselect
     *
     * @return array     An associative array of product ids and name
     */
    public static function getProducts()
    {
        $productArgs = array(
            'post_type' => 'product',
            'posts_per_page' => -1
        );

        $products = get_posts($productArgs);

        $productOptions = array();

        foreach ($products as $product) {
            $productOptions[$product->ID] = __($product->post_title, 'woocommerce-shippit');
        }

        return $productOptions;
    }

    public static function getAttributes()
    {
        $productAttributes = array();

        $attributeTaxonomies = wc_get_attribute_taxonomies();

        foreach ($attributeTaxonomies as $tax) {
            $productAttributes[$tax->attribute_name] = __($tax->attribute_name, 'woocommerce-shippit');
        }

        return $productAttributes;
    }
}