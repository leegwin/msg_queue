<?php
/**
 * Created by PhpStorm.
 * User: leegwin
 * Date: 2018/9/29
 * Time: 下午5:00
 */

$data = [
  "app_id" => 'hb_order_pay',
    "out_trade_no" => '',
];
ksort($data);
//组装
$paramStr = http_build_query($data, null, ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
//加密
$sign = md5(md5($paramStr) . $key);

return $sign;