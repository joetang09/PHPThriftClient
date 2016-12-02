<?php
/**
 *
 * @date   2016-07-18 19:27 
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace thriftlib\monitors;

use thriftlib\SMonitorBase;

class SMonitorFile implements SMonitorBase
{

    private $logStack = array();

    public $fileName = '';




    public function onConnFailed($serverName, $host, $port, $msg)
    {
        $this->buildLogs($serverName, 'CONNECTION', 401, null, $host, $port, null, $msg);
    }

    public function onConnSuccess($serverName, $host, $port)
    {
        $this->buildLogs($serverName, 'CONNECTION', 200, null, $host, $port, null, null);
    }

    public function onRemoteCallSuccess($serverName, $host, $port, $itf, $func, array $params, $code, $useTime)
    {
        $this->buildLogs($serverName, $itf . '::' . $func . '?p=' . json_encode($params), 200, $useTime, $host, $port, $code, null);
    }

    public function onLocalCall($serverName, $itf, $func, array $params)
    {
        $this->buildLogs($serverName, $itf . '::' . $func . '?p=' . json_encode($params), 200, null, null, null, null, null);
    }

    public function onRemoteCallFailed($serverName, $host, $port, $itf, $func, array $params, $msg, $runtime)
    {
        $this->buildLogs($serverName, $itf . '::' . $func . '?p=' . json_encode($params), 501, null, $host, $port, $runtime, $msg);
    }

    public function onCloseRemoteConn($serverName, $host, $port)
    {

    }


    private function buildLogs($serverName, $request, $status, $useTime, $host, $port, $remoteCode, $msg)
    {
        $this->logStack[] =
            'rpc_' . $serverName
            . "\t" . null
            . "\t" . '[' . date('d/M/Y:H:i:s O') . ']'
            . "\t" . '"RPC ' . $request . ' THRIFT/0.9.2"'
            . "\t" . $status
            . "\t" . '-'
            . "\t" . $useTime
            . "\t" . '"' . $host . ':' . $port . '"'
            . "\t" . $remoteCode
            . "\t" . '"' . $msg . '"'
            . "\t" . getmypid();
    }

    public function flushLog()
    {
        if (!empty($this->fileName) && is_writable($this->fileName))
        {
            file_put_contents($this->fileName, implode("\n", $this->logStack) . "\n", FILE_APPEND);
        }
        $this->clearLogStack();

    }

    public function clearLogStack() 
    {
        $this->logStack = array();
    }


}