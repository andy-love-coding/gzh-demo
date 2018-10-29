<?php
// 公众号自定义菜单配置
$arr = include '../config/app.conf.php'; // 这是站在lib/webchat角度来引用的路径，因为config在webchat中引用了
$domain = $arr['domain'];

return [
  'menu' => [
    "button" => [
      [
        "type" => "click",
        "name" => "今日歌曲",
        "key" => "V1001_TODAY_MUSIC"
      ],
      [
        "name" => "菜单菜单",
        "sub_button" => [
          [
            "type" => "view",
            "name" => "授权页面",
            "url" => $domain."/web_auth/go.php"
          ],
          [
            "type" => "view",
            "name" => "搜索",
            "url" => "http://www.soso.com/"
          ],
          [
            "type" => "scancode_waitmsg",
            "name" => "扫码带提示",
            "key" => "rselfmenu_0_0",
          ],
          [
            "type" => "scancode_push",
            "name" => "扫码推事件",
            "key" => "rselfmenu_0_1",
          ]
        ]
      ],
      [
        "name" => "发图",
        "sub_button" => [
          [
            "type" => "pic_sysphoto",
            "name" => "系统拍照发图",
            "key" => "rselfmenu_1_0",
          ],
          [
            "type" => "pic_photo_or_album",
            "name" => "拍照或者相册发图",
            "key" => "rselfmenu_1_1",
          ],
          [
            "type" => "pic_weixin",
            "name" => "微信相册发图",
            "key" => "rselfmenu_1_2",
          ]
        ]
      ]
    ]
  ]
];