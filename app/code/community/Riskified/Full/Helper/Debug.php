<?php

class Riskified_Full_Helper_Debug extends Mage_Core_Helper_Abstract
{
    const ALERT_EMAIL = 'assaf@riskified.com';
    const CONFIG_SECTION = 'fullsection';

    /**
     * Send all relevant debug info to riskified
     *
     * @return void
     */
    public function sendDebugInfoToRiskified()
    {
        $debugData = $this->_getDebugData();

        $mail = new Zend_Mail();
        $mail->setBodyHtml(print_r($debugData, true))
            ->setFrom(Mage::getStoreConfig('trans_email/ident_general/email'), Mage::getStoreConfig('trans_email/ident_general/name'))
            ->addTo(self::ALERT_EMAIL)
            ->setSubject('Magento Extension Debug Info for ' . Mage::getBaseUrl());

        try {
            $mail->send();
        }
        catch (Exception $e) {
            Mage::helper('full/log')->logException($e);
        }
    }

    /**
     * @return array
     */
    protected function _getDebugData()
    {
        $helper = Mage::helper('full');

        // Relevant version numbers
        $data = array(
            'magentoVersion' => Mage::getVersion(),
            'magentoExtensionVersion' => $helper->getExtensionVersion(),
            'magentoBaseUrl' => Mage::getBaseUrl(),
            'sdkVersion' => $helper->getSdkVersion(),
            'sdkApiVersion' => $helper->getSdkApiVersion()
        );

        // All Riskified config values
        $configGroups = Mage::getStoreConfig(self::CONFIG_SECTION);

        foreach($configGroups as $groupName => $group) {
            foreach($group as $fieldName => $field) {
                $data['magentoConfig-' . self::CONFIG_SECTION . '/' . $groupName . '/' . $fieldName] = $field;
            }
        }

        return $data;
    }
}