<?php

/**
 * Mamis - https://www.mamis.com.au
 * Copyright © Mamis 2023-present. All rights reserved.
 * See https://www.mamis.com.au/license
 */

class Mamis_Shippit_Data_Mapper_Order extends Mamis_Shippit_Object
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
        $retailerReference = $this->order->get_id();

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
            $this->order->get_shipping_first_name(),
            $this->order->get_shipping_last_name()
        );

        return $this->setReceiverName(trim($receiverName));
    }

    public function mapReceiverContactNumber()
    {
        $receiverContactNumber = $this->order->get_billing_phone();

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
            'email' => $this->order->get_billing_email(),
            'first_name' => $this->order->get_billing_first_name(),
            'last_name' => $this->order->get_billing_last_name(),
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
            $courierAllocation = $this->helper->getShippitLiveQuoteMetaAttributeValue($this->order, 'courier_allocation');

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

        $deliveryDate = $this->helper->getShippitLiveQuoteMetaAttributeValue($this->order, 'delivery_date');

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

        $deliveryWindow = $this->helper->getShippitLiveQuoteMetaAttributeValue($this->order, 'delivery_window');

        if (empty($deliveryWindow)) {
            return $this;
        }

        return $this->setDeliveryWindow($deliveryWindow);
    }

    public function mapDeliveryCompany()
    {
        $deliveryCompany = $this->order->get_shipping_company();

        return $this->setDeliveryCompany($deliveryCompany);
    }

    public function mapDeliveryAddress()
    {
        $deliveryAddress = sprintf(
            '%s %s',
            $this->order->get_shipping_address_1(),
            $this->order->get_shipping_address_2()
        );

        return $this->setDeliveryAddress(trim($deliveryAddress));
    }

    public function mapDeliverySuburb()
    {
        $deliverySuburb = $this->order->get_shipping_city();

        return $this->setDeliverySuburb($deliverySuburb);
    }

    public function mapDeliveryPostcode()
    {
        $deliveryPostcode = $this->order->get_shipping_postcode();

        return $this->setDeliveryPostcode($deliveryPostcode);
    }

    public function mapDeliveryState()
    {
        $deliveryState = $this->order->get_shipping_state();

        // If no state has been provided, use the suburb
        if (empty($deliveryState)) {
            $deliveryState = $this->order->get_shipping_city();
        }

        return $this->setDeliveryState($deliveryState);
    }

    public function mapDeliveryCountryCode()
    {
        $deliveryCountryCode = $this->order->get_shipping_country();

        return $this->setDeliveryCountryCode(trim($deliveryCountryCode));
    }

    public function mapDeliveryInstructions()
    {
        $deliveryInstructions = $this->order->get_customer_note();

        return $this->setDeliveryInstructions($deliveryInstructions);
    }

    public function mapAuthorityToLeave()
    {
        $authorityToLeaveData = $this->order->get_meta('authority_to_leave', true);

        if (in_array(strtolower($authorityToLeaveData), ['yes', 'y', 'true', 'atl'])) {
            return $this->setAuthorityToLeave('Yes');
        }
        elseif (in_array(strtolower($authorityToLeaveData), ['no', 'n', 'false'])) {
            return $this->setAuthorityToLeave('No');
        }

        return $this;
    }

    public function mapProductCurrency()
    {
        $orderCurrency = $this->order->get_currency();

        if (empty($orderCurrency)) {
            return $this;
        }

        return $this->setProductCurrency($orderCurrency);
    }

    public function mapParcelAttributes()
    {
        $itemsData = array();
        $orderItems = $this->order->get_items();

        $orderItemDataMapper = new Mamis_Shippit_Data_Mapper_Order_Item();

        foreach ($orderItems as $orderItem) {
            // If the order item does not have a linked product, skip it
            if (empty($orderItem['product_id'])) {
                continue;
            }

            $product = $orderItem->get_product();

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
