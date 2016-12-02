# Thrift 客户端


## Lib 依赖

1. thrift 0.9.2
2. php >= 5.5

## 使用方法

1. thrift   生成文件位置 ./core/genfile , 多服务型放在multi，单服务single
2. service  可选 SServerCommon,SMultiplexedServerCommon,另外高级可选SThriftCli,SMultiplexedThriftCli
3. classNs  即生成的命名空间, 如果使用SMultiplexedServerCommon 或者 SMultiplexedThriftCli 使用 服务名 => 命名空间

demo :

```
<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . "SThriftIft.php";

$a = new SThriftIft();
$a->service = 'SServerCommon';
$a->host = '127.0.0.1';
$a->port = '7001';
$a->classNs = 'test';
$a->sendTimeOut = 1000;
$a->recvTimeOut = 1000;
$a->persist = false;
$a->doLog = true;
$a->init();
try {
    $r = $a->call(
        '\test\testClient',
        'method',
        array(
            'params'
        )
    );
//    $a->__destruct();
    var_dump($r);
} catch (\thriftlib\SThriftException $e)
{
__//__var_dump($e);
}
```

Yii 易用配置

```
'testThriftLib' => array(
    'class' => 'system.zyb.thriftlib.SThriftIft',
    'service' => 'SServerCommon',
    'host' => '127.0.0.1',
    'port' => '7001',
    'classNs' => 'test',
    'sendTimeOut' => 1000,
    'recvTimeOut' => 1000,
    'persist' => true,
),

```

## Monitor 配置

支持多种形式的日志输出

```
'testThriftLib' => array(
    'class' => 'system.zyb.thriftlib.SThriftIft',
    'service' => 'SServerCommon',
    'host' => '127.0.0.1',
    'port' => '7001',
    'classNs' => 'test',
    'sendTimeOut' => 1000,
    'recvTimeOut' => 1000,
    'persist' => true,
    'monitor' => array(
                array(
                    'class' => "\\thriftlib\\monitors\\SMonitorPipe",
                    'logFile' => '/Users/sergey/Archive/Logs/php/test/a'
                ),
                array(
                    'class' => "\\thriftlib\\monitors\\SMonitorUdp",
                    'host' => '127.0.0.1',    // 使用 IP
                    'port' => 9999            // 日志端口
                ),
                array(
                    'class' => "\\thriftlib\\monitors\\SMonitorFile",
                    'fileName' => '/Users/sergey/Archive/Logs/php/test/test',
                ),
            ),
)

```

* 支持多个配置
* 如果没有配置，将使用之前默认的pipe 方式记录，
* 有新的配置就会忽略之前的pipe 方式

# thrift 集群客户端， SLustersThriftIft

## yii 中配置

```
    'lusters' => array(
            'class' => 'system.xxx.thriftlib.SLustersThriftIft',
            'service' => 'SServerCommon',
            'lusters' => array(
                'default_read' => array(),
                'default_write' => array(),
                'node1' => array(),
                'node2' => array(),
                'node3' => array(
                    ''
                ),
            ),
            'host' => '',
            'port' => '',

            'serverName' => 'anzoservant',
            'classNs' => 'anzoservant',
            'sendTimeOut' => 1000,
            'recvTimeOut' => 1000,
            'persist' => false,
        ),
        
```

## TIPS

1. class 必须为 system.xxx.thriftlib.SLustersThriftIft
2. service 在集群中只支持单种服务类型，即支持SServerCommon 的话那么就不支持 SMultiplexedServiceBase
3. 除此之外的参数，如果设置在lusters 层级的话，那么如果lusters 内部存在相同参数，将会被内部参数覆盖




# 集群路由 SLustersThriftIftRouter 

## 依赖
    
    > SLustersThriftIft
    
## 配置

```
'lustersrouter' => array(
    'lusterObj' => 'lusters',
    'mapping' => array(
        'node_write' => 'node1',
        'node_read' => 'xxxx',
        
    )
)

```

1. lusterObj 为配置的 SLustersThriftIft 名字

## 使用

```
\Yii::app()->lustersrouter->fetchNode([$p1 [,$p2 ...]])->doLike SLustersThriftIft();

```


1. $p1, $p2 ... 参数将会将会按照数量逐渐减小的方式组装， 比如 $p1_$p2 ， $p1 
2. 通过组装出来的信息到设定的mapping 中查找对应的 node
3. 如果设定的node 存在将会返回


```php

'broodLuster' => array(
            'class' => 'system.xxx.thriftlib.SLustersThriftIft',
            'service' => 'SServerCommon',
            'lusters' => array(
                'default' => array(
                    'host' => '10.174.201.24',
                    'port' => '8001',
                    'sendTimeOut' => 1000,
                    'recvTimeOut' => 1000,
                    'persist' => true,
                ),
                'commonRead' => array(
                    'host' => '100.98.43.28',
                    'port' => '8001',
                    'sendTimeOut' => 1000,
                    'recvTimeOut' => 1000,
                    'persist' => true,
                ),
                'commonWrite' => array(
                    'host' => '10.174.201.24',
                    'port' => '8001',
                    'sendTimeOut' => 1000,
                    'recvTimeOut' => 1000,
                    'persist' => true,
                ),
            ),
            'serverName' => 'broodService',
            'classNs' => 'brood',
            'sendTimeOut' => 1000,
            'recvTimeOut' => 1000,
            'persist' => true,
        ),

        'broodLusterRouter' => array(
            'class' => 'system.xxx.thriftlib.SLustersThriftIftRouter',
            'lusterObj' => 'broodLuster',
            'mapping' => array(
                'read' => 'commonRead',
                'write' => 'commonWrite',
            ),
        ),
        
```
