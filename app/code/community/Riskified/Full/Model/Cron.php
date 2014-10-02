<?php

class Riskified_Full_Model_Cron
{
    const MAX_ATTEMPTS = 7;
    const INTERVAL_BASE = 3;

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
            )
            ->setOrder('retry_id','ASC');

        $select = $retries->getSelect();
        $adapter = $select->getAdapter();
        $select
            ->where(sprintf(
                "TIMESTAMPDIFF(MINUTE, `updated_at`, %s) - POW(%s, attempts) > 0"
                , $adapter->quote(Mage::getSingleton('core/date')->gmtDate())
                , $adapter->quote(self::INTERVAL_BASE)
            ));

        foreach($retries as $retry) {
            Mage::helper('full/log')->log("Retrying order " . $retry->getOrderId());

            $order = Mage::getModel('sales/order')->load($retry->getOrderId());

            if (!$order) {
                Mage::helper('full/log')->log("Order doesn't exist, skipping");

                $retry->delete();
                continue;
            }

            try {
                Mage::helper('full/order')->postOrder($order, $retry->getAction());

                // There is no need to delete the retry here.  postOrder() dispatches a success event which
                // results in all retries for this order getting deleted.
            }
            // Log the exception, store the backtrace and increment the counter
            catch (Exception $e) {
                Mage::helper('full/log')->logException($e);

                $retry
                    ->setLastError("Exception Message: " . $e->getMessage() . "\n\n" . Varien_Debug::backtrace(true, false))
                    ->setAttempts($retry->getAttempts() + 1)
                    ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
                    ->save();
            }
        }

        Mage::helper('full/log')->log("Done retrying failed order submissions");
    }
}