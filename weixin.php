<?php

/**
 * 被动接收公众号信息接口类(接入与响应)
 */

//define your token
// define("TOKEN", "weixin");
$wechatObj = new Wechat();

class Wechat
{
	const TOKEN = 'weixin';

	// 数据库属性
	private $pdo;

	// 接入与响应并存
	public function __construct()
	{
		if (!isset($_GET["echostr"])) {
			$this->pdo = include './lib/db.php'; // 先引入pdo
			$this->responseMsg(); // 响应时：微信服务器发送post请求，没有echostr参数			
		} else {
			$this->valid(); // 接入时：微信服务器发送get请求，有echostr参数
		}
	}

	// 被动接收消息，并响应,响应要求：3次5秒（即：在3次5秒内响应微信服务器，如无法保证，也应该回复空串，微信服务器会不做处理）
	public function responseMsg()
	{
		// 获取原生post数据：php5.5之后官方不建议使用,若使用会报一个警告，php7.0之后则会直接报致命错误
		// $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		// 接收原生post数据
		$postStr = file_get_contents('php://input');
		// 记录接收日志
		$this->writeLog($postStr);

		if (!empty($postStr)) {
			// 把接收的xml数据，转化成对象
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			// 消息类型
			$msgType = $postObj->MsgType;
			$ret = '';
			switch ($msgType) {
				case 'text': // 文本
					echo $ret = $this->handleText($postObj); // 回复文本
					break;
				case 'image': // 图片
					echo $ret = $this->handleImage($postObj);  // 回复图片
					break;
				case 'voice':
					echo $ret = $this->handleVoice($postObj);
					break;
				case 'event': // 事件
					echo $ret = $this->handleEvent($postObj); // 处理事件，并回复文本
					break;
			}
			// 记录发送日志
			$this->writeLog($ret, 2);
		}
	}

	// 事件触发：处理事件（$obj是接收的原生post数据）
	private function handleEvent($obj)
	{
		$content = '';
		$event = $obj->Event; // 事件的类型
		switch ($event) {
			case 'CLICK':
				$key = $obj->EventKey;
				$content = '你点击了' . $key . '😍' . '👬😙';
				$content = $this->createText($obj, $content); // 返回数据变成微信需要的xml数据形式
				break;
			case 'scancode_waitmsg': // 带提示扫码
				$content = $this->createText($obj, $obj->ScanCodeInfo->ScanResult);
				break;
			case 'subscribe': // 关注事件
				$content = $this->createText($obj, $obj->FromUserName);
				break;
			case 'LOCATION': // 得到地理位置事件				
				// 先判断用户记录是否存在（存在则更新，不存在则添加）
				$openid = $obj->FromUserName;
				$longitude = $obj->Longitude;
				$latitude = $obj->Latitude;
				$sql = "select * from location where openid='$openid'";
				$ret = $this->pdo->query($sql)->fetch();
				if ($ret) { // 存在，更新
					$sql = "update location set longitude=?,latitude=? where openid='$openid'";
					$stmt = $this->pdo->prepare($sql);
					$stmt->execute([$longitude, $latitude]);
				} else { // 不存在，新增
					// $sql = "insert into location (openid,longitude,latitude) value (?,?,?)"; // vulue 和 values 都可以
					$sql = "insert into location (openid,longitude,latitude) values (?,?,?)";
					$stmt = $this->pdo->prepare($sql);
					$stmt->execute([$openid, $longitude, $latitude]);
				}
				break;

		}
		return $content;
	}
	
	// 语音触发：回复语音识别结果
	public function handleVoice($obj)
	{
		$content = $obj->Recognition;
		$message = $this->aiMsg($content);
		return $this->createText($obj, $message);
	}

