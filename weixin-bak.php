<?php
// 在微信公众号后台设置的token，不会在网络传输，只有微信和开发者自己知道
$token = "weixin";

// 获取签名参数
$arr['token'] = $token;
$arr["timestamp"] = $_GET["timestamp"];
$arr["nonce"] = $_GET["nonce"];

// 对签名参数排序
sort($arr, SORT_STRING);

// 将签名参数拼接成字符串，并sha1加密
$tmpStr = implode($arr);
$tmpStr = sha1($tmpStr); // 最后加密计算得出的签名

// 若本地计算的签名与微信传来的签名一致，则证明消息来源微信服务器，返回echostr，接入微信服务器生效
if ($tmpStr = $_GET['signature']) {
  echo $_GET['echostr'];
}