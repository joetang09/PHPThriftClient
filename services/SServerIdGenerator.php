<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 7/3/15
 * Time: 13:53
 */

namespace thriftlib\services;

use \thriftlib\SServiceBase;

class SServerIdGenerator extends SServiceBase
{

    const INDEX_MAX = 2048;
    const MACHINE_MAX = 1024;
    private $crtIndex = 0;
    protected $classNs = 'anzoservant';
    public $persist = true;
    public $serverName = 'idGenerator';

    private $class = '\anzoservant\AnZoServantClient';
    private $func = 'generateID';

    protected function callDowngrade($itf, $func, array $params) {
        if ($itf == $this->class && $func == $this->func)
            return $this->generate();
    }

    public function getUniqId(array $params)
    {
        $result = $this->call($this->class, $this->func, $params);
        return $result;
    }

    public function getUniqIds(array $params, $num)
    {
        $result = array();
        $num = intval($num);
        if ($num < 1)
            return $result;
        for ($i = 0; $i < $num; $i ++)
        {
            $result[] = $this->call($this->class, $this->func, $params);
        }
        return $result;
    }

    private function generate()
    {
        $timeInMillSeconds = microtime(true) * 1000;
        $id = $timeInMillSeconds << (63-42) | $this->getMachineId() << (63-42-10) | $this->getCountIndex();
        return $id;
    }

    private function getMachineId()
    {
        return (microtime(true) * $this->seed()) % self::MACHINE_MAX;
    }

    private function getCountIndex()
    {
        if ($this->crtIndex < 0 )
            $this->crtIndex = srand($this->seed());
        else
            $this->crtIndex ++;
        return $this->crtIndex;
    }

    private function seed()
    {
        $time = explode(' ', microtime());
        return (float) $time[1];
    }

}