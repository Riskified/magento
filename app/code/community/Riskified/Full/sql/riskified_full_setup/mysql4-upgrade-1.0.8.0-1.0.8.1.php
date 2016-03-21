<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
    ->addForeignKey(
        $installer->getFkName($installer->getTable('full/retry'), 'order_id', $installer->getTable('sales/order'), 'entity_id'),
        $installer->getTable('full/retry'),
        'order_id',
        $installer->getTable('sales/order'),
        'entity_id'
    );


$installer->endSetup();
