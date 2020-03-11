<?php

/**
 * ECSHOP 首页文件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: index.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/lib_transaction.php');
include_once(ROOT_PATH . 'includes/lib_payment.php');
include_once(ROOT_PATH . 'includes/lib_order.php');
include_once(ROOT_PATH . 'includes/lib_clips.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';
if($_POST['password'])
{
	$mobile = $_POST['mobile'];
	$sql = "SELECT  user_name FROM ".$ecs->table('users')." WHERE office_phone='".$mobile."' or home_phone='".$mobile."' or mobile_phone='".$mobile."' ";
	$pan = $db->getOne($sql);
	$username = $pan;
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $back_act =  'user.php?act=order_list&type=order_list';
    
	if(!empty($_REQUEST['username']))
	{
		$username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
		$password = isset($_REQUEST['password']) ? trim($_REQUEST['password']) : '';
		//$back_act = '/';
	}
    
    
    $captcha = intval($_CFG['captcha']);

    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }
    }
    if(is_email($username))
    {
    	$sql ="select user_name from ".$ecs->table('users')." where email='".$username."'";
    	$username_e = $db->getOne($sql);
    	if($username_e) $username=$username_e;
    }
    if(is_telephone($username))
    {
    	$sql ="select user_name from ".$ecs->table('users')." where mobile_phone='".$username."'";
    	$username_e = $db->getOne($sql);
    	if($username_e) $username=$username_e;
    }
    if ($user->login($username, $password,isset($_POST['remember'])))
    {
        update_user_info();
        recalculate_price();

        $ucdata = isset($user->ucdata)? $user->ucdata : '';
        show_message($_LANG['login_success'] . $ucdata , array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act,'user.php'), 'info');
    }
    else
    {
        $_SESSION['login_fail'] ++ ;
        show_message($_LANG['login_failure'], $_LANG['relogin_lnk'], 'user.php', 'error');
    }
	exit;
}

if($_POST['mobile_code']){
	//$_POST['mobile'] = $_POST['code'].' '.$_POST['mobile'];
	if($_POST['mobile_code']!=$_SESSION['mobile_code'] or empty($_POST['mobile']) or empty($_POST['mobile_code'])){
		
		echo ' <script language="javascript"> alert("手机验证码输入错误。'.'");window.location.href="index.php"; </script>';
		
		exit;
	}else{
        $_SESSION['tel_order'] = $_SESSION['mobile'];
        unset($_SESSION['mobile']);
        unset($_SESSION['mobile_code']);
    }
}
if(empty($_SESSION['tel_order'])&&1!=1){
	echo ' <script language="javascript"> alert("請用手機驗證碼進行查詢。");window.location.href="index.php";  </script>';
	
	exit;
}else{
    $mobile = $_POST['mobile'];
}

if($action == 'list'){
    if(isset($_REQUEST['unpay'])){
        $p=$_REQUEST['unpay'];
    }else{
        $p=0;
    }
    assign_template();
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $type = empty($_REQUEST['type'])? 'await_pay_long' : $_REQUEST['type'];

    $mobile = $_REQUEST['mobile'];
    
    $code_mobile = $_REQUEST['code2'].' '.$_REQUEST['mobile'];
    $res = order_list_tel($mobile,$code_mobile,$type,$page);

    $smarty->assign('action_type',$type);
    $smarty->assign('is_pay',$p);
    $smarty->assign('record_count_pay',  $res['record_count_pay']);
    $smarty->assign('pager',  $res['pager']);
    $smarty->assign('orders', $res['orders']);
    $smarty->display('tel_order.dwt');
}
elseif($action == 'order_detail'){
    assign_template();
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    /* 订单详情 */
    $order = get_order_detail($order_id);

    if ($order === false)
    {
        $err->show($_LANG['back_home_lnk'], './');

        exit;
    }

    /* 是否显示添加到购物车 */
    if ($order['extension_code'] != 'group_buy' && $order['extension_code'] != 'exchange_goods')
    {
        $smarty->assign('allow_to_cart', 1);
    }

    /* 订单商品 */
    $goods_list = order_goods($order_id);
    foreach ($goods_list AS $key => $value)
    {
        $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
        $goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
        $goods_list[$key]['subtotal']     = price_format($value['subtotal'], false);
        $goods_list[$key]['yh']           = floatval($value['market_price']) - floatval($value['goods_price']);
        $goods_list[$key]['img']          = get_image_path($value['goods_id'],$GLOBALS['db']->getOne("select goods_thumb from ".$GLOBALS['ecs']->table('goods').
            " where goods_id =".$value['goods_id']));
    }

    /* 未发货，未付款时允许更换支付方式 */
    if ($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED)
    {
        $payment_list = available_payment_list(false, 0, true);

        /* 过滤掉当前支付方式和余额支付方式 */
        if(is_array($payment_list))
        {
            foreach ($payment_list as $key => $payment)
            {
                if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance')
                {
                    unset($payment_list[$key]);
                }
            }
        }
        $smarty->assign('payment_list', $payment_list);
    }

    /* 订单 支付 配送 状态语言项 */
    $order['pay_status_value'] = $order['pay_status'];
    $order['order_status'] = $_LANG['os'][$order['order_status']];
    $order['pay_status'] = $_LANG['ps'][$order['pay_status']];
    $order['shipping_status'] = $_LANG['ss'][$order['shipping_status']];
    $order['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);

    $smarty->assign('order',      $order);
    $smarty->assign('goods_list', $goods_list);

    $smarty->display('tel_detail.dwt');
}
elseif($action == 'act_edit_payment'){
    /* 检查支付方式 */
    $pay_id = intval($_POST['pay_id']);
    if ($pay_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    $payment_info = payment_info($pay_id);
    if (empty($payment_info))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单号 */
    $order_id = intval($_POST['order_id']);
    if ($order_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得订单 */
    $order = order_info($order_id);
    if (empty($order))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变*/
    if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id)
    {
        ecs_header("Location: reg.php?act=order_detail&order_id=$order_id\n");
        exit;
    }

    $order_amount = $order['order_amount'] - $order['pay_fee'];
    $pay_fee = pay_fee($pay_id, $order_amount);
    $order_amount += $pay_fee;

    $sql = "UPDATE " . $ecs->table('order_info') .
        " SET pay_id='$pay_id', pay_name='$payment_info[pay_name]', pay_fee='$pay_fee', order_amount='$order_amount'".
        " WHERE order_id = '$order_id'";
    $db->query($sql);

    /* 跳转 */
    ecs_header("Location: reg.php?act=order_detail&order_id=$order_id\n");
    exit;
}

/* 取消订单 */
elseif($action == 'cancel_order')
{

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (cancel_order($order_id, 0))
    {
        ecs_header("Location: reg.php\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'reg.php');
    }
}

/* 确认收货 */
elseif($action == 'affirm_received')
{
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (affirm_received($order_id, 0))
    {
        ecs_header("Location: reg.php\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'reg.php');
    }
}


/*訂單列表*/
function order_list_tel($mobile,$code_mobile,$type,$page){

	if($mobile=='')
	{
		return null;
	}
    $action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';
    $pager=array();
    $orders=array();

    $record_count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."')");
   
    $record_count_pay = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."')".order_query_sql('await_pay_long'));
    $record_count_ship = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."')".order_query_sql('await_ship'));
    $record_count_shipped = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."')".order_query_sql('await_shipped'));
    $record_count_shipped_x = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."') ". "AND referer!='' AND referer!='本站' AND referer!='管理員添加'");
  
    $sql = "SELECT order_id, order_sn,cart_rate, order_status, shipping_status, pay_status,referer, add_time, " .
        "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
        " FROM " .$GLOBALS['ecs']->table('order_info') .
        " WHERE (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."')";

    switch($type){
        case 'order_list':
            $pager  = get_pager('reg.php', array('act' => $action), $record_count, $page);
            $orders = get_tel_orders($mobile, $pager['size'], $pager['start']);
            break;
        case 'await_pay_long':
            $pager  = get_pager('reg.php', array('act' => $action), $record_count_pay, $page);
            $res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_pay_long')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
            break;
        case 'await_ship':
            $pager  = get_pager('reg.php', array('act' => $action), $record_count_ship, $page);
            $res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_ship')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
            break;
        case 'await_shipped':
            $pager  = get_pager('reg.php', array('act' => $action), $record_count_shipped, $page);
            $res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_shipped')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
            break;
        case 'await_shipped_x':
        	$pager  = get_pager('reg.php', array('act' => $action), $record_count_shipped_x, $page);
        	$res = $GLOBALS['db']->SelectLimit($sql." AND referer!='' AND referer!='本站' AND referer!='管理員添加' "." ORDER BY add_time DESC", $pager['size'], $pager['start']);
        	break;
    }
    
    if(!empty($res)){
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
            $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];

            switch($type){
                case 'await_pay_long':
                    $order_pay = get_order_detail($row['order_id']);
                    $row['handler'] = $order_pay['pay_online'];
                    break;
                case 'await_ship':
                    $row['handler'] = '<span style="color:red">'.'已確認' .'</span>';
                    break;
                case 'await_shipped':
                    $row['handler'] = "<a href=\"reg.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_received']."')) return false;\">".$GLOBALS['_LANG']['received']."</a>";
                    break;
            }

            $num = $GLOBALS['db']->getOne("select sum(goods_number) from".$GLOBALS['ecs']->table('order_goods')." where order_id = ".$row['order_id']);

            $orders[] = array('order_id'       => $row['order_id'],
                'order_sn'       => $row['order_sn'],
                'order_num'      => $num,
            	'cart_rate'      =>$row['cart_rate'],
            	'referer'        =>$row['referer'],
                'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
                'order_status'   => $row['order_status'],
                'total_fee'      => $row['total_fee'], false,
                'handler'        => $row['handler']);
        }
    }

    $list['record_count'] = $record_count;
    $list['record_count_pay'] = $record_count_pay;
    $list['record_count_ship'] = $record_count_ship;
    $list['record_count_shipped'] = $record_count_shipped;
    $list['record_count_shipped_x'] = $record_count_shipped_x;
    $list['pager'] = $pager;
    $list['orders'] = $orders;

    return $list;
}

