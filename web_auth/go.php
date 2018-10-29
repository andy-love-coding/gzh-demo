<?php
// 生成授权页面url地址

$arr = include '../config/app.conf.php';
$appid = $arr['appId'];
$domain = $arr['domain'];


$appid = 'wx4f9f7500f8bd7ac2';
$url = $domain.'/web_auth/web_auth.php';
$redirect_url = urlencode($url); // 记得urlencode处理
// $scope: snsapi_userinfo需用户同意授权，snsapi_base静默授权（需注意公众号账户下scope的作用域）
// 服务号获得高级接口后，默认拥有scope参数中的snsapi_base和snsapi_userinfo
$scope = 'snsapi_userinfo';
$state = 'weixin_auth'; // 重定向url的参数

$surl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s#wechat_redirect';
$url = sprintf($surl, $appid, $redirect_url, $scope, $state);

// 跳转到授权页面
header('location:'.$url);