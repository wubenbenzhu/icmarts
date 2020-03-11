<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');




/*function Post($curlPost,$url){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
		$return_str = curl_exec($curl);
		curl_close($curl);
		return $return_str;
}
function xml_to_array($xml){
	$reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
	if(preg_match_all($reg, $xml, $matches)){
		$count = count($matches[0]);
		for($i = 0; $i < $count; $i++){
		$subxml= $matches[2][$i];
		$key = $matches[1][$i];
			if(preg_match( $reg, $subxml )){
				$arr[$key] = xml_to_array( $subxml );
			}else{
				$arr[$key] = $subxml;
			}
		}
	}
	return $arr;
}

function fillZero($str){
    if(strlen($str) < 1){
        return "0000";
    }else if(strlen($str) < 2){
        return "000".$str;
    }else if(strlen($str) < 3){
        return "00".$str;
    }else if(strlen($str) < 4){
        return "0".$str;
    }else{
        return $str;
    }
}

function uniord($ch) {

    $n = ord($ch{0});

    if ($n < 128) {
        return $n; // no conversion required
    }

    if ($n < 192 || $n > 253) {
        return false; // bad first byte || out of range
    }

    $arr = array(1 => 192, // byte position => range from
        2 => 224,
        3 => 240,
        4 => 248,
        5 => 252,
    );

    foreach ($arr as $key => $val) {
        if ($n >= $val) { // add byte to the 'char' array
            $char[] = ord($ch{$key}) - 128;
            $range  = $val;
        } else {
            break; // save some e-trees
        }
    }

    $retval = ($n - $range) * pow(64, sizeof($char));

    foreach ($char as $key => $val) {
        $pow = sizeof($char) - ($key + 1); // invert key
        $retval += $val * pow(64, $pow);   // dark magic
    }

    return $retval;
}

function getUTF8($str){
    $output = "";
    $encStr = $str;
    for($i=0; $i<strlen($str); $i=$i+1){
        $tmpCh = uniord($encStr);
        if($tmpCh){
            if($tmpCh > 254){
                $encStr = substr($encStr, 3, strlen($encStr)-3);
                $i = $i + 2;
            }else{
                $encStr = substr($encStr, 1, strlen($encStr)-1);
            }
            $tmpCh = strtoupper(dechex($tmpCh));
            $tmpCh = fillZero($tmpCh);
            $output = $output."&#x".$tmpCh.";";
        }else{ //Unknown charaters
            $output = $output.substr($encStr, 0, 1);
            $encStr = substr($str, 1, strlen($encStr)-1);
        }
    }
    return $output;
}

function random1($length = 6 , $numeric = 0) {
	PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
	if($numeric) {
		$hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
	} else {
		$hash = '';
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $chars[mt_rand(0, $max)];
		}
	}
	return $hash;
}*/
//$target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
$target = "http://openapi.mdtechcorp.com:20000/openapi/";
$mobile = $_POST['code'].$_POST['mobile'];

$send_code = $_POST['send_code'];

$mobile_code = random1(4,1);
if(empty($mobile)){
	exit('手機號碼不能為空');
}

if(empty($_SESSION['send_code']) or $send_code!=$_SESSION['send_code']){
	//防用户恶意请求
	
	exit('請求超時，請刷新頁面後重試');
}
//96507440
 //$post_data = "account=cf_icmarts&password=dk123456&mobile=".$mobile."&content=".rawurlencode("你的ICMARTS驗證碼是：".$mobile_code."。請不要把驗證碼泄露給他人。如非本人操作，請勿理會。");
$post_data ="你的ICMARTS驗證碼是：".$mobile_code."。請不要把驗證碼泄露給他人。如非本人操作，請勿理會。";
//密码可以使用明文密码或使用32位MD5加密

$gets = Post($mobile,$post_data);

if(empty($gets)){
    $get['msg'] = '提交失敗';
}else{
    $_SESSION['mobile'] = $mobile;
    $_SESSION['mobile_code'] = $mobile_code;

    $get['msg'] = '提交成功';
}
echo $get['msg'];
?>