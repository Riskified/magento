<?php

class Riskified_Full_Test_Helper_General extends EcomDev_PHPUnit_Test_Case
{
    /**
     * An example test that configures the extension before making any assertions
     *
     * @test
     * @loadFixture extensionConfigEnabled
     */
    public function testConfigurationValuesLoaded()
    {
        $helper = Mage::helper('full');

        $this->assertEquals(
            $helper->getConfigStatusControlActive(),
            1
        );

        $this->assertEquals(
            $helper->getConfigEnv(),
            'Riskified\Common\Env::DEV'
        );
    }
}