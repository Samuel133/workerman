<?php

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

require_once __DIR__ . '/Workerman/Autoloader.php';
$worker = new Worker();
// 进程启动时
$worker->onWorkerStart = function() {
    // 以websocket协议连接远程websocket服务器
    $ws_connection = new AsyncTcpConnection("ws://47.90.20.214:8999");
    // 连上后发送hello字符串
    $ws_connection->onConnect = function($connection) {
        $qishu = curl_post('m.vbxbx.com/tools/qishu_mysql.php',[]);
        //echo $qishu;
        $connection->send($qishu);
    };
    // 远程websocket服务器发来消息时
    $ws_connection->onMessage = function($connection, $data) {
        echo $data;
        $data_log = $data . date("Y-m-d H:i:s", time()) . "\n";
        file_put_contents('./data.logo', $data_log, FILE_APPEND);
        $data_json = json_decode($data, true);
        curl_post('m.vbxbx.com/tools/deal_data_pk.php', $data_json);
    };
    // 当连接远程websocket服务器的连接断开时
    $ws_connection->onClose = function($connection) {
	exec("/home/wwwroot/pk10_master/Channel/reload.sh");
    };
    // 设置好以上各种回调后，执行连接操作
    $ws_connection->connect();
};
function curl_post($url, $data, $timeout = 60) {
    $ch = curl_init();
    $o = "";
    foreach ($data as $k => $v) {
        $o .= "$k=" . urlencode($v) . "&";
    }
    $data = substr($o, 0, -1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $result = curl_exec($ch);
    curl_close($ch);
    if (is_string($result) && strlen($result)) {
        return $result;
    } else {
        return false;
    }
}
Worker::runAll();
