<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 6/1/15
 * Time: 14:22
 */

namespace thriftlib;

use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;
use Thrift\Transport\TFramedTransport;
use Thrift\Protocol\TMultiplexedProtocol;

class SMultiplexedThriftCli
{

    public $host = '';
    public $port = '';
    public $genPath = '';
    public $persist = false;
    public $classNs = array();

    const DEFAULT_READ_BUFFER_SIZE = 1024;
    const DEFAULT_WRITE_BUFFER_SIZE = 1024;

    public $sendTimeOut = 0;
    public $recvTimeOut = 0;

    public $rBufferSize = 0;
    public $wBufferSize = 0;

    const DEFAULT_GEN_PATH = './core/genfile/multi/';

    private $multiplexedProtocol = array();

    private $socket = null;
    private $transport = null;
    private $protocol = null;

    private $loader = null;

    public function __construct($host = 'localhost', $port = '0', $persist = false, $genPath = self::DEFAULT_GEN_PATH, array $classNs, $rBufferSize = self::DEFAULT_READ_BUFFER_SIZE, $wBufferSize = self::DEFAULT_WRITE_BUFFER_SIZE)
    {
        $this->host = $host;
        $this->port = $port;
        $this->persist = $persist;
        if ($genPath == self::DEFAULT_GEN_PATH)
        {
            $this->genPath =  str_replace("/", DIRECTORY_SEPARATOR, $genPath);
        } else {
            $this->genPath = $genPath;
        }

        $this->rBufferSize = $rBufferSize;
        $this->wBufferSize = $wBufferSize;
        $this->classNs = $classNs;
    }

    public function init()
    {

    }

    public function conn()
    {
        if (!$this->isIp($this->host)) {
            throw new SThriftException('SMultiplexedThrift.host must be ip.');
        }
        if ($this->port < 0 || $this->port > 65535)
        {
            throw new SThriftException('SMultiplexedThrift.port must be port ([0, 65535])');
        }
        $this->loader = new ThriftClassLoader();

        if (strpos($this->genPath, DIRECTORY_SEPARATOR) != 0)
        {
            $this->genPath = __DIR__ . DIRECTORY_SEPARATOR . $this->genPath;
        }
        if (strrpos($this->genPath, DIRECTORY_SEPARATOR) != (strlen($this->genPath) - 1))
        {
            $this->genPath .= DIRECTORY_SEPARATOR;
        }
        if (!is_array($this->classNs))
        {
            throw new SThriftException('SMultiplexedThrift.classNs must array');
        }
        $this->loader->registerDefinition('thriftcommon', $this->genPath);
        foreach ($this->classNs as $server => $ns)
        {
            $this->loader->registerDefinition((empty($ns)) ? '\\' : $ns, $this->genPath);
        }
        $this->loader->register();
        $this->socket = new TSocket($this->host, $this->port, $this->persist);
        if ($this->sendTimeOut > 0)
            $this->socket->setSendTimeout($this->sendTimeOut);
        if ($this->recvTimeOut > 0)
            $this->socket->setRecvTimeout($this->recvTimeOut);
        $this->transport = new TFramedTransport($this->socket, $this->rBufferSize <= 0 ? self::DEFAULT_READ_BUFFER_SIZE : $this->rBufferSize, $this->wBufferSize <= 0 ? self::DEFAULT_WRITE_BUFFER_SIZE : $this->wBufferSize);
        $this->protocol = new TBinaryProtocol($this->transport);
        foreach ($this->classNs as $server => $ns)
        {
            $this->multiplexedProtocol[$server] = new \Thrift\Protocol\TMultiplexedProtocol($this->protocol, $server);;
        }
        return $this;
    }

    public function setClass($class)
    {
        $class =  new $class($this->getProtocol());
        return $class;
    }

    public function getMultiplexedProtocol($server)
    {
        if (!isset($this->multiplexedProtocol[$server]))
        {
            throw new SThriftException('can not find the server');
        }
        return $this->multiplexedProtocol[$server];
    }

    public function transportOpen()
    {
        $this->transport->open();
    }

    public function transportClose()
    {
        $this->transport->close();
    }

    private function isIp($str)
    {
        if($str == 'localhost' || preg_match("/[\d]{2,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/", $str))
            return true;
        return false;
    }

}