	// 图灵机器人回复
	private function aiMsg(string $content)
	{
		// 图灵机器人 api url
		$arr = include './config/tuling.conf.php';
		$apikey = $arr['apikey'];
		$url = 'http://openapi.tuling123.com/openapi/api/v2';
		$json = '{
				"reqType":0,
				"perception": {
					"inputText": {
						"text": "' . $content . '"
					}
				},
				"userInfo": {
					"apiKey": "'.$apikey.'",
					"userId": "' . md5($obj->FromUserName) . '"
				}
			}';
		var_dump($json);
		$ret = $this->http_request($url, $json);
		var_dump($ret);
		$arr = json_decode($ret, true);
			// 得到机器人回复的文本
		$message = $arr['results'][0]['values']['text'];
		return $message;
	}

	// 文字触发：回复文本(图灵机器人）、图片、语音、视频、图文
	private function handleText($obj)
	{
		$content = $obj->Content; // $content 的数据类型为：object(SimpleXMLElement)，所以if条件中不能用“===”
		if ($content == "语音") {
			$sql = "select media_id from material where type='voice'";
			$rows = $this->pdo->query($sql)->fetchAll();
			$index = array_rand($rows); // 从数组中随机取一个值，返回索引
			return $this->createVoice($obj, $rows[$index]['media_id']);
		} elseif ($content == '图片') {
			$sql = "select media_id from material where type='image'";
			$rows = $this->pdo->query($sql)->fetchAll();
			$index = array_rand($rows); // 从数组中随机取一个值，返回索引
			return $this->createImage($obj, $rows[$index]['media_id']);
		} elseif ($content == '视频') {
			$sql = "select media_id from material where type='video'";
			$rows = $this->pdo->query($sql)->fetchAll();
			$index = array_rand($rows); // 从数组中随机取一个值，返回索引
			return $this->createVideo($obj, $rows[$index]['media_id']);
		} elseif ($content == '图文') { // 当用户发送文本、图片、视频、图文、地理位置这五种消息时，开发者只能回复1条图文消息；其余场景最多可回复8条图文消息
			$items = [
				[
					'title' => '这次中招的是《教父2》演员',
					'description' => '这次中招的是《教父2》演员，这次中招的是《教父2》演员',
					'picurl' => 'http://cms-bucket.nosdn.127.net/catchpic/d/da/da1c12e0b0566258a8f28b93ff66498a.png?imageView&thumbnail=550x0',
					'url' => 'https://news.163.com/18/1025/20/DV060EIU0001875O.html',
				],
				[
					'title' => '"大桥游"火热 乘客欢呼鼓掌',
					'description' => '顾客在面包里吃出指甲 泉州85度C被食药监局约谈,客在面包里吃出指甲 泉州85度C被食药监局约谈',
					'picurl' => 'https://yt-adp.nosdn.127.net/wangjing/600200_acwn_20181011.jpg',
					'url' => 'https://news.163.com/18/1025/20/DV06MLUB0001875P.html',
				],
				[
					'title' => '港珠澳大桥乘客欢呼鼓掌',
					'description' => '顾客在面包里吃出指甲 泉州85度C被食药监局约谈,客在面包里吃出指甲 泉州85度C被食药监局约谈',
					'picurl' => 'https://yt-adp.nosdn.127.net/wangjing/600200_acwn_20181011.jpg',
					'url' => 'https://news.163.com/18/1025/20/DV06MLUB0001875P.html',
				]
			];
			return $this->createNews($obj, $items);
		} elseif (strstr($content, '位置-')) { // 如果含有"位置-"（strstr()函数搜索一个字符串在另一个字符串中第一次出现，并返回字符串的剩余部分）
			// 高德开放平台 周边搜索 接口
			$surl = 'http://restapi.amap.com/v3/place/around?key=%s&location=%s,%s&keywords=%s&types=%s&radius=%s&offset=20&page=1&extensions=all';
			$gaodeArr = include './config/gaode.php';
			$key = $gaodeArr['key'];
			$openid = $obj->FromUserName;
			$sql = "select * from location where openid='$openid'";
			$ret = $this->pdo->query($sql)->fetch();
			$longitude = $ret['longitude'];
			$latitude = $ret['latitude'];
			$keywords = str_replace('位置-', '', $content);
			$type = ''; // 搜索的类目id，为空表示不限类目
			$radius = 2000; // 搜索半径（米）
			$url = sprintf($surl, $key, $longitude, $latitude, $keywords, $type, $radius);
			$json = $this->http_request($url);
			$arr = json_decode($json, true);
			if (count($arr['pois']) > 0) {
				// 获取第一个信息
				$tmp = $arr['pois'][0];
				$message = "***************\n";// 双引号能解析换行，单引号不能
				$message .= '距离您的位置：' . $tmp['distance'] . "米\n";
				$message .= '名称为：' . $tmp['name'] . "\n";
				$message .= '地址为：' . $tmp['address'] . "\n";
				$message .= '***************';
				return $this->createText($obj, $message);
			} else {
				return $this->createText($obj, '没有相关服务');
			}
		}
		// 如果不是上述关键词，则用图灵机器人来回复
		$message = $this->aiMsg($obj->Content);
		return $this->createText($obj, $message);
	}

	// 图片触发：回复图片
	private function handleImage($obj)
	{
		return $this->createImage($obj, $obj->MediaId);
	}
  
	// 生成【文本】格式xml数据（$obj是接收的元素post数据;$content是要发送的内容）
	private function createText($obj, string $content)
	{
		$xml = '<xml>
						<ToUserName><![CDATA[%s]]>
						</ToUserName>
						<FromUserName><![CDATA[%s]]>
						</FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[text]]>
						</MsgType>
						<Content><![CDATA[%s]]>
						</Content>
				</xml>';
		$time = time();
		$xmlstr = sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, $content);
		return $xmlstr;
	}	
  
	// 生成【图片】格式xml数据
	private function createImage($obj, string $mediaid)
	{
		$xml = '<xml>
						<ToUserName><![CDATA[%s]]>
						</ToUserName>
						<FromUserName><![CDATA[%s]]>
						</FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[image]]>
						</MsgType>
						<Image>
              <MediaId><![CDATA[%s]]>
              </MediaId>
            </Image>
				</xml>';
		$time = time();
		$xmlstr = sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, $mediaid);
		return $xmlstr;
	}
	
	// 生成【语音】格式xml数据
	private function createVoice($obj, string $mediaid)
	{
		$xml = '<xml>
							<ToUserName><![CDATA[%s]]>
							</ToUserName>
							<FromUserName><![CDATA[%s]]>
							</FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[voice]]>
							</MsgType>
							<Voice>
									<MediaId><![CDATA[%s]]>
									</MediaId>
							</Voice>
						</xml>';
		$time = time();
		$xmlstr = sprintf($xml, $obj->FromUserName, $obj->ToUSerName, $time, $mediaid);
		return $xmlstr;
	}

	// 生成【视频】格式xml数据
	private function createVideo($obj, string $mediaid)
	{
		// 视频的title和description可以省略，删掉xml中的这2个节点即可省略
		$xml = '<xml>
								<ToUserName><![CDATA[%s]]>
								</ToUserName>
								<FromUserName><![CDATA[%s]]>
								</FromUserName>
								<CreateTime>%s</CreateTime>
								<MsgType><![CDATA[video]]>
								</MsgType>
								<Video>
										<MediaId><![CDATA[%s]]>
										</MediaId>
										<Title><![CDATA[%s]]>
										</Title>
										<Description><![CDATA[%s]]>
										</Description>
								</Video>
						</xml>';
		$time = time();
		$title = '我是视频标题';
		$description = '我是视频介绍，我是视频介绍，我是视频介绍';
		$xmlstr = sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, $mediaid, $title, $description);
		return $xmlstr;
	}

	// 生成【图文】格式xml数据
	private function createNews($obj, array $arr)
	{
		$xml = '<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[news]]></MsgType>
							<ArticleCount>%s</ArticleCount>
							<Articles>
							%s
							</Articles>
					</xml>';
		$items = '';
		foreach ($arr as $item) {
			$items .= '<item>
								<Title><![CDATA[' . $item['title'] . ']]>
								</Title>
								<Description><![CDATA[' . $item['description'] . ']]>
								</Description>
								<PicUrl><![CDATA[' . $item['picurl'] . ']]>
								</PicUrl>
								<Url><![CDATA[' . $item['url'] . ']]>
								</Url>
							</item>';
		}
		$time = time();
		return sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, count($arr), $items);
	}

	// 写日志，$data: 日志内容，$flag: 1接收，2发送	
	private function writeLog(string $data, int $flag = 1)
	{
		$preStr = $flag === 1 ? '接收' : '发送';
		$date = date('Y-m-d H:i:s');
		$log = $preStr . '---------------' . $date . PHP_EOL . $data . PHP_EOL; // PHP_EOL 是换行符，不同的系统对应值不同
		// 追加写日志
		file_put_contents('wx.xml', $log, FILE_APPEND); // 第一个参数是文件名，没有的话会创建一个文件
	}

	// 接入时验证
	public function valid()
	{
		$echoStr = $_GET["echostr"];
        //valid signature , option
		if ($this->checkSignature()) {
			echo $echoStr;
			exit;
		}
	}

	// 验证
	private function checkSignature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		// $token = TOKEN; // 全局常量使用
		$token = self::TOKEN; // 内中定义的常量使用
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);

		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
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
