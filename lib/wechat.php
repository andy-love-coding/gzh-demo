<?php
// 主动调用公众号接口类

class WeChat
{ 
  private $appId;
  private $appSecret;

  // 类的构造函数
  public function __construct() {
    $arr = include '../config/app.conf.php';
    $this->appId = $arr['appId'];
    $this->appSecret = $arr['appSecret'];
  }

  // 获取accessToken
  private function getAccessToken()
  {
    
    // 设置一个缓存文件存储access_token，实际工作中不会用文件，会存储在memcache或redis中
    $cacheFile = __DIR__ . '/' . '_accessToken.cache';
    if (is_file($cacheFile) && filemtime($cacheFile) + 7000 > time()) { // 条件：是文件&&没过期，则执行...
      // $accessToken = include $cacheFile; // 因为扩展名不为php所以不用使用include
      $accessToken = file_get_contents($cacheFile); // 写缓存
      // echo "取得是缓存哦！，调试用";
      return $accessToken;
    }
    // 否则，缓存过期了    
    $surl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
    $url = sprintf($surl, $this->appId, $this->appSecret);
    $json = $this->http_request($url);
    $arr = json_decode($json, true);
    if (empty($arr['errcode'])) { // 没有错误
      file_put_contents($cacheFile, $arr['access_token']); // 缓存access_token
      return $arr['access_token'];
    }
    return false;
  }

  // 创建自定义菜单
  public function createMenu(array $menuArr = [])
  {
    $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessToken();
    // 微信要json参数，需要把数组转json
    $json = json_encode($menuArr, JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE=256，设置中文不转为unicode
    $ret = $this->http_request($url, $json);
    return $ret;
  }

  // 删除自定义菜单
  public function deleteMenu()
  {
    $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $this->getAccessToken();
    return $this->http_request($url);
  }

  // 上传素材 ($flag：0临时素材，1永久素材)
  public function upFile(string $type = 'image', string $file = '', $flag = 0)
  {
    $params = [];
    if ($flag == 0) { // 临时素材
      $surl = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s';
    } else { // 永久素材
      $surl = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=%s&type=%s';
      if ($type === 'video') { // 上传视频时，除了文件，还需要以下post参数       
        $params['description'] = '{
          "title":"我是视频标题",
          "introduction":"我是视频介绍，我是视频介绍，我是视频介绍"
        }';
      }
    }
    $url = sprintf($surl, $this->getAccessToken(), $type);
    $json = $this->http_request($url, $params, $file); // 文件上传请求
    $arr = json_decode($json, true);
    return $arr;
  }

  // 获取场景二维码（0临时二维码，1永久二维码）, $scene是场景值
  public function qrcode(int $flag = 0, $scene)
  {
    // 1.0 获取凭证ticket
    $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->getAccessToken();
    if ($flag === 0) { // 临时二维码
      $filename = $scene; // 用二维码的场景值来做二维码图片名称
      $json = '{"expire_seconds": 2592000, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": ' . $filename . '}}}';
    } else { // 永久二维码
      $filename = $scene;
      $json = '{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "' . $filename . '"}}}';
    }
    $ret = $this->http_request($url, $json);
    $arr = json_decode($ret, true);
    $ticket = $arr['ticket'];

    // 2.0 通过ticket换取二维码
    $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($ticket);
    $data = $this->http_request($url);
    file_put_contents($filename . '.jpg', $data);
    return $filename . '.jpg';
  }

  // 根据openid获取用户信息
  public function getUserInfoByOpenid(string $openid)
  {
    $surl = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN';
    $url = sprintf($surl, $this->getAccessToken(), $openid);
    $json = $this->http_request($url);
    $arr = json_decode($json, true);
    return $arr;
  }

