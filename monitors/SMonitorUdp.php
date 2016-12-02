<?php 
/**
 *
 * @date   2016-07-18 18:52
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace thriftlib\monitors;

use thriftlib\SMonitorBase;

class SMonitorUdp implements SMonitorBase
{

    public $host = '';
    public $port = '';

    private $logStack = array();

    private $localIp = "-";

    private $socket;

    public function init()
    {
        
        
        if (isset($_SERVER) && isset($_SERVER["SERVER_ADDR"]))
        {
            $this->localIp = $_SERVER["SERVER_ADDR"];
        }
    }

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
            $this->localIp
            . "\t" . 'rpc_' . $serverName
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
        if (!empty($this->logStack))
        {
            $msg = implode("\n", $this->logStack) . "\n";
            try {
                if (!$this->socket)
                {
                    $this->socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                }
                if ($this->socket)
                {
                    $r = @socket_sendto($this->socket, $msg, strlen($msg), 0, $this->host, $this->port);
                }
                
            } catch (\Exception $ex) {

            }
            
        }
        $this->clearLogStack();

    }

    public function clearLogStack() 
    {
        $this->logStack = array();
    }

}