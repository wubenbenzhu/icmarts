<?php

//配置
$db_host = '192.168.1.111:3306';
$db_user = 'root';
$db_pwd = 'onlinetrade';
$db_date = 'chartclient';

$admin_name = 'long';
$admin_pwd = md5('admin888');


$name = empty($_REQUEST['user']) ? '' : $_REQUEST['user'];
$pwd =  empty($_REQUEST['pwd']) ? '' : $_REQUEST['pwd'];
$code =  empty($_REQUEST['code']) ? '' : $_REQUEST['code'];
$linetype =  empty($_REQUEST['linetype']) ? '' : $_REQUEST['linetype'];
$datetime =  empty($_REQUEST['datetime']) ? '' : $_REQUEST['datetime'];
$count =  empty($_REQUEST['count']) ? 0 : intval($_REQUEST['count']);

$user_type = 0;
if(empty($name) || empty($pwd)){
    //账户错误
    exit;
}else{
    if($name == $admin_name){
        //密码错误，提示并跳出
        if($pwd != $admin_pwd){
            exit;
        }else{
            //正确
            $user_type = 1;
        }
    }else{
        //登陆失败，提示并跳出
        exit;
    }
}

if(!empty($user_type)){
    $con = mysql_connect($db_host,$db_user,$db_pwd);
    if (!$con){
        die('数据库连接错误: ' . mysql_error());
    }
    $re = mysql_select_db($db_date, $con);
    mysql_query("SET NAMES 'gbk'");
    if(!$re){
        die( mysql_error());
    }

    if(empty($code)){
        //行情
        $list = goods_list();
        echo $list;
    }else{
        //K线
        $date = k_date($code,$linetype,$datetime,$count);
        echo $date;
    }
    mysql_close($con);
}

//行情接口
function goods_list(){
    $sql = "select * from quotations";
    $result = mysql_query($sql);
    $text = '';
    while($row = mysql_fetch_array($result)){
        $text.= $row['product_code'].",".$row['product_name'].",".$row['updatetime'].",".$row['nowprice'].",".$row['openpriceT'].",".$row['closingpriceY'].",".
            $row['highprice'].",".$row['lowprice'].",".$row['buyprice'].",".$row['sellprice']."\n";
    }

    return $text;
}

//K线接口
function k_date($code,$linetype,$datetime,$count){

    $sql = "select chart_name from quotations where product_code = '".$code."' LIMIT 1";
    $result = mysql_query($sql);
    $chart_name = '';
    if ($result !== false)
    {
        $row = mysql_fetch_row($result);

        if ($row !== false)
        {
            $chart_name = $row[0];
        }
        else
        {
            exit;
        }
    }

    if(strtotime($datetime) == false){
        exit;
    }


    switch($linetype){
        case 'half':
            $table_name = '_thirty_min';
            break;
        case 'day':
            $table_name = '_one_day';
            break;
        case 'week':
            $table_name = '_seven_day';
            break;
        case 'month':
            $table_name = '_thirty_day';
            break;
        default:
            exit;
            break;
    }
    if(!empty($chart_name)){
        $table = $chart_name.$table_name;

        if($count > 200){
            $count = 200;
        }

        $sql = "select  *  from ". $table." where  UNIX_TIMESTAMP(p_date) < UNIX_TIMESTAMP('".$datetime."') order by UNIX_TIMESTAMP(p_date) desc LIMIT $count";
        $result = mysql_query($sql);

        $text = '';
        while($row = mysql_fetch_array($result)){
            $text.= $row['p_date'].",".$row['p_open'].",".$row['p_close'].",".$row['p_high'].",".$row['p_low'].",".$row['dealamount']."\n";
        }
        return $text;

    }else{
        exit;
    }

}

?>