  // 发送客服消息
  public function sendKefuMsg(String $openid, string $message)
  {
    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->getAccessToken();
    $data = '{
        "touser":"' . $openid . '",
        "msgtype":"text",
        "text":
        {
            "content":"' . $message . '"
        }
    }';
    $json = $this->http_request($url, $data);
    return $json;
  }

  // 群发消息
  public function sendAll(string $content)
  {
    $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' . $this->getAccessToken();
    $json = '{
      "filter":{
         "is_to_all":true
      },
      "text":{
         "content":"' . $content . '"
      },
      "msgtype":"text"
    }';
    return $this->http_request($url, $json);

  }

  // 查询群发消息的状态
  public function sendAllStatus($msg_id)
  {
    $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/get?access_token=' . $this->getAccessToken();
    $json = '{
      "msg_id": ' . $msg_id . '
    }';
    return $this->http_request($url, $json);
  }

  // 获取jsApiTicket (接口请求次数有限，要做缓存)
  public function getjsapiTicket()
  {
    $cacheFile = __DIR__ . '/' . '_jsapiTicket.cache';
    if (is_file($cacheFile) && filemtime($cacheFile) + 7000 > time()) { // 条件：是文件&&没过期，则执行...
      // $accessToken = include $cacheFile; // 因为扩展名不为php所以不用使用include
      $jsapiTicket = file_get_contents($cacheFile); // 写缓存
      // echo "取得是缓存哦！，ticket调试用<hr />";
      return $jsapiTicket;
    }
    // 否则，缓存过期了    
    $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $this->getAccessToken() . '&type=jsapi';
    $arr = json_decode($this->http_request($url),true);
    if ($arr['errcode'] == 0) { // 没有错误,则写缓存
      file_put_contents($cacheFile, $arr['ticket']); // 写缓存jsapiTicket
      return $arr['ticket'];
    }
    return false;
  }

  // 获取当前url地址
  public function getCurrentUrl()
  {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; // 这样的url会包括"?参数"，不会包括"#号及其后内容"
    return $url;
  }

  // 生成随机字符串
  public function createNonceStr($length = 16)
  {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  // 计算出js-SDK签名
  public function getSignPackage() {
    $jsapiTicket = $this->getjsapiTicket();
    $nonceStr = $this->createNonceStr();
    $timestamp = time();
    $url = $this->getCurrentUrl();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }
  
  // curl请求方法
  private function http_request(string $url, $params = [], string $filepath = '') // $params: array|json
  {    
    // 有文件上传时，用CURLFile类来指向文件，并赋值给post参数数组$params。
    if (!empty($filepath)) {
      $params['media'] = new CURLFile($filepath);
    }
    // 1.0 初始化
    $ch = curl_init($url); 
    // 2.0 设置选项
    curl_setopt($ch, CURLOPT_URL, $url); // 设置请求的url地址
    curl_setopt($ch, CURLOPT_HEADER, 0); // 不在终端中显示header头信息，其实默认就不显示
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 不直接将请求结果显示在终端中，而是赋值给指定变量【重要】
    curl_setopt($ch, CURLOPT_USERAGENT, 'msie'); // 设置请求的浏览器型号（具体设置再聊），对于爬虫伪造浏览器请求，从而避免被屏蔽有用
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时时间，单位秒
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 不进行https证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 不进行https证书检查

    if ($params) {
      curl_setopt($ch, CURLOPT_POST, 1); // 设置请求方法为post
      curl_setopt($ch, CURLOPT_POSTFIELDS, $params); // 设置post请求的参数
    }  
    // 3.0 执行
    $data = curl_exec($ch); // 将成功请求的结果，赋值给指定的变量
    $info = curl_getinfo($ch); // 执行后，得到请求结果相关信息
    if ($info['http_code'] !== 200) {
      echo curl_error($ch); // 输出错误,若为https请求,且没有相关设置的话，则报错：SSL certificate problem: unable to get local issuer certificate
      echo '<br/>' . '请求失败，状态码：' . $info['http_code'];
    }
    // 4.0 关闭
    curl_close($ch);
    // 返回数据
    return $data;
  }
}

