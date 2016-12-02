<?php
/**
 * Created by PhpStorm.
 * User: sergey
 * Date: 9/10/15
 * Time: 10:57
 */


namespace thriftlib;

require_once __DIR__ . DIRECTORY_SEPARATOR. 'core' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Thrift' . DIRECTORY_SEPARATOR . 'ClassLoader' . DIRECTORY_SEPARATOR . 'ThriftClassLoader.php';

use Thrift\ClassLoader\ThriftClassLoader;
$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'lib');
$loader->register();

class SThriftLoader
{
    public static function autoload($class)
    {
        $classAry = explode("\\", $class);
        if (count($classAry) < 2 || $classAry[0] != 'thriftlib')
        {
            return;
        }
        array_shift($classAry);
        $class = array_pop($classAry);
        $path = __DIR__ . DIRECTORY_SEPARATOR;
        foreach ($classAry as $a)
        {
            $path .= $a . DIRECTORY_SEPARATOR;
        }
        if (file_exists($path . $class . '.php'))
        {
            require_once $path . $class . '.php';
        }
    }

}

spl_autoload_register(array('\thriftlib\SThriftLoader', 'autoload'));