<?php

/**
 * Riskified Full quote submit request model.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
class Riskified_Full_Model_Request_Quote_Submit
    extends Riskified_Full_Model_Request_Abstract
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getEndpointAction()
    {
        return 'checkout_create';
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
        return $this->executeRequest($data);
    }
}
