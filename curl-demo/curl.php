<?php
// 文件地址
$file = __DIR__. '/img/01.jpg';

// 1.0 发送【http】 get请求，并输出结果
$url = 'http://gzh.com/curl-demo/server-demo.php';
echo "<h3>1.0 发送【http】 get请求</h3>";
// echo http_get($url);
echo http_request($url);

// 2.0 发送【https】 get请求，并输出结果
$url = 'https://wx.1314000.cn/';
echo "<h3>2.0 发送【https】 get请求</h3>";
echo http_request($url);
echo "<hr /><hr />";

// 3.0 发送 【http】 post请求，并输出结果
$url = 'http://gzh.com/curl-demo/server-demo.php';
echo "<h3>3.0 发送 【http】 post请求</h3>";
// echo http_post($url, ['id'=>1, 'name'=>'张三', 'age'=>19]);
echo http_request($url, ['id'=>1, 'name'=>'张三', 'age'=>19]);


// 4.0 发送 【http】 post请求，上传文件，并输出结果
// 上传图片到服务器server-demo，并输出$_FILES（注意此demo省略了表单上传，然后服务器转存这2个过程）
$url = 'http://gzh.com/curl-demo/server-demo.php';
echo "<h3>4.0 发送 【http】 post请求，上传文件</h3>";
// echo http_post_file($url, ['id'=>1, 'name'=>'张三', 'age'=>19], $file); 
echo http_request($url, ['id'=>1, 'name'=>'张三', 'age'=>19], $file); 


// curl get请求
function http_get(string $url)
{
  // curl发送请求4步走：初始化、设置选项、执行、关闭
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
  // 3.0 执行
  $data = curl_exec($ch); // 将成功请求的结果，赋值给指定的变量
  $info = curl_getinfo($ch); // 执行后，得到请求结果相关信息
  if ($info['http_code'] !== 200) {
    echo curl_error($ch); // 输出错误,若为https请求，则报错：SSL certificate problem: unable to get local issuer certificate
    echo '<br/>' . '请求失败，状态码：' . $info['http_code'];
  }
  // 4.0 关闭
  curl_close($ch);
  // 返回数据
  return $data;
} 

// curl post请求 (与get请求只有两行设置不同)
function http_post(string $url, array $params)
{
  // curl发送请求4步走：初始化、设置选项、执行、关闭
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

  curl_setopt($ch, CURLOPT_POST, 1); // 设置请求方法为post
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params); // 设置post请求的参数
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

// curl post请求_上传文件
function http_post_file(string $url, array $params = [], string $filepath = '')
{
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

  curl_setopt($ch, CURLOPT_POST, 1); // 设置请求方法为post
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params); // 设置post请求的参数
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

// 封装以上curl请求：支持get、post、post上传文件
function http_request(string $url, $params = [], string $filepath = '')   // $params: array|json
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