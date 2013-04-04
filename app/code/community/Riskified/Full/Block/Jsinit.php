<?php
class Riskified_Full_Block_Jsinit extends Mage_Adminhtml_Block_Template
{
	/**
	 * Include JS in head if section is moneybookers
	 */
	protected function _prepareLayout()
	{
		$section = $this->getAction()->getRequest()->getParam('section', false);
		if ($section == 'full') {
			$this->getLayout()
			->getBlock('head')
			->addJs('mage/adminhtml/moneybookers.js');
		}
		parent::_prepareLayout();
	}

	/**
	 * Print init JS script into body
	 * @return string
	 */
	protected function _toHtml()
	{
		$section = $this->getAction()->getRequest()->getParam('section', false);
		if ($section == 'full') {
			return parent::_toHtml();
		} else {
			return '';
		}
	}
}