<?php
include "../lib/wechat.php";
$pdo = include "../lib/db.php";

  // 特别注意：empty($_FILES['media']) 永远真，不管是否传了文件，没有传文件时，$_FILES['media']是一个包含5个元素的非空组，只是元素值为空罢了
  // 因此：要判断是否上传了文件，应该用 empty($_FILES['media']['name']) 来判断
if (!empty($_FILES['media']['name'])) {
  $type = $_POST['type']; // 上传素材类型
  $is_forever = $_POST['is_forever']; // 是临时还是永久素材

  // 上传文件（素材）到开发者服务器（注意：php默认上传大小为2M）
  $filename = __DIR__ . '/' . $_FILES['media']['name'];
  move_uploaded_file($_FILES['media']['tmp_name'], $filename);

  // 把开发者服务器上的素材，上传到微信服务器
  $wx = new WeChat();
  $ret = $wx->upFile($type, $filename, $is_forever);
  var_dump($ret);
  // $ret['created_at'] = $ret['created_at'] ? $ret['created_at'] : time(); // 如果不存在，会报"Undefined index"错误提醒
  $ret['created_at'] = $ret['created_at'] ?? time(); // php7的条件语句写法，如果不存在，也不会报"Undefined index"错误提醒
  $ret['url'] = $ret['url'] ?? '';

  // 入库操作
  $sql = "insert into material (is_forever,type,media_id,url,filepath,created_at) value (?,?,?,?,?,?)";
  $stmt = $pdo->prepare($sql);
  $res = $stmt->execute([$is_forever, $type, $ret['media_id'], $ret['url'], $filename, $ret['created_at']]);
  var_dump($res);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>上传素材</title>
</head>
<body>
  <form action="" method="post" enctype="multipart/form-data">
    <label for="input">临时还是永久：</label>
    <select name="is_forever" style="width:100px;">  
      <option value="0">临时素材</option>
      <option value="1">永久素材</option>
    </select> <br/><br/>
    <label for="input">选择素材类型：</label>
    <select name="type" style="width:100px;">  
      <option value="image">图片</option>
      <option value="voice">语音</option>
      <option value="video">视频</option>
    </select> <br/><br/>
    <label for="input">上传临时素材：</label>
    <input type="file" name="media" id=""> <br/><br/>
    <button type="submit">提交</button>
  </form>
  
</body>
</html>