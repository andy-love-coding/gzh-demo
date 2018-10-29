<?php
// 主动请求

// 引入主动请求的类
include "../lib/wechat.php";

$zd = new WeChat();

// // 创建（或更新）自定义菜单
// $arr = include '../config/config.php'; 
// echo $zd->createMenu($arr['menu']); 

// 删除自定义菜单
// echo $zd->deleteMenu();

// 上传素材，移步到：upload.php

// 获取场景二维码（0临时二维码，1永久二维码）
// echo '<img src="' . $zd->qrcode(1,'andy') . '"/>';

// 根据openid获取用户信息
// print_r($zd->getUserInfoByOpenid('oakWY1FB8KevnCZqRiasgeInWOIE'));

// 发送客服消息
// echo $zd->sendKefuMsg('oakWY1FB8KevnCZqRiasgeInWOIE', "<a href='http://baidu.com'>跳转百度啦啦啦啦</a>");
// echo $zd->sendKefuMsg('oakWY1Lj49yi2PNL7Y7TNA8OuuTU', "小白，你在干嘛");

// 群发消息
// echo $zd->sendAll('我就是群发消息');

// 查询群发消息的状态
// echo $zd->sendAllStatus('1000000002');

// 删除群发，还没做呢

// 获取jsApiTicket

echo $zd->getjsApiTicket();// ticket有效期2小时，接口请求次数有限，要做缓存
echo "<hr />";
echo $zd->getCurrentUrl();

