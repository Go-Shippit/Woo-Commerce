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

class Mamis_Shippit_Data_Mapper_Order_Item_V26 extends Varien_Object
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
            ->mapPrice();

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
        $price = $this->orderItem['line_total'] / $this->orderItem['qty'];

        return $this->setPrice($price);
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
}
