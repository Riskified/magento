<?php

class Riskified_Full_Helper_Log extends Mage_Core_Helper_Abstract
{
	public function log($message, $level = null)
	{
		Mage::log($message, $level, 'riskified_full.log');
	}
}