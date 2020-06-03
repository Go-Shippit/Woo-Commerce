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

class Mamis_Shippit_Data_Mapper_Order_V26 extends Mamis_Shippit_Object
{
    protected $helper;
    protected $order;

    public function __invoke($order)
    {
        $this->helper = new Mamis_Shippit_Helper();
        $this->order = $order;

        $this->mapRetailerReference()
            ->mapRetailerInvoice()
            ->mapUserAttributes()
            ->mapReceiverName()
            ->mapReceiverContactNumber()
            ->mapReceiverLanguageCode()
            ->mapCourierType()
            ->mapCourierAllocation()
            ->mapDeliveryDate()
            ->mapDeliveryWindow()
            ->mapDeliveryCompany()
            ->mapDeliveryAddress()
            ->mapDeliverySuburb()
            ->mapDeliveryState()
            ->mapDeliveryPostcode()
            ->mapDeliveryCountryCode()
            ->mapDeliveryInstructions()
            ->mapAuthorityToLeave()
            ->mapProductCurrency()
            ->mapParcelAttributes();

        return $this;
    }

    public function mapRetailerReference()
    {
        $retailerReference = $this->order->id;

        return $this->setRetailerReference($retailerReference);
    }

    public function mapRetailerInvoice()
    {
        $retailerInvoice = $this->order->get_order_number();

        return $this->setRetailerInvoice($retailerInvoice);
    }

    public function mapReceiverName()
    {
        $receiverName = sprintf(
            '%s %s',
            $this->order->shipping_first_name,
            $this->order->shipping_last_name
        );

        return $this->setReceiverName(trim($receiverName));
    }

    public function mapReceiverContactNumber()
    {
        $receiverContactNumber = $this->order->billing_phone;

        return $this->setReceiverContactNumber($receiverContactNumber);
    }

    public function mapReceiverLanguageCode()
    {
        // WooCommerce does not provide order level
        // language code, so we rely on store locale
        $merchantLocale = get_locale();

        if (empty($merchantLocale)) {
            return $this;
        }

        $languageCode = explode('_', $merchantLocale);

        return $this->setReceiverLanguageCode(reset($languageCode));
    }

    public function mapUserAttributes()
    {
        $userAttributes = array(
            'email' => $this->order->billing_email,
            'first_name' => $this->order->billing_first_name,
            'last_name' => $this->order->billing_last_name,
        );

        return $this->setUserAttributes($userAttributes);
    }

    public function mapCourierType()
    {
        if ($this->helper->isShippitLiveQuote($this->order)) {
            // If a shippit live quote is available, we'll set a courier allocation
            // as such, return early
            return $this;
        }

        $mappedShippingMethod = $this->helper->getMappedShippingMethod($this->order);

        // Plain label services are assigned as a courier allocation
        if ($mappedShippingMethod == 'plainlabel') {
            return $this;
        }
        elseif ($mappedShippingMethod !== false) {
            return $this->setCourierType($mappedShippingMethod);
        }

        return $this->setCourierType('standard');
    }

    public function mapCourierAllocation()
    {
        if ($this->helper->isShippitLiveQuote($this->order)) {
            $courierAllocation = $this->helper->getShippitLiveQuoteDetail($this->order, 'courier_allocation');

            return $this->setCourierAllocation($courierAllocation);
        }

        $mappedShippingMethod = $this->helper->getMappedShippingMethod($this->order);

        if ($mappedShippingMethod == 'plainlabel') {
            return $this->setCourierAllocation($mappedShippingMethod);
        }

        return $this;
    }

    public function mapDeliveryDate()
    {
        // Only provide a delivery date if the order has a shippit live quote
        if (!$this->helper->isShippitLiveQuote($this->order)) {
            return $this;
        }

        $deliveryDate = $this->helper->getShippitLiveQuoteDetail($this->order, 'delivery_date');

        if (empty($deliveryDate)) {
            return $this;
        }

        return $this->setDeliveryDate($deliveryDate);
    }

    public function mapDeliveryWindow()
    {
        // Only provide a delivery date if the order has a shippit live quote
        if (!$this->helper->isShippitLiveQuote($this->order)) {
            return $this;
        }

        $deliveryWindow = $this->helper->getShippitLiveQuoteDetail($this->order, 'delivery_window');

        if (empty($deliveryWindow)) {
            return $this;
        }

        return $this->setDeliveryWindow($deliveryWindow);
    }

    public function mapDeliveryCompany()
    {
        $deliveryCompany = $this->order->shipping_company;

        return $this->setDeliveryCompany($deliveryCompany);
    }

    public function mapDeliveryAddress()
    {
        $deliveryAddress = sprintf(
            '%s %s',
            $this->order->shipping_address_1,
            $this->order->shipping_address_2
        );

        return $this->setDeliveryAddress(trim($deliveryAddress));
    }

    public function mapDeliverySuburb()
    {
        $deliverySuburb = $this->order->shipping_city;

        return $this->setDeliverySuburb($deliverySuburb);
    }

    public function mapDeliveryPostcode()
    {
        $deliveryPostcode = $this->order->shipping_postcode;

        return $this->setDeliveryPostcode($deliveryPostcode);
    }

    public function mapDeliveryState()
    {
        $deliveryState = $this->order->shipping_state;

        // If no state has been provided, use the suburb
        if (empty($deliveryState)) {
            $deliveryState = $this->order->shipping_state;
        }

        return $this->setDeliveryState($deliveryState);
    }

    public function mapDeliveryCountryCode()
    {
        $deliveryCountryCode = $this->order->shipping_country;

        return $this->setDeliveryCountryCode(trim($deliveryCountryCode));
    }

    public function mapDeliveryInstructions()
    {
        $deliveryInstructions = $this->order->customer_message;

        return $this->setDeliveryInstructions($deliveryInstructions);
    }

    public function mapAuthorityToLeave()
    {
        $authorityToLeaveData = get_post_meta($this->order->id, 'authority_to_leave', true);

        if (in_array(strtolower($authorityToLeaveData), ['yes', 'y', 'true', 'atl'])) {
            $this->setAuthorityToLeave('Yes');
        }
        elseif (in_array(strtolower($authorityToLeaveData), ['no', 'n', 'false'])) {
            $this->setAuthorityToLeave('No');
        }

        return $this;
    }

    public function mapProductCurrency()
    {
        $orderCurrency = $this->order->get_order_currency();

        if (empty($orderCurrency)) {
            return $this;
        }

        return $this->setProductCurrency($orderCurrency);
    }

    public function mapParcelAttributes()
    {
        $itemsData = array();
        $orderItems = $this->order->get_items();

        // map to v2.6 order item data mapper
        $orderItemDataMapper = new Mamis_Shippit_Data_Mapper_Order_Item_V26();

        foreach ($orderItems as $orderItem) {
            // If the order item does not have a linked product, skip it
            if (empty($orderItem['product_id'])) {
                continue;
            }

            $product = $this->order->get_product_from_item($orderItem);

            // If the product is a virtual item, skip it
            if ($product->is_virtual()) {
                continue;
            }

            $itemsData[] = $orderItemDataMapper->__invoke(
                $this->order,
                $orderItem,
                $product
            )->toArray();
        }

        return $this->setParcelAttributes($itemsData);
    }

    protected function getLegacyShippingOptions($shippingMethodId)
    {
        if (stripos($shippingMethodId, 'priority') === FALSE) {
            return;
        }

        return explode('_', $shippingMethodId);
    }
}
