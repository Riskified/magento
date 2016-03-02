<?php
use Riskified\Common\Riskified;
use Riskified\Common\Env;
use Riskified\Common\Validations;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

class Riskified_Full_Adminhtml_RiskifiedfullController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order');
    }

    public function sendAction()
    {
        $id = $this->getRequest()->getParam('order_id');
        $call = Mage::getModel('full/observer');
        $call->postOrderIds(array($id));

        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$id)));
    }

    public function massSendAction()
    {
        $order_ids = $this->getRequest()->getParam('order_ids');
        $call = Mage::getModel('full/observer');
        $call->postOrderIds($order_ids);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/index"));
    }

    public function sendHistoricalOrdersAction() {
        ignore_user_abort(true);
        set_time_limit(0);

        $batch_size = 10;
        $page = $this->getRequest()->getParam('page', 1);

        if (!$page) {
            $page = 1;
        }

        include MAGENTO_ROOT . '/lib/riskified_php_sdk/src/Riskified/autoloader.php';

        $helper = Mage::helper('full');
        $authToken = $helper->getAuthToken();
        $env = constant($helper->getConfigEnv());
        $domain = $helper->getShopDomain();

        Riskified::init($domain, $authToken, $env, Validations::SKIP);

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('is_sent_to_riskified', 0);

        $total_count = $orders->getSize();

        $orders_collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('is_sent_to_riskified', 0)
            ->setPageSize($batch_size)
            ->setCurPage($page);

        $total_uploaded = 0;
        if($total_count > 0) {
            while ($total_uploaded < $total_count) {
                try {
                    Mage::helper('full/order')->postHistoricalOrders($orders_collection);
                    $total_uploaded += $orders_collection->count();
                    $page++;
                    $orders_collection = Mage::getModel('sales/order')
                        ->getCollection()
                        ->addFieldToFilter('is_sent_to_riskified', 0)
                        ->setPageSize($batch_size)
                        ->setCurPage($page);

                } catch (Exception $e) {
                    Mage::logException($e);
                    exit(1);
                }
            }

        }
        echo json_encode(array('success' => true, 'uploaded' => $total_uploaded));
    }
}