<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 7/3/15
 * Time: 19:57
 */

namespace thriftlib;

interface SMonitorBase
{

    function onConnFailed($serverName, $host, $port, $msg);

    function onConnSuccess($serverName, $host, $port);

    function onRemoteCallSuccess($serverName, $host, $port, $itf, $func, array $params, $code, $useTime);

    function onLocalCall($serverName, $itf, $func, array $params);

    function onRemoteCallFailed($serverName, $host, $port, $itf, $func, array $params, $msg, $runtime);

    function onCloseRemoteConn($serverName, $host, $port);

    function clearLogStack();
}
