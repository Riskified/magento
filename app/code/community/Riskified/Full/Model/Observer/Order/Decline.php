<?php

class Riskified_Full_Model_Observer_Order_Decline
{
    private $order;

    public function handleOrderDecline(
        Varien_Event_Observer $observer
    ) {
        $order = $observer->getOrder();
        $this->order = $order;
        /**
         * @var Riskified_Full_Helper_Data $dataHelper
        */
        $dataHelper = Mage::helper("full");

        if (!$dataHelper->isDeclineNotificationEnabled()) {
            return $this;
        }

        if (Mage::registry("decline-email-sent")) {
            return $this;
        }

        $declinationNotificationSent = Mage::getModel("full/declination")
            ->load($order->getId(), "order_id");

        if ($declinationNotificationSent->getId()) {
            return $this;
        }

        Mage::register("decline-email-sent", true);

        $emailTemplate  = Mage::getModel('core/email_template')
            ->loadDefault('riskified_order_declined');

        $emailTemplate->setSenderEmail(
            $dataHelper->getDeclineNotificationSenderEmail()
        );

        $emailTemplate->setSenderName(
            $dataHelper->getDeclineNotificationSenderName()
        );

        $subject = $dataHelper->getDeclineNotificationSubject();
        $content = $dataHelper->getDeclineNotificationContent();

        $shortCodes = array(
            "{{customer_name}}",
            "{{customer_firstname}}",
            "{{order_increment_id}}",
            "{{order_view_url}}",
            "{{products}}",
            "{{store_name}}",
        );
        $formattedPayload = $this->getFormattedData();

        foreach ($shortCodes as $key => $value) {
            $subject = str_replace($value, $formattedPayload[$key], $subject);
            $content = str_replace($value, $formattedPayload[$key], $content);
        }

        try {
            if ($content == "") {
                throw new Exception("Email content is empty");
            }

            if ($subject == "") {
                throw new Exception("Email subject is empty");
            }

            $wasSent = $emailTemplate->send(
                $order->getCustomerEmail(),
                $order->getCustomerName(),
                array(
                    'store' => Mage::app()->getStore(),
                    'subject' => $subject,
                    'order' => $order,
                    'content' => $content
                )
            );

            if ($wasSent === true) {
                $fileLog = $dataHelper->__(
                    "Decline email was sent to customer %s (%s) for order #%s",
                    $order->getCustomerName(),
                    $order->getCustomerEmail(),
                    $order->getIncrementId()
                );

                $orderComment = $dataHelper->__(
                    "Decline email was sent to customer %s (%s)",
                    $order->getCustomerName(),
                    $order->getCustomerEmail()
                );
            } else {
                $fileLog = $dataHelper->__(
                    "Decline email was not sent to customer %s (%s) for order #%s - server internal error",
                    $order->getCustomerName(),
                    $order->getCustomerEmail(),
                    $order->getIncrementId()
                );
                $orderComment = $dataHelper->__(
                    "Decline email was not sent to customer %s (%s) - server internal error",
                    $order->getCustomerName(),
                    $order->getCustomerEmail()
                );
            }

            Mage::helper('full/log')->log($fileLog);

            $order
                ->addStatusHistoryComment($orderComment)
                ->setIsCustomerNotified(true);
            $order->save();

            Mage::getModel("full/declination")
                ->setOrderId($order->getId())
                ->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    private function getFormattedData()
    {
        $products = array();

        foreach ($this->order->getAllItems() as $item) {
            $products[] = $item->getName();
        }

        $data = array(
            $this->order->getCustomerName(),
            $this->order->getCustomerFirstname(),
            $this->order->getIncrementId(),
            Mage::getUrl('sales/order/view', array('order_id' => $this->order->getId())),
            join(', ', $products),
            Mage::app()->getStore()->getFrontendName()
        );

        return $data;
    }
}
