<?php

class Riskified_Full_Model_Cron
{
    /**
     * Maximum number of times we'll attempt to resubmit an order
     */
    const MAX_ATTEMPTS = 7;

    /**
     * The base for calculating the exponential backoff
     */
    const INTERVAL_BASE = 3;

    /**
     * The maximum number of orders to try per cron run
     */
    const BATCH_SIZE = 10;

    /**
     * Attempt to retry order submissions
     */
    public function retrySubmissions()
    {
        Mage::helper('full/log')->log("Retrying failed order submissions");

        // Load all retries that haven't reached the max number of attempts and are past due to run.
        // Past due is calculated as self::INTERVAL_BASE ^ attempts.  This results in an exponential backoff
        // if this submission continues to fail.
        $retries = Mage::getModel('full/retry')->getCollection()
            //->addExpressionFieldToSelect(
            //    'minutes_late',
            //    "TIMESTAMPDIFF(MINUTE, `updated_at`, '" . Mage::getSingleton('core/date')->gmtDate() . "') - POW(" . self::INTERVAL_BASE . ", attempts)",
            //    array()
            //)
            ->addfieldtofilter('attempts',
                array(
                    array('lt' => self::MAX_ATTEMPTS)
                )
            );

        $select = $retries->getSelect();
        $adapter = $select->getAdapter();
        $select
            ->where(sprintf(
                "TIMESTAMPDIFF(MINUTE, `updated_at`, %s) - POW(%s, attempts) > 0"
                , $adapter->quote(Mage::getSingleton('core/date')->gmtDate())
                , $adapter->quote(self::INTERVAL_BASE)
            ))
            ->order('updated_at ASC')
            ->limit(self::BATCH_SIZE);

        $mapperOrder = array();
        $orderIds = array();

        foreach ($retries as $retry) {
            $orderIds[] = $retry->getOrderId();
            $mapperOrder[$retry->getOrderId()] = $retry;
        }

        if (count($orderIds) > 0) {
            $collection = Mage::getModel('sales/order')->getCollection();
            $collection->addFieldToFilter('entity_id', array('in' => array($orderIds)));
            foreach ($collection as $order) {
                Mage::helper('full/log')->log("Retrying order " . $order->getId());

                try {
                    Mage::helper('full/order')->postOrder($order, $mapperOrder[$order->getId()]->getAction());

                    // There is no need to delete the retry here.  postOrder() dispatches a success event which
                    // results in all retries for this order getting deleted.
                } // Log the exception, store the backtrace and increment the counter
                catch (Exception $e) {
                    Mage::helper('full/log')->logException($e);

                    $mapperOrder[$order->getId()]
                        ->setLastError("Exception Message: " . $e->getMessage() . "\n\n" . Varien_Debug::backtrace(true, false))
                        ->setAttempts($mapperOrder[$order->getId()]->getAttempts() + 1)
                        ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
                        ->save();
                }
            }
        }

        Mage::helper('full/log')->log("Done retrying failed order submissions");
    }

    public function uploadHistoricalOrders() {
        if(!Mage::getStoreConfig('riskified/cron/run_historical_orders')) return;

        $orders = Mage::getModel('sales/order')->getCollection();

        if(Mage::getStoreConfig('riskified/cron/resend')) {
            $orders->addFieldToFilter('entity_id', array('nin' => $this->getSentCollection()));
        }
        $orders->getSelect()->order('entity_id DESC');

        Mage::helper('full/order')->postHistoricalOrders($orders);

        Mage::getConfig()->saveConfig('riskified/cron/run_historical_orders', 0);
        Mage::getConfig()->saveConfig('riskified/cron/resend', 0);
        Mage::app()->getStore()->resetConfig();
    }

    protected function getSentCollection() {
        $sentCollection = Mage::getModel('full/sent')->getCollection();
        $sentArray = array();

        foreach($sentCollection AS $entry) {
            $sentArray[] = $entry->getOrderId();
        }

        return array_unique($sentArray);
    }
}