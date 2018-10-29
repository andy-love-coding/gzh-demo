<?php
// 能到这个页面，说明已经授权了，有了code
// 此demo由于没有存储网页网页授权access_token，其实每次都是用新的code（在授权页得到的），获取新了的access_token

// 引入主动请求类
include "../lib/wechat.php";
$zd = new WeChat();
// 取得js-SDK签名数据报表
$signPackage = $zd->GetSignPackage();

class WebAuth
{
  private $appId;
  private $appSecret;
  public $code;
  public $json_access; // json格式的access_token数据，包含：access_token、expires_in、refresh_token、openid、scope
  
  // 类的构造函数
  public function __construct() {
    $arr = include '../config/app.conf.php';
    $this->appId = $arr['appId'];
    $this->appSecret = $arr['appSecret'];

    // 首先判断access_token是否存在，再判断其是否过期
    if ($this->json_access) { // 存在，再看是否有效

    } else { // 不存在，则获取,然后写缓存，
      $this->getToken();
      $this->code = $_GET['code'];
    }
  }

  // 通过code换取access_token
  private function getToken()
  {
    $appid = $this->appId;
    $appsecret = $this->appSecret;
    $code = $_GET['code']; // 用户同意授权后，跳转到：redirect_uri/?code=CODE&state=STATE，带有code和state参数

    // code换取网页授权access_token的请求地址
    $surl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
    $url = sprintf($surl, $appid, $appsecret, $code);
    $json_access = file_get_contents($url); // 发送get请求,获取access_token
    $this->json_access = $json_access;
  }

  // 通过refresh_token刷新access_token
  public function refreshToken($appid, $refresh_token)
  {
    $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' . $appid . '&grant_type=refresh_token&refresh_token=' . $refresh_token;
    $this->$json_access = file_get_contents($url);
  }

  // 检验access_token是否有效
  public function checkToken($access_token, $openid)
  {
    $url = 'https://api.weixin.qq.com/sns/auth?access_token=' . $access_token . '&openid=' . $openid;
    return file_get_contents($url); // 返回 { "errcode":0,"errmsg":"ok"} 或 { "errcode":40003,"errmsg":"invalid openid"}
  }

  // 获取用户信息
  public function getUserInfo()
  {
    $json_access = $this->json_access;
    $arr = json_decode($json_access, true);
    $access_token = $arr['access_token'];
    $appid = $this->appId;
    // 获取用户信息
    $surl = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
    $url = sprintf($surl, $access_token, $appid);
    $userinfo = file_get_contents($url);
    return $userinfo; // 返回json格式用户信息
  }
}

$auth = new WebAuth();

// access_token信息
$json_access = $auth->json_access;
$arr_access = json_decode($json_access, true);
$access_token = $arr_access['access_token'];
$refresh_token = $arr_access['refresh_token'];
$openid = $arr_access['openid'];

// access_token是否有效
$ret = $auth->checkToken($access_token, $openid);
$arr = json_decode($ret, true);
$errmsg = $arr['errmsg'];

$code = $auth->code;

// 用户信息
$json = $auth->getUserInfo();
$userinfo = json_decode($json, true);

?>
 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>网页授权</title>
<!-- 引入微信js-SDK -->
<script src="http://res.wx.qq.com/open/js/jweixin-1.4.0.js"></script>
</head>
<body>
  <h3>用户信息</h3>
  <p>
    <button onclick="chooseImage()">拍照或从手机相册中选图接口</button>
  </p>
  <p>
    <img src="#" id="img">
  </p>
  <p><?php echo 'access_token: <br/>' . $access_token ?></p>
  <p><?php echo 'refresh_token: <br/>' . $refresh_token ?></p>
  <p><?php echo 'access_token是否有效: ' . $errmsg ?></p>
  <p><?php echo 'code: ' . $code ?></p>
  <p><?php echo $openid ?></p>
  <p><?php echo $userinfo['openid'] ?></p>
  <p><?php echo $userinfo['nickname'] ?></p>
  <p><?php echo $userinfo['sex'] ? '男生' : '女生' ?></p>
  <p><img src="<?php echo $userinfo['headimgurl'] ?>"></p>
</body>
<script>
wx.config({
  debug: true,// 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
  appId: '<?php echo $signPackage["appId"]; ?>',
  timestamp: <?php echo $signPackage["timestamp"]; ?>,
  nonceStr: '<?php echo $signPackage["nonceStr"]; ?>',
  signature: '<?php echo $signPackage["signature"]; ?>',
  jsApiList: [
    // 所有要调用的 API 都要加到这个列表中
    'onMenuShareAppMessage', // 分享给朋友
    'onMenuShareTimeline', // 分享到朋友圈
    'chooseImage' // 拍照或从手机相册中选图
  ]
});

// 若分享不显示图片时，看看是不是分享链接与授权域名不一致
wx.ready(function(){
  // 分享给朋友 （即将废弃）
  wx.onMenuShareAppMessage({
    title: '我帅不帅', // 分享标题
    desc: '你臭不要脸呀', // 分享描述
    link: 'http://m4e8k7.natappfree.cc/web_auth/go.php', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
    imgUrl: 'https://www.baidu.com/img/bd_logo1.png', // 分享图标
    type: 'link', // 分享类型,music、video或link，不填默认为link
    dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
    success: function () {
      // 用户点击了分享后执行的回调函数
      alert("分享给朋友成功啦！");
    }
  });

  // 分享到朋友圈 （即将废弃）
  wx.onMenuShareTimeline({
    title: '我有朋友圈了', // 分享标题
    link: 'http://m4e8k7.natappfree.cc/web_auth/go.php', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
    imgUrl: 'https://www.baidu.com/img/bd_logo1.png', // 分享图标
    success: function () {
      // 用户点击了分享后执行的回调函数
      alert("分享到朋友圈成功了");
    }
  });  
});

// 拍照或从手机相册中选图接口
function chooseImage() {  
  wx.chooseImage({
    count: 1, // 默认9，表示最多可以选中几张图片
    sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有
    sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
    success: function (res) {
      var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
      document.querySelector('#img').src = localIds;
    }
  });
}
</script>  
</html> 