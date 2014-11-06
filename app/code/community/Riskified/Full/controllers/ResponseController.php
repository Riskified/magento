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
            $notification = $helper->parseRequest($request);
            $id = $notification->id;

            Mage::helper('full/log')->log("Notification received: ", serialize($notification));

            $order = Mage::getModel('sales/order')->load($id);
            if (!$order || !$order->getId()) {
                $logger->log("ERROR: Unable to load order (" . $id . ")");
                $statusCode = 400;
                $msg = 'Could not find order to update.';
            } else {
                $helper->updateOrder($order, $notification->status, $notification->description);
                $statusCode = 200;
                $msg = 'Order-Update event triggered.';
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
            $statusCode = 500;
            $msg = "Internal Error";
        }

        $response->setHttpResponseCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setBody('{ "order" : { "id" : "' . $id . '", "description" : "' . $msg .'" } }');

    }
}