<?php

class Riskified_Full_Helper_Customer_Order extends Mage_Core_Helper_Abstract
{
    private $_orders = array();

    private function _prepare($customer_id) {
        return Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $customer_id);
    }

    public function getCustomerOrders($customer_id) {
        if(!isset($this->_orders[$customer_id])) {
            $customer_orders = $this->_prepare($customer_id);
            $size = $customer_orders->getSize();

            if ($size) {
                $last_id = $customer_orders->getLastItem()->getId();

                $total = $customer_orders
                    ->addExpressionFieldToSelect('sum_total', 'SUM(base_grand_total)', 'base_grand_total')
                    ->addOrder('entity_id')
                    ->fetchItem()->getSumTotal();

                $this->_orders[$customer_id] = array(
                    'last_order_id' => $last_id,
                    'total_spent' => $total,
                    'orders_count' => $size,
                );
            }
        }

        return $this->_orders[$customer_id];
    }
}