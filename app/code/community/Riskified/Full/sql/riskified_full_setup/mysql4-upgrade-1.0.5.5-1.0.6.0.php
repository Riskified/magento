<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$helper = Mage::helper('full/order_status');

// create new approved and declined statuses

Mage::getModel('sales/order_status')
	->setStatus($helper->getRiskifiedDeclinedStatusCode())
    ->setLabel($helper->getRiskifiedDeclinedStatusLabel())
	->assignState(Mage_Sales_Model_Order::STATE_HOLDED)
	->save();

Mage::getModel('sales/order_status')
    ->setStatus($helper->getRiskifiedDeclinedStatusCode())
    ->setLabel($helper->getRiskifiedDeclinedStatusLabel())
    ->assignState(Mage_Sales_Model_Order::STATE_CANCELED)
    ->save();

Mage::getModel('sales/order_status')
    ->setStatus($helper->getRiskifiedApprovedStatusCode())
    ->setLabel($helper->getRiskifiedApprovedStatusLabel())
    ->assignState(Mage_Sales_Model_Order::STATE_HOLDED)
    ->save();

Mage::getModel('sales/order_status')
    ->setStatus($helper->getRiskifiedApprovedStatusCode())
    ->setLabel($helper->getRiskifiedApprovedStatusLabel())
    ->assignState(Mage_Sales_Model_Order::STATE_PROCESSING)
    ->save();

// update or create existing on hold for review and transport error labels and statuses
Mage::getModel('sales/order_status')
    ->setStatus($helper->getOnHoldStatusCode())
    ->setLabel($helper->getOnHoldStatusLabel())
    ->assignState(Mage_Sales_Model_Order::STATE_HOLDED)
    ->save();

Mage::getModel('sales/order_status')
    ->setStatus($helper->getTransportErrorStatusCode())
    ->setLabel($helper->getTransportErrorStatusLabel())
    ->assignState(Mage_Sales_Model_Order::STATE_HOLDED)
    ->save();


$installer->endSetup();
