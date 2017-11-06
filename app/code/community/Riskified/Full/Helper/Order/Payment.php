<?php

require_once
    Mage::getBaseDir('lib')
    . DS . 'riskified_php_sdk'
    . DS . 'src'
    . DS . 'Riskified'
    . DS . 'autoloader.php';

use Riskified\OrderWebhook\Model;

/**
 * Riskified Full order payment helper.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
class Riskified_Full_Helper_Order_Payment extends Mage_Core_Helper_Abstract
{
    /**
     * Return order payment details.
     *
     * @param Mage_Sales_Model_Order $order Order object.
     *
     * @return Model\PaymentDetails|null
     */
    public function getPaymentDetails($order)
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return null;
        }

        if (Mage::helper('full')->isDebugLogsEnabled()) {
            $this->logPaymentData($order);
        }

        $transactionId = $payment->getTransactionId();
        $gatewayName = $payment->getMethod();

        try {
            switch ($gatewayName) {
                case 'authorizenet':
                    $authorizeData = $payment
                        ->getAdditionalInformation('authorize_cards');
                    if ($authorizeData && is_array($authorizeData)) {
                        $cardsData = array_values($authorizeData);
                        if ($cardsData && $cardsData[0]) {
                            $cardData = $cardsData[0];
                            if (isset($cardData['cc_last4'])) {
                                $creditCardNumber = $cardData['cc_last4'];
                            }
                            if (isset($cardData['cc_type'])) {
                                $creditCardCompany = $cardData['cc_type'];
                            }
                            if (isset($cardData['cc_avs_result_code'])) {
                                $avsResultCode = $cardData['cc_avs_result_code'];
                            } // getAvsResultCode
                            if (isset($cardData['cc_response_code'])) {
                                $cvvResultCode = $cardData['cc_response_code'];
                            } // getCardCodeResponseCode
                        }
                    }
                    break;
                case 'authnetcim':
                    $avsResultCode = $payment
                        ->getAdditionalInformation('avs_result_code');
                    $cvvResultCode = $payment
                        ->getAdditionalInformation('card_code_response_code');
                    break;
                case 'optimal_hosted':
                    try {
                        $optimalTransaction = unserialize(
                            $payment->getAdditionalInformation('transaction')
                        );
                        $cvvResultCode = $optimalTransaction->cvdVerification;
                        $houseVerification = $optimalTransaction
                            ->houseNumberVerification;
                        $zipVerification = $optimalTransaction->zipVerification;
                        $avsResultCode = $houseVerification . ',' . $zipVerification;
                    } catch (Exception $e) {
                        Mage::helper('full/log')->log(
                            'optimal payment (' . $gatewayName . ') additional '
                            . 'payment info failed to parse:' . $e->getMessage()
                        );
                    }
                    break;
                case 'paypal_express':
                case 'paypaluk_express':
                case 'paypal_standard':
                    $payerEmail = $payment
                        ->getAdditionalInformation('paypal_payer_email');
                    $payerStatus = $payment
                        ->getAdditionalInformation('paypal_payer_status');
                    $payerAddressStatus = $payment
                        ->getAdditionalInformation('paypal_address_status');
                    $protectionEligibility = $payment
                        ->getAdditionalInformation('paypal_protection_eligibility');
                    $paymentStatus = $payment
                        ->getAdditionalInformation('paypal_payment_status');
                    $pendingReason = $payment
                        ->getAdditionalInformation('paypal_pending_reason');

                    return new Model\PaymentDetails(
                        array_filter(
                            array(
                                'authorization_id' => $transactionId,
                                'payer_email' => $payerEmail,
                                'payer_status' => $payerStatus,
                                'payer_address_status' => $payerAddressStatus,
                                'protection_eligibility' => $protectionEligibility,
                                'payment_status' => $paymentStatus,
                                'pending_reason' => $pendingReason,
                            ),
                            'strlen'
                        )
                    );
                case 'paypal_direct':
                case 'paypaluk_direct':
                    $avsResultCode = $payment
                        ->getAdditionalInformation('paypal_avs_code');
                    $cvvResultCode = $payment
                        ->getAdditionalInformation('paypal_cvv2_match');
                    $creditCardNumber = $payment->getCcLast4();
                    $creditCardCompany = $payment->getCcType();
                    break;
                case 'sagepaydirectpro':
                case 'sage_pay_form':
                case 'sagepayserver':
                    $sage = $order->getSagepayInfo();
                    if ($sage) {
                        $avsResultCode = $sage->getData('address_result');
                        $cvvResultCode = $sage->getData('cv2result');
                        $creditCardNumber = $sage->getData('last_four_digits');
                        $creditCardCompany = $sage->getData('card_type');
                        Mage::helper('full/log')->log(
                            'sagepay payment (' . $gatewayName . ') additional'
                            . 'info: ' . PHP_EOL
                            . var_export($payment->getAdditionalInformation(),
                                1)
                        );
                    } else {
                        Mage::helper('full/log')->log(
                            'sagepay payment (' . $gatewayName . ') - '
                            . 'getSagepayInfo returned null object'
                        );
                    }
                    break;

                case 'transarmor':
                    $avsResultCode = $payment
                        ->getAdditionalInformation('avs_response');
                    $cvvResultCode = $payment
                        ->getAdditionalInformation('cvv2_response');
                    Mage::helper('full/log')->log(
                        'transarmor payment additional info: ' . PHP_EOL
                        . var_export($payment->getAdditionalInformation(), 1)
                    );
                    break;

                case 'gene_braintree_creditcard':
                case 'braintree':
                case 'braintreevzero':
                    $cvvResultCode = $payment
                        ->getAdditionalInformation('cvvResponseCode');
                    $creditCardBin = $payment
                        ->getAdditionalInformation('bin');
                    $houseVerification = $payment
                        ->getAdditionalInformation('avsStreetAddressResponseCode');
                    $zipVerification = $payment
                        ->getAdditionalInformation('avsPostalCodeResponseCode');
                    $avsResultCode = $houseVerification . ',' . $zipVerification;
                    break;

                case 'adyen_cc':
                    $avsResultCode = $payment->getAdyenAvsResult();
                    $cvvResultCode = $payment->getAdyenCvcResult();
                    $transactionId = $payment->getAdyenPspReference();
                    $creditCardBin = $payment->getAdyenCardBin();
                    break;

                // Conekta gateway
                case 'card':
                    $creditCardBin = $payment->getCardBin();
                    break;

                default:
                    Mage::helper('full/log')->log('unknown gateway:' . $gatewayName);
                    Mage::helper('full/log')->log(
                        'Gateway payment (' . $gatewayName . ') additional '
                        . 'info: ' . PHP_EOL
                        . var_export($payment->getAdditionalInformation(), 1)
                    );
                    break;
            }
        } catch (Exception $e) {
            Mage::helper('full/log')->logException($e);
            Mage::getSingleton('adminhtml/session')
                ->addError('Riskified extension: ' . $e->getMessage());
        }

        if (!isset($cvvResultCode)) {
            $cvvResultCode = $payment->getCcCidStatus();
        }
        if (!isset($creditCardNumber)) {
            $creditCardNumber = $payment->getCcLast4();
        }
        if (!isset($creditCardCompany)) {
            $creditCardCompany = $payment->getCcType();
        }
        if (!isset($avsResultCode)) {
            $avsResultCode = $payment->getCcAvsStatus();
        }
        if (!isset($creditCardBin)) {
            $creditCardBin = $payment->getAdditionalInformation('riskified_cc_bin');
        }
        if (isset($creditCardNumber)) {
            $creditCardNumber = "XXXX-XXXX-XXXX-" . $creditCardNumber;
        }

        return new Model\PaymentDetails(
            array_filter(
                array(
                    'authorization_id' => $transactionId,
                    'avs_result_code' => $avsResultCode,
                    'cvv_result_code' => $cvvResultCode,
                    'credit_card_number' => $creditCardNumber,
                    'credit_card_company' => $creditCardCompany,
                    'credit_card_bin' => $creditCardBin,
                ),
                'strlen'
            )
        );
    }

    /**
     * Log payment data.
     *
     * @param Mage_Sales_Model_Order $order Order object.
     *
     * @return Riskified_Full_Helper_Order_Payment
     */
    protected function logPaymentData($order)
    {
        $logHelper = Mage::helper('full/log');

        $logHelper->log('Payment info debug Logs:');
        try {
            $payment = $order->getPayment();
            $gatewayName = $payment->getMethod();

            $logHelper->log(
                'Payment Gateway: ' . $gatewayName
            );
            $logHelper->log(
                'payment->getCcLast4(): ' . $payment->getCcLast4()
            );
            $logHelper->log(
                'payment->getCcType(): ' . $payment->getCcType()
            );
            $logHelper->log(
                'payment->getCcCidStatus(): '
                . $payment->getCcCidStatus()
            );
            $logHelper->log(
                'payment->getCcAvsStatus(): '
                . $payment->getCcAvsStatus()
            );
            $logHelper->log(
                'payment->getAdditionalInformation(): ' . PHP_EOL
                . var_export($payment->getAdditionalInformation(), 1)
            );

            $logHelper->log(
                'payment->getAdyenPspReference(): '
                . $payment->getAdyenPspReference()
            );
            $logHelper->log(
                'payment->getAdyenKlarnaNumber(): '
                . $payment->getAdyenKlarnaNumber()
            );
            $logHelper->log(
                'payment->getAdyenAvsResult(): '
                . $payment->getAdyenAvsResult()
            );
            $logHelper->log(
                'payment->getAdyenCvcResult(): '
                . $payment->getAdyenCvcResult()
            );
            $logHelper->log(
                'payment->getAdyenBoletoPaidAmount(): '
                . $payment->getAdyenBoletoPaidAmount()
            );
            $logHelper->log(
                'payment->getAdyenTotalFraudScore(): '
                . $payment->getAdyenTotalFraudScore()
            );
            $logHelper->log(
                'payment->getAdyenRefusalReasonRaw(): '
                . $payment->getAdyenRefusalReasonRaw()
            );
            $logHelper->log(
                'payment->getAdyenAcquirerReference(): '
                . $payment->getAdyenAcquirerReference()
            );
            $logHelper->log(
                '(possibly BIN?) payment->getAdyenAuthCode(): '
                . $payment->getAdyenAuthCode()
            );

            $logHelper->log(
                'payment->getInfo(): ' . PHP_EOL
                . var_export($payment->getInfo(), 1)
            );

            // paypal_avs_code,paypal_cvv2_match,paypal_fraud_filters,
            // avs_result,cvv2_check_result,address_verification,
            // postcode_verification,payment_status,pending_reason,payer_id,
            // payer_status,email,credit_card_cvv2, cc_avs_status,cc_approval,
            // cc_last4,cc_owner,cc_exp_month,cc_exp_year

            $sage = $order->getSagepayInfo();
            if (is_object($sage)) {
                // postcode_result,avscv2,address_status,payer_status
                $logHelper->log(
                    'sagepay->getLastFourDigits(): '
                    . $sage->getLastFourDigits()
                );
                $logHelper->log(
                    'sagepay->last_four_digits: '
                    . $sage->getData('last_four_digits')
                );
                $logHelper->log(
                    'sagepay->getCardType(): '
                    . $sage->getCardType()
                );
                $logHelper->log(
                    'sagepay->card_type: '
                    . $sage->getData('card_type')
                );
                $logHelper->log(
                    'sagepay->getAvsCv2Status: '
                    . $sage->getAvsCv2Status()
                );
                $logHelper->log(
                    'sagepay->address_result: '
                    . $sage->getData('address_result')
                );
                $logHelper->log(
                    'sagepay->getCv2result: '
                    . $sage->getCv2result()
                );
                $logHelper->log(
                    'sagepay->cv2result: '
                    . $sage->getData('cv2result')
                );
                $logHelper->log(
                    'sagepay->getAvscv2: '
                    . $sage->getAvscv2()
                );
                $logHelper->log(
                    'sagepay->getAddressResult: '
                    . $sage->getAddressResult()
                );
                $logHelper->log(
                    'sagepay->getPostcodeResult: '
                    . $sage->getPostcodeResult()
                );
                $logHelper->log(
                    'sagepay->getDeclineCode: '
                    . $sage->getDeclineCode()
                );
                $logHelper->log(
                    'sagepay->getBankAuthCode: '
                    . $sage->getBankAuthCode()
                );
                $logHelper->log(
                    'sagepay->getPayerStatus: '
                    . $sage->getPayerStatus()
                );
            }

            if ($gatewayName == 'optimal_hosted') {
                $optimalTransaction = unserialize(
                    $payment->getAdditionalInformation('transaction')
                );
                if ($optimalTransaction) {
                    $logHelper->log(
                        'Optimal transaction: '
                    );
                    $logHelper->log(
                        'transaction->cvdVerification: '
                        . $optimalTransaction->cvdVerification
                    );
                    $logHelper->log(
                        'transaction->houseNumberVerification: '
                        . $optimalTransaction->houseNumberVerification
                    );
                    $logHelper->log(
                        'transaction->zipVerification: '
                        . $optimalTransaction->zipVerification
                    );
                } else {
                    $logHelper->log('Optimal gateway but no transaction found');
                }
            }

        } catch (Exception $e) {
            $logHelper->logException($e);
        }

        return $this;
    }
}
