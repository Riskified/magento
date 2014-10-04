<?php
error_reporting(E_ALL | E_STRICT);
define('MAGENTO_ROOT', getcwd());

$compilerConfig = MAGENTO_ROOT . '/includes/config.php';
if (file_exists($compilerConfig)) {
    include $compilerConfig;
}
$mageFilename = MAGENTO_ROOT . '/app/Mage.php';

if (!file_exists($mageFilename)) {
    if (is_dir('downloader')) {
        header("Location: downloader");
    } else {
        echo $mageFilename." was not found";
    }
    exit;
}

require_once $mageFilename;
ini_set('display_errors', 1);
umask(0);

Mage::init('admin');

$core_session = Mage::getSingleton('core/session', array('name'=>'adminhtml'));
$uid = Mage::helper('adminhtml')->getCurrentUserId();
$logged_in = Mage::getSingleton('admin/session')->isLoggedIn();
if (!($uid || $logged_in)) {
    header('HTTP/1.1 401 Unauthorized', true, 401);
    echo '401 Unauthorized'.PHP_EOL;
    exit(401);
}

### End of Magneto init ###

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $body = json_decode($HTTP_RAW_POST_DATA);
    $models = Mage::getModel('sales/order')->getCollection()
        ->addAttributeToSort('increment_id', 'ASC')
        ->addFieldToFilter('increment_id', array('from'=>$body->min_id,'to'=>$body->max_id))
        ->setPage($body->page + 1, $body->page_size);

    try {
        echo Mage::helper('full/order')->postHistoricalOrders($models);
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error", true, 500);
        echo $e->getMessage();
        exit(500);
    }

} else {

    $slice_size = 3;

    function models_slice($sort='ASC', $limit=0) {
        $all_models = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToSelect('increment_id')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('base_grand_total')
                ->addAttributeToSelect('grand_total')
                ->addAttributeToSelect('status');

        if (array_key_exists('min_id', $_GET) && strlen($_GET['min_id']) > 0)
            $all_models = $all_models->addFieldToFilter('increment_id', array('gteq'=>$_GET['min_id']));
        if (array_key_exists('max_id', $_GET) && strlen($_GET['max_id']) > 0)
            $all_models = $all_models->addFieldToFilter('increment_id', array('lteq'=>$_GET['max_id']));

        if ($limit > 0)
            $all_models->getSelect()->order('increment_id '.$sort)->limit($limit);

        return $all_models;
    }

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

    $head_orders = array();
    foreach (models_slice('ASC', $slice_size) as $model)
        $head_orders[] = hash_from_model($model);

    $tail_orders = array();
    foreach (models_slice('DESC', $slice_size) as $model)
        $tail_orders[] = hash_from_model($model);

    $min_id = $head_orders[0]['id'];
    $max_id = $tail_orders[0]['id'];
    $tail_orders = array_reverse($tail_orders);
    $orders_count = models_slice()->count();

    if ($orders_count <= $slice_size*2) {
        $head_orders = array_unique(array_merge($head_orders, $tail_orders), SORT_REGULAR);
        $tail_orders = array();
    }

?>

<html>
<head><title>submit historical orders</title></head>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script>
    // TODO: chunk here or before?
    var min_id = '<?= $min_id ?>';
    var max_id = '<?= $max_id ?>';
    var page_size = 10;
    var orders_count = <?= $orders_count ?>;
    var page = 0;

    function update_progress_bar() {
        var percent = Math.min(100, 100 * (page * page_size / orders_count));
        var gradient = 'linear-gradient(to left, #c8c8c8 ' + (100-percent) + '%, #9bc474 0%)';
        var bar = $('#progress_bar');
        bar.css('background', gradient);
        bar.text(percent + '%');
    }

    function error_progress_bar() {
        var bar = $('#progress_bar');
        bar.css('background', '#DD0000');
        bar.text('ERROR');
    }

    function append_to_console(data) {
        $('#console').text($('#console').text() + data);
    }

    function send_clicked() {
        $('#send_button').hide();
        $('#progress_bar').show();
        send_page();
    }

    function send_page() {
        var range = {
            min_id: min_id,
            max_id: max_id,
            page: page,
            page_size: page_size
        };
        append_to_console("Uploading orders...\n");
        $.ajax({
            type: "POST",
            data: JSON.stringify(range),
            success: function(data, textStatus, jqXHR) {
                page++;
                update_progress_bar();
                if (page * page_size < orders_count)
                    send_page();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(errorThrown);
                error_progress_bar();
            },
            complete: function(jqXHR, textStatus) {
                append_to_console(jqXHR.responseText);
            },
            contentType: 'application/json'
        });
    }

    $(document).ready(function() {
        $('#progress_bar').hide();
        update_progress_bar();
    });

</script>
<style>
    body {
        margin-left: 1%;
    }

    #logo {
        float: left;
        height: 1.5em;
        margin-top: 5px;
        margin-right: 10px;
    }

    #send_actions {
        height: 40px;
    }

    #send_button {
        font-size: 1em;
        line-height: 1.5em;
    }

    #progress_bar {
        border: #4d4d4d 1px solid;
        width: 40%;
        font-family: sans-serif;
        height: 30px;
        line-height: 30px;
        border-radius: 5px;
        text-align: center;
    }

    table {
        min-width: 95%;
        border-spacing: 0;
        border: black 2px solid;
    }

    td, th {
        border: black 1px solid;
        padding: 5px;
    }

    th {
        background-color: #F1F1F1;
    }

    #arrow_row {
        text-align: center;
        border: none;
        background-color: #F1F1F1;
        font-size: 30px;
    }

    #console {
        background-color: black;
        color: #37DF37;
        width: 85%;
        height: 190px;
        padding: 10px;
        overflow: auto;
        white-space: pre-wrap;
    }
