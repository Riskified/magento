<?php

class Riskified_Full_ResponseController extends Mage_Core_Controller_Front_Action
{
    public function getresponseAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $helper = Mage::helper('full/order');
        $logger = Mage::helper('full/log');
        $statusCode = 200;
        $id = null;
        $msg = null;

        try {
            Mage::log($this->getRequest()->getParams(), null, 'riskified_full.log');
            $notification = $helper->parseRequest($request);
            $id = $notification->id;
            if ($notification->status == 'test' && $id == 0) {
                $statusCode = 200;
                $msg = 'Test notification received successfully';
                Mage::helper('full/log')->log("Test Notification received: ", serialize($notification));
            } else {

                // Changing scope to ADMIN store so that all orders will be visible and all admin functionalities will work
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

                Mage::helper('full/log')->log("Notification received: ", serialize($notification));

                $order = $this->loadOrderByOrigId($id);
                if (!$order || !$order->getId()) {
                    $logger->log("ERROR: Unable to load order (" . $id . ")");
                    $statusCode = 400;
                    $msg = 'Could not find order to update.';
                } else {
                    try {
                        $helper->updateOrder(
                            $order,
                            $notification->status,
                            $notification->oldStatus,
                            $notification->description
                        );

                        $statusCode = 200;
                        $msg = 'Order-Update event triggered.';
                    } catch (PDOException $e) {
                        $exceptionMessage = 'SQLSTATE[40001]: Serialization '
                        . 'failure: 1213 Deadlock found when trying to get '
                        . 'lock; try restarting transaction';

                        if ($e->getMessage() === $exceptionMessage) {
                            throw new Exception('Deadlock exception handled.');
                        } else {
                            throw $e;
                        }
                    }
                }
            }
        } catch (Riskified\DecisionNotification\Exception\AuthorizationException $e) {
            $logger->logException($e);
            $statusCode = 401;
            $msg = 'Authentication Failed.';
        } catch (\Riskified\DecisionNotification\Exception\BadPostJsonException $e) {
            $logger->logException($e);
            $statusCode = 400;
            $msg = "JSON Parsing Error.";
        } catch (Exception $e) {
            $logger->log("ERROR: while processing notification for order $id");
            $logger->logException($e);
            $statusCode = 500;
            $msg = "Internal Error";
        }

        $response->setHttpResponseCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setBody('{ "order" : { "id" : "' . $id . '", "description" : "' . $msg . '" } }');
    }

    private function loadOrderByOrigId($full_orig_id)
    {
        if (!$full_orig_id) {
            return null;
        }

        $magento_ids = explode("_", $full_orig_id);
        $order_id = $magento_ids[0];
        $increment_id = $magento_ids[1];

        if ($order_id && $increment_id) {
            return Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('entity_id', $order_id)
                ->addFieldToFilter('increment_id', $increment_id)
                ->getFirstItem();
        }
        return Mage::getModel('sales/order')->load($order_id);
    }
}