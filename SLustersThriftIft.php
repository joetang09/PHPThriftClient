<?php 
/**
 *
 * @date   2016-03-17 11:26
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "SThriftLoader.php";

use \thriftlib\SThriftException;

class SLustersThriftIft
{

    private $params = array();



    private $serviceInstances = null;

    private $serviceInited = array();

    private $yii = '\Yii';


    public function init(){
        if (!isset($this->params['service']))
        {
            throw new SThriftException('sevice not set');
        }

        if (!isset($this->params['lusters']) || !is_array($this->params['lusters']) || empty($this->params['lusters']))
        {
            throw new SThriftException('lusters not set or empty');
        }

        $class = '';
        if (class_exists("\\thriftlib\\" . $this->params['service']))
        {
            $class = "\\thriftlib\\" . $this->params['service'];
        }
        else if (class_exists("\\thriftlib\\services\\" . $this->params['service']))
        {
            $class = "\\thriftlib\\services\\" . $this->params['service'];
        }
        else
        {
            throw new SThriftException('sevice not found');
        }
        if (!isset($this->params['baseLogPath']) && class_exists($this->yii))
        {
            $yiiClass = $this->yii;
            $this->params['baseLogPath'] = $yiiClass::app()->runtimePath;
        }


        foreach ($this->params['lusters'] as $node => $p)
        {
            $tmpObj = new $class;

            foreach ($this->params as $k => $val)
            {
                if (isset($tmpObj->$k))
                {
                    $tmpObj->$k = $val;
                }
            }
            foreach ($p as $k => $val)
            {
                if (isset($tmpObj->$k))
                {
                    $tmpObj->$k = $val;
                }
            }

            
            $this->serviceInstances[$node] = $tmpObj;
        }
        
    }

    public function issetNode($node)
    {
        return isset($this->serviceInstances[$node]);
    }


    public function __set($k, $v)
    {
        $this->params[$k] = $v;
    }

    public function __get($k)
    {
        $result = array();
        foreach ($this->serviceInstances as $name => $instance)
        {
            if (isset($instance->$k))
            {
                $result[$name] = $instance->$k;
            }
        }
        return $result;
    }


    public function __call($method, $args)
    {
        $node = '';
        if (count($args) < 1)
        {
            throw new SThriftException('node lost');
        }
        $node = $args[0];
        unset($args[0]);
        ksort($args);
        if (isset($this->serviceInstances[$node]))
        {
            if (!isset($this->serviceInited[$node]))
            {
                if (method_exists($this->serviceInstances[$node], 'init'))
                {
                    $this->serviceInstances[$node]->init();
                }
                $this->serviceInited[$node] = 1;
            }
            

            if (method_exists($this->serviceInstances[$node], $method))
            {
                return call_user_func_array(array($this->serviceInstances[$node], $method), $args);
            }
        }

    }

}