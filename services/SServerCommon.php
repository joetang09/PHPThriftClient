<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 7/3/15
 * Time: 13:53
 */

namespace thriftlib\services;

use \thriftlib\SServiceBase;
use \thriftlib\SThriftCli;

class SServerCommon extends SServiceBase
{
    public $genPath = SThriftCli::DEFAULT_GEN_PATH;
    public $classNs = '';
    public $downgradeCallEnable = false;

    public function __construct($host = 'localhost', $port = '0', $persist = false, $genPath = SThriftCli::DEFAULT_GEN_PATH, $classNs = '', $rBufferSize = SThriftCli::DEFAULT_READ_BUFFER_SIZE, $wBufferSize = SThriftCli::DEFAULT_WRITE_BUFFER_SIZE)
    {
        parent::__construct($host, $port, $persist, $rBufferSize, $wBufferSize);
    }

    protected function callDowngrade($itf, $func, array $params) {
        throw new SThriftException('no downgrade function', 404);
    }

    public function call($itf, $func, array $params) {
        return parent::call($itf, $func, $params);
    }
}