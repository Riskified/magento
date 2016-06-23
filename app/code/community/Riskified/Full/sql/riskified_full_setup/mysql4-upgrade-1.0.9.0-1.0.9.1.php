<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
	->newTable($installer->getTable('full/historical_order_sent'))
	->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
	), 'Id')
	->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned'  => true,
		'nullable'  => false,
		'default' => 0
	), 'Order Id')
	->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
		'nullable'  => false
	), 'Date when order was sent');

$installer->getConnection()->createTable($table);
$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('is_sent_to_riskified', 1);

foreach($orders AS $order) {
	Mage::getModel('full/sent')
		->setOrderId($order->getId())
		->save();
}

$installer->getConnection()->dropColumn(
	$this->getTable('sales/order'),
	'is_sent_to_riskified'
);

$installer->endSetup();