/**
 *  获取用户指定范围的订单列表
 *
 * @access  public
 * @param   int         $user_id        手機號
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function get_tel_orders($tel, $num = 10, $start = 0)
{
    /* 取得订单列表 */
    $arr    = array();

    $sql = "SELECT order_id, order_sn, order_status, shipping_status, pay_status, add_time, " .
        "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
        " FROM " .$GLOBALS['ecs']->table('order_info') .
        " WHERE (tel = '".$tel."' or mobile = '".$tel."') ORDER BY add_time DESC";
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['order_status'] == OS_UNCONFIRMED)
        {
            $row['handler'] = "<a href=\"reg.php?act=cancel_order&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_cancel']."')) return false;\">".$GLOBALS['_LANG']['cancel']."</a>";
        }
        else if ($row['order_status'] == OS_SPLITED)
        {
            /* 对配送状态的处理 */
            if ($row['shipping_status'] == SS_SHIPPED)
            {
                @$row['handler'] = "<a href=\"reg.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_received']."')) return false;\">".$GLOBALS['_LANG']['received']."</a>";
            }
            elseif ($row['shipping_status'] == SS_RECEIVED)
            {
                @$row['handler'] = '<span style="color:red">'.$GLOBALS['_LANG']['ss_received'] .'</span>';
            }
            else
            {
                if ($row['pay_status'] == PS_UNPAYED)
                {
                    @$row['handler'] = "<a href=\"reg.php?act=order_detail&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['pay_money']. '</a>';
                }
                else
                {
                    @$row['handler'] = "<a href=\"reg.php?act=order_detail&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['view_order']. '</a>';
                }

            }
        }
        else
        {
            $row['handler'] = '<span style="color:red">'.$GLOBALS['_LANG']['os'][$row['order_status']] .'</span>';
        }

        $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
        $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];

        $num = $GLOBALS['db']->getOne("select sum(goods_number) from".$GLOBALS['ecs']->table('order_goods')." where order_id = ".$row['order_id']);

        $arr[] = array('order_id'       => $row['order_id'],
            'order_sn'       => $row['order_sn'],
            'order_num'      => $num,
            'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
            'order_status'   => $row['order_status'],
            'total_fee'      => $row['total_fee'], false,
            'handler'        => $row['handler']);
    }

    return $arr;
}
function is_telephone($phone){
	$chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/";
	if (preg_match($chars, $phone)){
		return true;
	}
}
?>