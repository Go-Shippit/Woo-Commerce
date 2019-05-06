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

class Mamis_Shippit_Data_Mapper_Order extends Varien_Object
{
    public function process($order, $shippingMethodId)
    {
        $shippingMethods = $order->get_shipping_methods();
        $orderShippingMeta = (new Mamis_Shippit_Helper())
            ->getOrderShippingMeta($shippingMethods);

        // Check if the shipping method chosen was Mamis_Shippit
        // Legacy WC < 3.0 live quoting methods are saved in this
        // format e.g. Mamis_Shippit_{service_level}_{additional_data}
        $shippingMethodId = str_replace('Mamis_Shippit_', '', $shippingMethodId);

        if (isset($orderShippingMeta['service_level'])) {
            $shippingMethodId = $orderShippingMeta['service_level'];
        }
        elseif (empty($shippingMethodId)) {
            // fallback to standard if a method could no longer be mapped
            $shippingMethodId = 'standard';
        }

        $this->setCourierType($shippingMethodId)
            ->setCourierAllocation($shippingMethodId)
            ->setDeliveryDate($orderShippingMeta, $shippingMethodId)
            ->setDeliveryWindow($orderShippingMeta, $shippingMethodId);

        return $this->toArray();
    }

    public function setCourierType($shippingMethodId)
    {
        if ($shippingMethodId == 'plain_label') {
            return $this->setData('courier_type', null);
        }
        // in case of legacy, method id can have additional data
        elseif (stripos($shippingMethodId, 'priority') !== FALSE) {
            return $this->setData('courier_type', 'priority');
        }

        return $this->setData('courier_type', $shippingMethodId);
    }

    public function setCourierAllocation($shippingMethodId)
    {
        if ($shippingMethodId == 'plain_label') {
            return $this->setData('courier_allocation', 'PlainLabel');
        }

        return $this;
    }

    public function setDeliveryDate($orderShippingMeta, $shippingMethodId)
    {
        if (isset($orderShippingMeta['delivery_date'])) {
            return $this->setData('delivery_date', $orderShippingMeta['delivery_date']);
        }

        $shippingOptions = $this->getLegacyShippingOptions($shippingMethodId);
        if (isset($shippingOptions[1])) {
            return $this->setData('delivery_date', $shippingOptions[1]);
        }

        return $this;
    }

    public function setDeliveryWindow($orderShippingMeta, $shippingMethodId)
    {
        if (isset($orderShippingMeta['delivery_window'])) {
            return $this->setData('delivery_window', $orderShippingMeta['delivery_window']);
        }

        $shippingOptions = $this->getLegacyShippingOptions($shippingMethodId);
        if (isset($shippingOptions[2])) {
            return $this->setData('delivery_window', $shippingOptions[2]);
        }

        return $this;
    }

    public function getLegacyShippingOptions($shippingMethodId)
    {
        if (stripos($shippingMethodId, 'priority') === FALSE) {
            return;
        }

        return explode('_', $shippingMethodId);
    }
}
