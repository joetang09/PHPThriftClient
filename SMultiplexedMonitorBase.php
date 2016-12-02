<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 7/3/15
 * Time: 19:57
 */

namespace thriftlib;

interface SMultiplexedMonitorBase
{

    function onConnFailed($host, $port, $msg);

    function onConnSuccess($host, $port);

    function onRemoteCallSuccess($host, $port, $server, $itf, $func, array $params, $code, $useTime);

    function onLocalCall($server, $itf, $func, array $params);

    function onRemoteCallFailed($host, $port, $server, $itf, $func, array $params, $msg, $runtime);

    function onCloseRemoteConn($host, $port);
}