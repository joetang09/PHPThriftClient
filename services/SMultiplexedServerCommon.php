<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 7/3/15
 * Time: 13:53
 */

namespace thriftlib\services;

use \thriftlib\SMultiplexedServiceBase;
use \thriftlib\SMultiplexedThriftCli;
use \thriftlib\SThriftCli;

class SMultiplexedServerCommon extends SMultiplexedServiceBase
{
    public $genPath = SMultiplexedThriftCli::DEFAULT_GEN_PATH;
    public $classNs = array();
    public $downgradeCallEnable = false;

    public function __construct($host = 'localhost', $port = '0', $persist = false, $genPath = SThriftCli::DEFAULT_GEN_PATH, array $classNs = array(), $rBufferSize = SThriftCli::DEFAULT_READ_BUFFER_SIZE, $wBufferSize = SThriftCli::DEFAULT_WRITE_BUFFER_SIZE)
    {
        parent::__construct($host, $port, $persist, $rBufferSize, $wBufferSize);
    }

    protected function callDowngrade($server, $itf, $func, array $params) {
        throw new SThriftException('no downgrade function', 404);
    }

    public function call($server, $itf, $func, array $params) {
        return parent::call($server, $itf, $func, $params);
    }
}