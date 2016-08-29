<?php

/**
 * Riskified Full failed order payment request model.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
class Riskified_Full_Model_Request_Order_Payment_Failed
    extends Riskified_Full_Model_Request_Abstract
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getEndpointAction()
    {
        return 'checkout_denied';
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data Array data.
     *
     * @return array
     */
    public function sendRequest(array $data)
    {
        $data = array_merge_recursive(
            $data,
            array(
                'checkout' => array(
                    'payment_details' => array(
                        'authorization_error' => array(
                            'created_at' => $this->getDateTime(),
                        ),
                    ),
                ),
            )
        );

        return $this->executeRequest($data);
    }
}
