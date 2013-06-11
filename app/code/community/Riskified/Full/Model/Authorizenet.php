<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Paygate
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Riskified_Full_Model_Authorizenet extends Mage_Paygate_Model_Authorizenet 
{
    /**
     * it sets card`s data into additional information of payment model
     *
     * @param mage_paygate_model_authorizenet_result $response
     * @param mage_sales_model_order_payment $payment
     * @return varien_object
     */
    protected function _registercard(varien_object $response, mage_sales_model_order_payment $payment)
    {
        mage::log( "in inherited _registercard." );
        $card=parent::_registercard($response,$payment);
        $card->setCcAvsResultCode($response->getAvsResultCode());
        $card->setCcResponseCode($response->getCardCodeResponseCode());
        mage::log( $response->debug() );
        mage::log( $card->debug() );
        mage::log( "exiting inherited _registercard." );
        return $card;
    }
}
