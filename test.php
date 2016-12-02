<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 9/10/15
 * Time: 10:35
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "SThriftIft.php";

date_default_timezone_set('Asia/Chongqing');

$a = new SThriftIft();
$a->service = 'SServerCommon';
// $a->domain = 'rpc.user_profiler.zhangyoubao.com';
$a->host = '42.96.172.32';
$a->port = '7001';
$a->classNs = 'userProfiler';
$a->sendTimeOut = 1000;
$a->recvTimeOut = 1000;
$a->persist = false;

$a->doLog = true;

$a->init();
try {
    $r = $a->call(
        '\userProfiler\UserProfilerClient',
        'getCoinsInfo',
        array(
            6000000
        )
    );
//    $a->__destruct();
    var_dump($r);
} catch (\thriftlib\SThriftException $e)
{
var_dump($e);
}