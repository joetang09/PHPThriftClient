<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 7/3/15
 * Time: 19:57
 */

namespace thriftlib;


use thriftlib\monitors\SMonitorPipe;
use thriftlib\Domain;

abstract class SMultiplexedServiceBase implements SMonitorBase {

    public $domain = '';

    public $host = '';
    public $port = '';
    public $persist = false;
    public $serverName = '';
    public $sendTimeOut = 0;
    public $recvTimeOut = 0;
    public $rBufferSize = 0;
    public $wBufferSize = 0;
    public $monitor = array();
    public $downgradeCallEnable = true;
    public $logDiv = "\t";

    public $baseLogPath = __DIR__;

    public $doLog = true;

    private $beginTime = 0;

    protected $sthriftCli = null;

    public $genPath = SMultiplexedThriftCli::DEFAULT_GEN_PATH;
    protected $classNs = '';

    protected $className = array();

    protected $itf = array();

    protected $isStarted = false;

    protected $monitorObj = array();

    private $logStack = array();

    const OP_CONN_SUCCESS = 1;
    const OP_CONN_FAILED = -1;

    const OP_CALL_SUCCESS = 2;
    const OP_CALL_FAILED = -2;

    const OP_LOCAL_CALL = 3;

    public function __construct($host = 'localhost', $port = '0', $persist = false, $rBufferSize = SThriftCli::DEFAULT_READ_BUFFER_SIZE, $wBufferSize = SThriftCli::DEFAULT_WRITE_BUFFER_SIZE)
    {
        // $this->monitorObj = new SMonitorPipe();
        $this->beginTime = time();
        $this->host = $host;
        $this->port = $port;
        $this->persist = $persist;
        $this->rBufferSize = $rBufferSize;
        $this->wBufferSize = $wBufferSize;
    }

    private function initMonitor()
    {
        if (is_array($this->monitor))
        {
            foreach ($this->monitor as $valarr) {
                if (!is_array($valarr) || !isset($valarr['class']))
                {
                    continue;
                }
                $class = $valarr['class'];
                if (!class_exists($class))
                {
                    continue;
                }
                $class = new $class;
                if (!$class instanceof SMonitorBase)
                {
                    continue;
                }
                foreach ($valarr as $k => $v) {
                    if ($k == 'class')
                    {
                        continue;
                    }
                    if (property_exists($class, $k))
                    {
                        $class->$k = $v;
                    }
                }
                if (method_exists($class, 'init'))
                {
                    $class->init();
                }
                $this->monitorObj[] = $class;
            }
        }
        if (empty($this->monitorObj))
        {
            $this->monitorObj[] = new SMonitorPipe();
        }
    }

    public function init()
    {
        $this->initMonitor();
        if (!empty($this->domain))
        {
            $newHost = Domain::getInstance()->searchHost($this->domain);
            if ($newHost)
            {
                $this->host = $newHost;
            }
        }
        $this->sthriftCli = new SMultiplexedThriftCli($this->host, $this->port, $this->persist, $this->genPath, $this->classNs, $this->rBufferSize, $this->wBufferSize);
        $this->sthriftCli->sendTimeOut = $this->sendTimeOut;
        $this->sthriftCli->recvTimeOut = $this->recvTimeOut;
        $this->start();
    }

    public function setMonitor(SMonitorBase $monitorObj)
    {
        $this->monitorObj[] = $monitorObj;
    }

    protected function start()
    {
        try {
            $this->sthriftCli->conn()->transportOpen();
            $this->isStarted = true;
            $this->onConnSuccess($this->serverName, $this->host, $this->port);
            return true;
        } catch (\Exception $ex) {
            $this->onConnFailed($this->serverName, $this->host, $this->port, $ex->getMessage());
        }
        return false;

    }

