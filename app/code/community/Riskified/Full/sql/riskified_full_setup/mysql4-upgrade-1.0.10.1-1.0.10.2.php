<?php
/**
 * Copyright (c) 2017.
 * @category Cminds
 * @package  Cminds_
 * @author   CreativeMinds Developers <info@cminds.com>
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('full/declination_sent'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => 0
    ), 'Order Id')
    ->addIndex(
        $installer->getIdxName('full/declination_sent', array('order_id')),
        array('order_id')
    );

$installer->getConnection()->createTable($table);

$installer->getConnection()
    ->addForeignKey(
        $installer->getFkName('full/declination_sent', 'order_id', 'sales/order', 'entity_id'),
        $installer->getTable("full/declination_sent"),
        'order_id',
        $installer->getTable('sales/order'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    );

$installer->endSetup();
