<?php

require_once(Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . 'riskified_php_sdk' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Riskified' . DIRECTORY_SEPARATOR . 'autoloader.php');

class Riskified_Full_Test_Model_Environments extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test to make sure our environment options exist
     *
     * @test
     */
    public function testEnvironmentsExist()
    {
        $model = Mage::getModel('full/env');
        $environments = $model->toOptionArray();

        $this->assertEquals(
            count($environments),
            3
        );
    }
}