<?php
use Riskified\Common\Riskified;
use Riskified\Common\Env;
use Riskified\Common\Validations;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

class Riskified_Full_Adminhtml_RiskifiedfullController extends Mage_Adminhtml_Controller_Action
{
    const MAX_HISTORICAL_FOR_PAGE = 1000;
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
        $resend = $this->getRequest()->getParam('resend', false);
        $orders = Mage::getModel('sales/order')->getCollection();
        $alreadySent = $this->getSentCollection();

        if(!$resend && count($alreadySent) > 0) {
            $orders->addFieldToFilter('entity_id', array('nin' => $alreadySent));
        }

        $total_count = $orders->getSize();

        if($total_count > self::MAX_HISTORICAL_FOR_PAGE) {
            $this->_enableCronJob();
            if($resend) {
                $this->_enableResendOrders();
            }
            Mage::app()->getStore()->resetConfig();
            echo json_encode(array('success' => true, 'by_cron' => true));
            return;
        }
        $orders_collection = Mage::getModel('sales/order')->getCollection();
        $orders_collection->getSelect()->order('entity_id DESC');
        $orders_collection->setPageSize($batch_size)->setCurPage($page);

        if(!$resend && count($alreadySent) > 0) {
            $orders_collection->addFieldToFilter('entity_id', array('nin' => $alreadySent));
        }

        $total_uploaded = 0;
        if($total_count > 0) {
            while ($total_uploaded < $total_count) {
                try {
                    Mage::helper('full/order')->postHistoricalOrders($orders_collection);
                    $total_uploaded += $orders_collection->count();

                    $orders_collection = Mage::getModel('sales/order')
                        ->getCollection()
                        ->setPageSize($batch_size)
                        ->setCurPage($page);
                    $orders_collection->getSelect()->order('entity_id DESC');

                    if(!$resend) {
                        $orders_collection->addFieldToFilter('entity_id', array('nin' => $this->getSentCollection()));
                    }
                    $page++;
                } catch (Exception $e) {
                    Mage::logException($e);
                    exit(1);
                }
            }
        }

        if($total_uploaded > 0) {
            $message = Mage::helper('full')->__('%s was sent to Riskified', $total_count);
        } else {
            $message = Mage::helper('full')->__('No new orders sent to Riskified');
        }
        echo json_encode(array('success' => true, 'uploaded' => $total_uploaded, 'message' => $message));
    }

    public function sendHistoricalOrdersStatusAction() {
        try {
            $_allorders = Mage::getModel('sales/order')->getCollection();
            $all = $_allorders->getSize();

            $orders = Mage::getModel('sales/order')->getCollection();
            $orders->addFieldToFilter('entity_id', array('nin' => $this->getSentCollection()));
            $sent = $orders->getSize();

            echo json_encode(array('success' => true, 'status' => ($sent/$all), 'total_sent' => $sent)); exit;
        } catch(Exception $e) {
            echo json_encode(array('success' => false)); exit;
        }
    }

    protected function getSentCollection() {
        $sentCollection = Mage::getModel('full/sent')->getCollection();
        $sentArray = array();

        foreach($sentCollection AS $entry) {
            $sentArray[] = $entry->getOrderId();
        }

        return array_unique($sentArray);
    }

    private function _enableCronJob() {
        Mage::getConfig()->saveConfig('riskified/cron/run_historical_orders', 1);
        $timecreated   = strftime("%Y-%m-%d %H:%M:%S",  mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
        $timescheduled = strftime("%Y-%m-%d %H:%M:%S", mktime(date("H"), date("i")+ 5, date("s"), date("m"), date("d"), date("Y")));

        try {
            $schedule = Mage::getModel('cron/schedule');
            $schedule->setJobCode("riskfied_full_upload_historical_orders")
                ->setCreatedAt($timecreated)
                ->setScheduledAt($timescheduled)
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
                ->save();
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save Cron expression'));
        }
    }

    private function _enableResendOrders() {
        Mage::getConfig()->saveConfig('riskified/cron/resend', 1);
    }
}