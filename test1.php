<?php
/*
    young 375858706@qq.com
    2017年2月24日
*/
$fp = @fopen("C:\inetpub\wwwroot\icmarts\test.txt", "a+");
date_default_timezone_set(PRC);
$data = date("Y-m-d H:i:s",time()); 
fwrite($fp , $data. " 让PHP定时运行吧！<br>");fclose($fp);

$headers = array(
    "Content-Type:application/json"
);

// $url = "http://localhost/webpost/api/bonus.php?act=bonus_info";
$url = "https://www.icmarts.com/messenger/webh";
$post_data = '{"object": "page", "entry": [{"messaging": [{"message": "TEST_MESSAGE"}]}]}';
//$post_data = json_decode($post_data);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// post数据
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// post的变量
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

$output = curl_exec($ch);
curl_close($ch);
var_dump($output);
?>