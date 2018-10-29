<?php
// 此页面作为curl-demo的服务器，接收请求后，响应（输出）如下内容

echo '<b>网站根目录是：</b>' . $_SERVER['DOCUMENT_ROOT'];
echo "<hr />";

echo '<b>此次请求的方法是：</b>' . $_SERVER['REQUEST_METHOD'];
echo "<hr />";

echo '<b>此次请求的post参数是：</b>';
if ($_POST) {
  print_r($_POST);
} else {
  echo '客户端没有传post参数';
}
echo "<hr />";

echo '<b>此次上传的文件内容是：</b>';
if ($_FILES) {
  print_r($_FILES);
} else {
  echo '客户端没有传文件';
}

echo "<hr />";
echo "<hr />";


