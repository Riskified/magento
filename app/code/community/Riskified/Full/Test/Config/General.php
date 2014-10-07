<?php

class Riskified_Full_Test_Config_General extends EcomDev_PHPUnit_Test_Case_Config
{

    /**
     * Make sure all of our rewrites are in effect
     *
     * @test
     */
    public function testRewrites()
    {
        $this->assertModelAlias('paygate/authorizenet', 'Riskified_Full_Model_Authorizenet');
    }

    /**
     * Make sure layout files are defined
     *
     * @test
     */
    public function testLayouts()
    {
        $this->assertLayoutFileDefined(
            'frontend', 'full.xml'
        );

        $this->assertLayoutFileDefined(
            'adminhtml', 'full.xml'
        );
    }

    /**
     * Make sure event observers related to status are properly defined
     *
     * @test
     */
    public function testStatusSyncEventObservers()
    {
        $this->assertEventObserverDefined(
            'global', 'riskified_full_order_update', 'full/observer', 'updateOrderState'
        );
    }

    /**
     * Make sure event observers related to auto-invoicing are properly defined
     *
     * @test
     */
    public function testAutoInvoiceEventObservers()
    {
        $this->assertEventObserverDefined(
            'global', 'riskified_full_order_update_approved', 'full/observer', 'autoInvoice'
        );

        $this->assertEventObserverDefined(
            'global', 'riskified_full_order_update_captured', 'full/observer', 'autoInvoice'
        );
    }
}