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

class Mamis_Shippit_Data_Mapper_Order_Item_V26 extends Mamis_Shippit_Object
{
    const CUSTOM_OPTION_VALUE = '_custom';

    protected $order;
    protected $orderItem;
    protected $product;
    protected $helper;

    public function __invoke($order, $orderItem, $product)
    {
        $this->order = $order;
        $this->orderItem = $orderItem;
        $this->product = $product;
        $this->helper = new Mamis_Shippit_Helper();

        $this->mapProductLineId()
            ->mapSku()
            ->mapTitle()
            ->mapQty()
            ->mapPrice()
            ->mapWeight()
            ->mapTariffCode()
            ->mapOriginCountryCode()
            ->mapDangerousGoodsCode()
            ->mapDangerousGoodsText();

        if (!defined('SHIPPIT_IGNORE_ITEM_DIMENSIONS') || !SHIPPIT_IGNORE_ITEM_DIMENSIONS) {
            $this->mapDepth()
                ->mapLength()
                ->mapWidth();
        }

        return $this;
    }

    public function mapProductLineId()
    {
        $productLineId = $this->product->get_id();

        return $this->setProductLineId($productLineId);
    }

    public function mapSku()
    {
        if ($this->product->get_type() == 'variation') {
            $sku = sprintf(
                '%s|%s',
                $this->product->get_sku(),
                $this->product->get_variation_id()
            );
        }
        else {
            $sku = $this->product->get_sku();
        }

        return $this->setSku($sku);
    }

    public function mapTitle()
    {
        $title = $this->orderItem['name'];

        return $this->setTitle($title);
    }

    public function mapQty()
    {
        $qty = $this->orderItem['qty'];

        return $this->setQty($qty);
    }

    public function mapPrice()
    {
        $price = round(
            (
                ($this->orderItem['line_total'] + $this->orderItem['line_total'])
                /
                $this->orderItem['qty']
            ),
            2
        );

        return $this->setPrice($price);
    }

    public function mapWeight()
    {
        $itemWeight = $this->product->get_weight();

        // Get the weight if available, otherwise stub weight to 0.2kg
        $weight = (!empty($itemWeight) ? $this->helper->convertWeight($itemWeight) : 0.2);

        return $this->setWeight($weight);
    }

    public function mapDepth()
    {
        $depth = $this->product->get_height();

        if (empty($depth)) {
            return $this;
        }

        $depth = $this->helper->convertDimension($depth);

        return $this->setDepth($depth);
    }

    public function mapLength()
    {
        $length = $this->product->get_length();

        if (empty($length)) {
            return $this;
        }

        $length = $this->helper->convertDimension($length);

        return $this->setLength($length);
    }

    public function mapWidth()
    {
        $width = $this->product->get_width();

        if (empty($width)) {
            return $this;
        }

        $width = $this->helper->convertDimension($width);

        return $this->setWidth($width);
    }

    public function mapTariffCode()
    {
        $tariffCodeAttribute = get_option('wc_settings_shippit_tariff_code_attribute');
        $tariffCodeCustomAttribute = get_option('wc_settings_shippit_tariff_code_custom_attribute');
        $tariffCodeValue = $this->mapProductAttribute($tariffCodeAttribute, $tariffCodeCustomAttribute);

        if (empty($tariffCodeValue)) {
            return $this;
        }

        return $this->setTariffCode($tariffCodeValue);
    }

    public function mapOriginCountryCode()
    {
        $originCountryCodeAttribute = get_option('wc_settings_shippit_origin_country_code_attribute');
        $originCountryCodeAttibuteCustomAttribute = get_option('wc_settings_shippit_origin_country_code_custom_attribute');
        $originCountryCodeValue = $this->mapProductAttribute($originCountryCodeAttribute, $originCountryCodeAttibuteCustomAttribute);

        if (empty($originCountryCodeValue)) {
            return $this;
        }

        return $this->setOriginCountryCode($originCountryCodeValue);
    }

    public function mapDangerousGoodsCode()
    {
        $dangerousGoodsCodeAttribute = get_option('wc_settings_shippit_dangerous_goods_code_attribute');
        $dangerousGoodsCodeCustomAttribute = get_option('wc_settings_shippit_dangerous_goods_code_custom_attribute');
        $dangerousGoodsCodeValue = $this->mapProductAttribute($dangerousGoodsCodeAttribute, $dangerousGoodsCodeCustomAttribute);

        if (empty($dangerousGoodsCodeValue)) {
            return $this;
        }

        return $this->setDangerousGoodsCode($dangerousGoodsCodeValue);
    }

    public function mapDangerousGoodsText()
    {
        $dangerousGoodsTextAttribute = get_option('wc_settings_shippit_dangerous_goods_text_attribute');
        $dangerousGoodsTextCustomAttribute = get_option('wc_settings_shippit_dangerous_goods_text_custom_attribute');
        $dangerousGoodsTextValue = $this->mapProductAttribute($dangerousGoodsTextAttribute, $dangerousGoodsTextCustomAttribute);

        if (empty($dangerousGoodsTextValue)) {
            return $this;
        }

        return $this->setDangerousGoodsText($dangerousGoodsTextValue);
    }

    public function mapProductAttribute($attribute, $customAttribute)
    {
        $value = null;

        // If we have a mapped DG custom value, and the custom value is not empty, use this value
        if ($attribute == self::CUSTOM_OPTION_VALUE) {
            $value = $this->product->get_attribute($customAttribute);
        }
        // Otherwise, if we have a mapped text attribute, use this value
        else {
            $value = $this->product->get_attribute($attribute);
        }

        return $value;
    }
}