    protected function call($server, $itf, $func, array $params)
    {
        if ($this->isStarted)
        {
            $md5Val = md5($server . $itf);
            if (!isset($this->itf[$md5Val]))
            {
                $this->itf[$md5Val] = new $itf($this->sthriftCli->getMultiplexedProtocol($server));
            }
            $startTime = microtime(true);
            try {
                $result = call_user_func_array(array($this->itf[$md5Val], $func), $params);
                $endTime = microtime(true);
                $this->onRemoteCallSuccess($this->serverName, $this->host, $this->port, $server . '::' . $itf, $func, $params, isset($result->code) ? $result->code: 1, $endTime - $startTime);
                return $result;
            } catch (\Exception $ex) {
                $endTime = microtime(true);
                $this->onRemoteCallFailed($this->serverName, $this->host, $this->port, $server . '::' . $itf, $func, $params, $ex->getMessage(), $endTime - $startTime);
                if ($this->downgradeCallEnable)
                {
                    $this->onLocalCall($this->serverName, $server . '::' . $itf, $func, $params);
                    return $this->callDowngrade($server, $itf, $func, $params);
                }
                else
                {
                    throw new SThriftException('call failed : ' . $ex->getMessage(), 1001);
                }
            }
        }
        else if ($this->downgradeCallEnable)
        {
            $this->onLocalCall($this->serverName, $server . '::' . $itf, $func, $params);
            return $this->callDowngrade($server, $itf, $func, $params);
        }
        else
        {
            throw new SThriftException('call exception', 1002);
        }
    }

    protected abstract function callDowngrade($server, $itf, $func, array $params);

    protected function end()
    {
        if ($this->isStarted)
        {
            $this->sthriftCli->transportClose();
            $this->onCloseRemoteConn($this->serverName, $this->host, $this->port);
            $this->isStarted = false;
        }
    }

    public function __destruct()
    {
        $this->end();
        $this->flushLog();
    }

    function onConnFailed($serverName, $host, $port, $msg)
    {
        $this->buildLog(time(), $host, $port, self::OP_CONN_FAILED, null, null, array(null), -500, null, $msg);
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m) {
                $m->onConnFailed($serverName, $host, $port, $msg);
            }
        }
    }

    function onConnSuccess($serverName, $host, $port)
    {
        $this->buildLog(time(), $host, $port, self::OP_CONN_SUCCESS, null, null,  array(null), 1, null, null);
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m) {
                $m->onConnSuccess($serverName, $host, $port);
            }
        }
    }

    function onRemoteCallSuccess($serverName, $host, $port, $itf, $func, array $params, $code, $useTime)
    {
        $this->buildLog(time(), $host, $port, self::OP_CALL_SUCCESS, $itf, $func, $params, $code, $useTime, null);
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m) {
                $m->onRemoteCallSuccess($serverName, $host, $port, $itf, $func, $params, $code, $useTime);
            }
        }
    }

    function onLocalCall($serverName, $itf, $func, array $params)
    {
        $this->buildLog(time(), null, null, self::OP_LOCAL_CALL, $itf, $func, $params, 1, null, null);
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m) {
                $m->onLocalCall($serverName, $itf, $func, $params);
            }
        }
    }

    function onRemoteCallFailed($serverName, $host, $port, $itf, $func, array $params, $msg, $runtime)
    {
        $this->buildLog(time(), $host, $port, self::OP_CALL_FAILED, $itf, $func, $params, -500, $runtime, $msg);
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m) {
                $m->onRemoteCallFailed($serverName, $host, $port, $itf, $func, $params, $msg, $runtime);
            }
        }
    }

    function onCloseRemoteConn($serverName, $host, $port)
    {
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m) {
                $m->onCloseRemoteConn($serverName, $host, $port);
            }
        }
    }

    private function flushLog()
    {
        if (!$this->doLog) return;
        $logPath = $this->baseLogPath . DIRECTORY_SEPARATOR . 'thriftMultiplexedCall' . DIRECTORY_SEPARATOR . $this->serverName;

        if (!is_dir($logPath) && !mkdir($logPath, 0777,true)) return ;
        file_put_contents($logPath . DIRECTORY_SEPARATOR . date('YmdH', $this->beginTime) . '.log', implode("\n", $this->logStack) . "\n", FILE_APPEND);
        
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m)
            {
                $m->flushLog();
            }
            
        }
        $this->clearLogStack();


    }

    private function buildLog($time, $host, $port, $op, $itf, $func, array $params, $retCode, $useTime, $msg)
    {
        $this->logStack[] = date('YmdHis', $time) . $this->logDiv
            . $host . $this->logDiv
            . $port . $this->logDiv
            . $op . $this->logDiv
            . $itf . $this->logDiv
            . $func . $this->logDiv
            . json_encode($params)
            . $this->logDiv . $retCode
            . $this->logDiv . $useTime
            . $this->logDiv . $msg
            . $this->logDiv . getmypid();
    }

    public function clearLogStack()
    {
        $this->logStack = array();
        if (!empty($this->monitorObj))
        {
            foreach ($this->monitorObj as $m)
            {
                $m->clearLogStack();
            }
            
        }
    }

}