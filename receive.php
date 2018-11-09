<?php
/**
 * Created by PhpStorm.
 * User: leegwin
 * Date: 2018/9/11
 * Time: 下午3:20
 */
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

// 创建连接
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest','/');
// 创建channel，多个channel可以共用连接
$channel = $connection->channel();

// 可能会在数据发布之前启动消费者，所以我们要确保队列存在，然后再尝试从中消费消息。

// 创建直连的交换机
$channel->exchange_declare('direct_queen', 'direct', false, true, false);
// 创建队列
$channel->queue_declare('queen', false, true, false, false);
// 交换机跟队列的绑定，
$channel->queue_bind('queen', 'direct_queen', 'routigKey-queen');

// 回调函数
$callback = function ($msg) {
    echo " [x] Received ",$msg->delivery_info['routing_key'],":", $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done", "\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    //不会有消息处理超时的情况，只有当消费者进程异常退出时，RabbitMQ才会将其消息重发。即使处理消息需要一段非常长的时间。
};
/**
 * prefetch_count = 1设置。这告诉RabbitMQ不同时给多个消息到同一个消费者
 * 也就是说 在消息未处理完成前不分配新的任务给消费者
 * global=true时表示在当前channel上所有的consumer都生效，否则只对设置了之后新建的consumer生效
 * prefetch_count在no_ask=false的情况下生效，即在自动应答的情况下这两个值是不生效的
 */

$channel->basic_qos(null, 1, null);
/*
    消费消息
    queue: 制定队列
    consumer_tag: Consumer identifier
    no_local: Don't receive messages published by this consumer.
    no_ack: 服务器是否消息确认,默认为true是关闭的
    exclusive: 独占该消息，只有该channel才能消费这条消息
    nowait:
    callback: 回调函数
*/
// 启动队列消费者
$channel->basic_consume('queen', '', false, false, false, false, $callback);
// 判断是否存在回调函数
// 循环监听回调（竞争消费者模式）
while(count($channel->callbacks)) {
    // 此处为执行回调函数
    $channel->wait();
}

$channel->close();
$connection->close();