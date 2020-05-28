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

class Mamis_Shippit_Data_Mapper_Order_Item extends Mamis_Shippit_Object
{
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
        // @TODO: since WC version 3.0 get_variation_id is deprecated
        // suggested to use get_id() instead
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
        $title = $this->orderItem->get_name();

        return $this->setTitle($title);
    }

    public function mapQty()
    {
        $qty = $this->orderItem->get_quantity();

        return $this->setQty($qty);
    }

    public function mapPrice()
    {
        $price = $this->orderItem->get_total() / $this->orderItem->get_quantity();

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
        if (!get_option('wc_settings_shippit_tariff_code_attribute')) {
            return $this;
        }

        $tariffCode = $this->product->get_attribute('tariff_code');

        if (empty($tariffCode)) {
            return $this;
        }

        return $this->setTariffCode($tariffCode);
    }

    public function mapOriginCountryCode()
    {
        if (!get_option('wc_settings_shippit_origin_country_code_attribute')) {
            return $this;
        }

        $originCountryCode = $this->product->get_attribute('origin_country_code');

        if (empty($originCountryCode)) {
            return $this;
        }

        return $this->setOriginCountryCode($originCountryCode);
    }

    public function mapDangerousGoodsCode()
    {
        if (!get_option('wc_settings_shippit_dangerous_goods_code_attribute')) {
            return $this;
        }

        $dangerousGoodsCode = $this->product->get_attribute('dangerous_goods_code');

        if (empty($dangerousGoodsCode)) {
            return $this;
        }

        return $this->setDangerousGoodsCode($dangerousGoodsCode);
    }

    public function mapDangerousGoodsText()
    {
        if (!get_option('wc_settings_shippit_dangerous_goods_text_attribute')) {
            return $this;
        }

        $dangerousGoodsText = $this->product->get_attribute('dangerous_goods_text');

        if (empty($dangerousGoodsText)) {
            return $this;
        }

        return $this->setDangerousGoodsText($dangerousGoodsText);
    }
}
