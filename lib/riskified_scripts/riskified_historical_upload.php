    <?php
// if this is not run from the Magento root, use chdir() to move execution back.
//chdir('../magento/');
 
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
require 'app/Mage.php';

umask (0);
Mage::app(); // can set the run code/type, just like in the Mage::run() call
 
$helper = Mage::helper('full');
$authToken = $helper->getAuthToken();
$env = constant($helper->getConfigEnv());
$domain = $helper->getShopDomain();

echo "Riskified auth token: $authToken \n";
echo "Riskified shop domain: $domain \n";
echo "Riskified target environment: $env \n";
echo "*********** \n";
include __DIR__ . 'lib/riskified_php_sdk/src/Riskified/autoloader.php';
use Riskified\Common\Riskified;
use Riskified\Common\Env;
use Riskified\Common\Validations;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

Riskified::init($domain, $authToken, $env, Validations::SKIP);
// add your own code below:
$batch_size = 10;
$options = getopt("p::");
$page = intval($options["p"]);
if (!$page) {
    $page = 1;
}
$orders = Mage::getModel('sales/order')->getCollection();

$total_count = $orders->getSize();

echo "starting to upload orders, total_count: $total_count \n";

$orders_collection = Mage::getModel('sales/order')
    ->getCollection()
    ->setPageSize($batch_size)
    ->setCurPage($page);

$orders_collection->getSelect()->order('entity_id DESC');
$total_uploaded = 0;

while ($total_uploaded < $total_count) {
    $last_id = $orders_collection->getLastItem()->getEntityId();

    try {
    	Mage::helper('full/order')->postHistoricalOrders($orders_collection);

        $total_uploaded += $orders_collection->count();
        echo "last id: $last_id, page:$page, total_uploaded: $total_uploaded \n";
        $page++;
        $orders_collection = Mage::getModel('sales/order')
            ->getCollection()
            ->setPageSize($batch_size)
            ->setCurPage($page);

        $orders_collection->getSelect()->order('entity_id DESC');
    } catch (Exception $e) {
        echo "Error: ".$e->getMessage();
        exit(1);
    }
}
echo "*********** \n";
echo "wOOt, Finished successfully!!!! total_uploaded: $total_uploaded\n";
echo "Please let us know and we will process the order within 24 hours, Thanks!!\n";
?>
