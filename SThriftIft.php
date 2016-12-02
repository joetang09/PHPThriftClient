<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 9/10/15
 * Time: 14:54
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . "SThriftLoader.php";

use \thriftlib\SThriftException;

class SThriftIft
{

    private $params = array();

    private $serviceInstance = null;

    private $yii = '\Yii';


    public function init(){
        if (!isset($this->params['service']))
        {
            throw new SThriftException('sevice not set');
        }
        $class = '';
        if (class_exists("\\thriftlib\\" . $this->params['service']))
        {
            $class = "\\thriftlib\\" . $this->params['service'];
        } else if (class_exists("\\thriftlib\\services\\" . $this->params['service']))
        {
            $class = "\\thriftlib\\services\\" . $this->params['service'];
        } else {
            throw new SThriftException('sevice not found');
        }
        if (!isset($this->params['baseLogPath']) && class_exists($this->yii))
        {
            $yiiClass = $this->yii;
            $this->params['baseLogPath'] = $yiiClass::app()->runtimePath;
        }

        $this->serviceInstance = new $class();

        foreach ($this->params as $k => $v)
        {
            if (isset($this->serviceInstance->$k))
            {
                $this->serviceInstance->$k = $v;
            }
        }


        if (method_exists($this->serviceInstance, 'init'))
        {
            $this->serviceInstance->init();
        }
    }


    public function __set($k, $v)
    {
        $this->params[$k] = $v;
    }

    public function __get($k)
    {
        if ($this->serviceInstance && isset($this->serviceInstance->$k))
        {
            return $this->serviceInstance->$k;
        }
    }


    public function __call($method, $args)
    {
        if ($this->serviceInstance && method_exists($this->serviceInstance, $method))
        {
            return call_user_func_array(array($this->serviceInstance, $method), $args);
        }
    }

}