</style>
<body>
<img id="logo" src="http://www.riskified.com/images/logo.svg">
<h1>/ Upload Historical Orders</h1>

<div id="range">
    <h3>Range:</h3>
    <form onsubmit="$('#sample').fadeTo(100,0.1)">
        <input class="filter" type="text" name="min_id" value="<?= $min_id ?>">
        &#8594;
        <input class="filter" type="text" name="max_id" value="<?= $max_id ?>">
        <input type="submit" value="filter">
        <input type="submit" onclick="$('input.filter').val('');" value="reset">
    </form>
</div>


<div id="sample">
    <h3>Sample:</h3>

    <table id="sample_table">
        <tr>
            <th class="id">Order #</th>
            <th class="created_at">Purchased On</th>
            <th class="billing_name">Bill to Name</th>
            <th class="shipping_name">Ship to Name</th>
            <th class="base_gt">G.T. (Base)</th>
            <th class="gt">G.T. (Purchased)</th>
            <th class="status">Status</th>
        </tr>
    <? foreach ($head_orders as $order_hash) { ?>
        <tr>
            <td class="id"><?= $order_hash['id'] ?></td>
            <td class="created_at"><?= $order_hash['created_at'] ?></td>
            <td class="billing_name"><?= $order_hash['billing_name'] ?></td>
            <td class="shipping_name"><?= $order_hash['shipping_name'] ?></td>
            <td class="base_gt"><?= $order_hash['base_gt'] ?></td>
            <td class="gt"><?= $order_hash['gt'] ?></td>
            <td class="status"><?= $order_hash['status'] ?></td>
        </tr>
    <? }
    if (count($tail_orders) > 0) { ?>
        <tr>
            <td id="arrow_row" colspan="7">&#10507;</td>
        </tr>
    <? }
    foreach ($tail_orders as $order_hash) { ?>
        <tr>
            <td class="id"><?= $order_hash['id'] ?></td>
            <td class="created_at"><?= $order_hash['created_at'] ?></td>
            <td class="billing_name"><?= $order_hash['billing_name'] ?></td>
            <td class="shipping_name"><?= $order_hash['shipping_name'] ?></td>
            <td class="base_gt"><?= $order_hash['base_gt'] ?></td>
            <td class="gt"><?= $order_hash['gt'] ?></td>
            <td class="status"><?= $order_hash['status'] ?></td>
        </tr>
    <? } ?>
    </table>

    <br>

    <div id="send_actions">
        <button id="send_button" onclick="send_clicked()">upload <?= $orders_count ?> orders</button>
        <div id="progress_bar">0%</div>
    </div>
</div>

<h3>Console:</h3>
<pre id="console"></pre>
</body>
</html>

<? }