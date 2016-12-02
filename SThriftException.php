<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 6/1/15
 * Time: 15:19
 */

namespace thriftlib;

class SThriftException extends \Exception
{
    public $errorInfo;

    public function __construct($message,$code=0,$errorInfo=null)
    {
        $this->errorInfo=$errorInfo;
        parent::__construct($message,$code);
    }

}