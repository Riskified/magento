<?php
/**
 * Error reporting
 */
error_reporting(E_ALL | E_STRICT);

/**
 * Compilation includes configuration file
 */
define('MAGENTO_ROOT', getcwd());

$compilerConfig = MAGENTO_ROOT . '/includes/config.php';
if (file_exists($compilerConfig)) {
    include $compilerConfig;
}

$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
$maintenanceFile = 'maintenance.flag';

if (!file_exists($mageFilename)) {
    if (is_dir('downloader')) {
        header("Location: downloader");
    } else {
        echo $mageFilename." was not found";
    }
    exit;
}

if (file_exists($maintenanceFile)) {
    include_once dirname(__FILE__) . '/errors/503.php';
    exit;
}

require_once $mageFilename;

if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
    Mage::setIsDeveloperMode(true);
}

ini_set('display_errors', 1);

umask(0);

Mage::init('admin');
//session_start();
Mage::getSingleton('core/session', array('name'=>'adminhtml'));
$admin_session = Mage::getSingleton('admin/session');
if (!$admin_session->isLoggedIn()) {
    echo 'unauthorized';
    exit(1);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $body = json_decode($HTTP_RAW_POST_DATA);
    $models = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('increment_id', array("in"=>$body->order_ids));

    $helper = Mage::helper('full/order');
    $helper->postHistoricalOrders($models);

    echo $models->count();

} else {

    function all_models() {
        $all_models = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToSelect('increment_id')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('base_grand_total')
                ->addAttributeToSelect('grand_total')
                ->addAttributeToSelect('status');

        if (array_key_exists('min_id', $_GET))
            $all_models = $all_models->addFieldToFilter('increment_id', array('gteq'=>$_GET['min_id']));
        if (array_key_exists('max_id', $_GET))
            $all_models = $all_models->addFieldToFilter('increment_id', array('lteq'=>$_GET['max_id']));

        return $all_models;
    }

    $head_models = all_models();
    $head_models->getSelect()->order('increment_id ASC')->limit('3');
    $tail_models = all_models();
    $tail_models->getSelect()->order('increment_id DESC')->limit('3');

    function hash_from_model($model) {
        return array(
            'id' => $model->getIncrementId(),
            'created_at' => $model->getCreatedAt(),
            'billing_name' => $model->getBillingAddress()->getName(),
            'shipping_name' => $model->getShippingAddress()->getName(),
            'base_gt' => $model->getBaseGrandTotal(),
            'gt' => $model->getGrandTotal(),
            'status' => $model->getStatus()
        );
    }
    $order_hashes = array();
    foreach ($head_models as $model)
        $order_hashes[] = hash_from_model($model);
    foreach ($tail_models as $model)
        $order_hashes[] = hash_from_model($model);

?>
<html ng-app="uploader">
<head><title>submit historical orders</title></head>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.0-rc.1/angular.min.js"></script>
<script>
    // TODO: chunk here or before?
    var order_ids_chunks = <? echo json_encode(array_chunk($order_hashes, 1)) ?>;
    var batch_length = order_ids_chunks.length;
    var batch = 0;

    function update_progress_bar() {
        var percent = 100 * (batch/batch_length);
        var gradient = 'linear-gradient(to left, #c8c8c8 ' + (100-percent) + '%, #9bc474 0%)';
        var bar = $('#progress_bar');
        bar.css('background', gradient);
        bar.text(percent + '%');
    }

    function send_clicked() {
        $('#send_button').hide();
        $('#progress_bar').show();
        send_batch();
    }

    function get_next_batch() {
        return order_ids_chunks[batch++];
    }

    function send_batch() {
        var json = JSON.stringify({ "order_ids" : get_next_batch() });
        $.ajax({
            type: "POST",
            data: json,
            success: function($data, $textStatus, $jqXHR) {
                console.log($data);
                update_progress_bar();
                if (batch < batch_length)
                    send_batch();
            },
            contentType: 'application/json'
        });
    }

    $(document).ready(function() {
        $('#progress_bar').hide();
        update_progress_bar();
    });

    angular.module('uploader', [])
        .controller('OrderController', ['$scope', function($scope) {
            $scope.orders = [
<?
foreach ($order_hashes as $order_hash) {
    if (!isset($first))
        $first = false;
    else
        echo ',';
    echo PHP_EOL.json_encode($order_hash);
}
?>
            ];

            $scope.bounds = {
                min: $scope.orders[0].id,
                max: $scope.orders[$scope.orders.length - 3].id
            };

            $scope.query = $.extend(true, {}, $scope.bounds);

            $scope.rangeFilter = function (item) {
                if ($scope.query.min > $scope.bounds.max || $scope.query.max < $scope.bounds.min)
                    return true;
                return (item.id >= $scope.query.min && item.id <= $scope.query.max);
            };

        }]);

</script>
<style>
    #progress_bar {
        border: #4d4d4d 1px solid;
        width: 40%;
        margin: auto;
        height: 30px;
        line-height: 30px;
        border-radius: 5px;
        text-align: center;
    }
</style>
<body>


<div ng-controller="OrderController">
    <h3>found {{(orders | filter:rangeFilter).length}} orders</h3>

    <input ng-model="query.min"><br>
    <input ng-model="query.max">

    <div ng-repeat="order in orders | filter:rangeFilter | limitTo:3 |  orderBy:'id'">
        <span class="id">{{order.id}}</span>
        <span class="id">{{order.created_at}}</span>
        <span class="id">{{order.billing_name}}</span>
        <span class="id">{{order.shipping_name}}</span>
        <span class="id">{{order.base_gt}}</span>
        <span class="id">{{order.gt}}</span>
        <span class="id">{{order.status}}</span>
    </div>

    <div>to</div>

    <div ng-repeat="order in orders | filter:rangeFilter | limitTo:-3 |  orderBy:'id'">
        <span class="id">{{order.id}}</span>
        <span class="id">{{order.created_at}}</span>
        <span class="id">{{order.billing_name}}</span>
        <span class="id">{{order.shipping_name}}</span>
        <span class="id">{{order.base_gt}}</span>
        <span class="id">{{order.gt}}</span>
        <span class="id">{{order.status}}</span>
    </div>

</div>


<button id="send_button" onclick="send_clicked()">send</button>
<div id="progress_bar">0%</div>

</body>
</html>

<? }