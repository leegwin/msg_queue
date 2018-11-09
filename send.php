<?php
/**
 * Created by PhpStorm.
 * User: leegwin
 * Date: 2018/9/11
 * Time: 下午12:00
 */
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 创建连接
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest','/');
// 创建channel，多个channel可以共用连接
$channel = $connection->channel();

// 创建交换机以及队列（如果已经存在，不需要重新再次创建并且绑定）
// 创建直连的交换机
$channel->exchange_declare('direct_queen', 'direct', false, true, false);
/*
    name: 队列名称
    passive: false //消极创建，有同名队列直接返回，无同名队列也不创建，直接报错
    durable: true //服务器重启后队列依旧存活
    exclusive: false //队列能被其他channel访问,设置了排外为true的队列只可以在本次的连接中被访问，
          //也就是说在当前连接创建多少个channel访问都没有关系，但是如果是一个新的连接来访问，对不起，不可以，还有一个需要说一下的是，排外的queue在当前连接被断开的时候会自动消失（清除）无论是否设置了持久化
    auto_delete: false //channel关闭之后队列不删除
*/
// 创建队列
$channel->queue_declare('queen', false, true, false, false);
// 交换机跟队列的绑定，
$channel->queue_bind('queen', 'direct_queen', 'routigKey-queen');

$data = implode(' ', array_slice($argv, 1));
if(empty($data)) $data = "Hello World! My Queen \n";
// 设置消息body传送字符串logs(消息只能为字符串，建议消息均json格式)
$msg = new AMQPMessage($data ,array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
// 发送数据到对应的交换机direct_logs并设置对应的routigKey
$channel->basic_publish($msg, 'direct_queen', 'routigKey-queen');
echo " [x] ",$data," \n";

$channel->close();
$connection->close();
//这里队列和交换机的持久化值的是，服务器重启后，队列和交换机信息不会丢失，而如果想要重启后消息也不丢失，那么需要设置消息级别的持久，即deliveryMode = 2