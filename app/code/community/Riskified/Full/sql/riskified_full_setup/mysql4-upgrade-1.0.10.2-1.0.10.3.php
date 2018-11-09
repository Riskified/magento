<?php
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales/order'),
        'riskified_cart_token',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => true,
            'length' => 255,
            'after' => null,
            'comment' => 'Cart token that is sent to riskified'
        )
    );

$installer->endSetup();
