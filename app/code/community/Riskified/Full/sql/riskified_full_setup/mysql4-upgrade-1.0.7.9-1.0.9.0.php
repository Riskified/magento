<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
	$this->getTable('sales/order'),
	'is_sent_to_riskified',
	"INT NOT NULL DEFAULT 0"
);

$installer->endSetup();
