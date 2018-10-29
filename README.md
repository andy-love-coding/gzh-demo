# 微信公众号开发demo

##demo结构
```
config: 配置文件目录
curl-demo: curl的演示demo目录
db：此项目的数据库文件
lib：存放有向公众号【主动请求】的类文件（wechat.php)，及缓存token等文件
logs：存放apache日志文件
natapp：包含内容穿透（映射）客户端软件，及配置文件（authToken），用这个调试公众号开发很方便
php：是微信JS-SDK生成签名signature的官方demo代码，可参考
web_auth：网页授权目录
  ---- go.php是授权页
  ---- web_auth.php是着陆页，磁业包含JS-SDK演示demo
weixin_bak.php: 是简单手写接入公众号的代码，只包含接入的代码，没有响应的代码
weixin.php: 是功能比较丰富的【接入与被动响应】demo代码，含了图灵机器人响应、高德开发平台LBS周边搜索
wx.xml: 是接入后公众号后，用户向微信发消息或指令，微信服务器post消息给开发者服务器，开发者将接收的消息写进日志wx.xml
```