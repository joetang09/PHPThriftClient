<?php 
/**
 *
 * @date   2016-03-17 15:33
 *
 * @author sergey<joetang91@gmail.com>
 *
 */


/**
 * SLustersThriftIftRouter.
 */
class SLustersThriftIftRouter 
{

    public $mapping = array();

    public $lusterObj = null;

    private $yii = '\Yii';

    private $crtFetchNode;


    public function init()
    {

        if (is_string($this->lusterObj))
        {
            if (class_exists($this->yii))
            {
                $yiiClass = $this->yii;
                $lusterObj = $this->lusterObj;
                $this->lusterObj = $yiiClass::app()->$lusterObj;
            }
            
        }
        if ($this->lusterObj == null)
        {
            throw new Exception("lusterObj cannot be instantiated", 1);
        }
    }

    public function fetchNode()
    {
        $args = func_get_args();
        $this->crtFetchNode = 'default';
        for ($i = count($args); $i > 0; $i --)
        {
            $tmp = array_slice($args, 0, $i);
            $guessNode = implode('_', $tmp);
            if (isset($this->mapping[$guessNode]))
            {
                $this->crtFetchNode = $this->mapping[$guessNode];
                break;  
            }
        }
        if (!$this->lusterObj->issetNode($this->crtFetchNode))
        {
            throw new Exception("Node Not Found");
        }
        return $this;
    }

    public function __call($method, $args)
    {
        array_unshift($args, $this->crtFetchNode);
        return call_user_func_array(array($this->lusterObj, $method), $args);
    }



}