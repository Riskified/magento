<?php

/**
 * Riskified Full order creditmemo save request model.
 *
 * @category Riskified
 * @package  Riskified_Full
 * @author   Piotr Pierzak <piotrek.pierzak@gmail.com>
 */
class Riskified_Full_Model_Request_Order_Creditmemo_Save
    extends Riskified_Full_Model_Request_Abstract
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getEndpointAction()
    {
        return 'refund';
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
        foreach ($data['order']['refunds'] as &$refund) {
            $refund['refunded_at'] = $this->getDateTime($refund['refunded_at']);
        }

        return $this->executeRequest($data);
    }
}
