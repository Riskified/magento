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
        $data['authorization_error']['created_at'] = $this->getCurrentDateTime();

        return $this->executeRequest($data);
    }
}
