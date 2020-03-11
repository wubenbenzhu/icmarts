<?php

/**
 * ECSHOP 会员中心
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: user.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');


include_once(ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

//$_SESSION['facebook_user'] = 0;
$user_id = $_SESSION['user_id'];

$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
$back_act='';
$smarty->assign('helps',      get_shop_help());

/*新增的广告*/
$time = gmtime();
$sql = 'SELECT ad_link,ad_code,ad_id FROM ' . $ecs->table("ad") . " WHERE position_id = 10 and enabled=1 and ".$time." >= start_time and ".$time." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";

$ad_hdu = $db->getRow($sql);
if($ad_hdu)
{
	$ad_hdu['ad_link'] = "affiche.php?ad_id=".$ad_hdu['ad_id']."&amp;uri=" .urlencode($ad_hdu["ad_link"]);
	$ad_hdu['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hdu['ad_code'];
	
}
$smarty->assign('ad_hdu', $ad_hdu);  //A4
/*新增的广告*/
// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_login_arr =
array('login','logindl','act_login','act_logindl','register','register_xj','registerdlnew','act_register','act_edit_password','get_password','send_pwd_email','password', 'signin','is_tel','user_bonus','receive_bonus',
    'add_tag', 'collect', 'return_to_cart', 'logout', 'email_list', 'validate_email', 'send_hash_mail', 'order_query', 'is_registered','register_new','check_name','check_email1','face_user_new','is_tel_new',
    'check_email','clear_history','qpassword_name', 'get_passwd_question', 'check_answer', 'fboath' , 'fboath_login', 'face_user', 'get_user_vip', 'oath' , 'oath_login', 'other_login');

/* 显示页面的action列表 */
$ui_arr = array('register','register_xj', 'login','logindl', 'profile', 'registerdlnew','order_listdl','order_listfl','order_list', 'order_detail', 'address_list','set_default_address', 'collection_list',
'message_list', 'tag_list', 'get_password', 'reset_password', 'booking_list', 'add_booking', 'account_raply','user_bonus',
'account_deposit', 'account_log', 'dlfc_log','account_detail', 'act_account', 'pay', 'default', 'bonus', 'group_buy', 'group_buy_detail',
'affiliate', 'comment_list','validate_email','track_packages', 'transform_points','qpassword_name', 'get_passwd_question', 'check_answer','refund_list','refund_goods','refund');

if($action == 'user_bonus'){

	if(empty($_REQUEST['id'])) {
		$id = 12;
	}else{
		$id = $_REQUEST['id'];
	}
	$sql = "select * from ".$GLOBALS['ecs']->table('bonus_type')." where type_id=$id";
	$info = $GLOBALS['db']->getRow($sql);

	$info['is_show'] = 0;

	$time = time();

	if(intval($info['send_start_date']) > $time){
		$info['is_show'] = 1;
	}
	if($info['send_end_date'] < $time){
		$info['is_show'] = 2;
	}

	$smarty->assign('bonus', $info);

	if(empty($_REQUEST['id'])) {
		$smarty->display('bonus.dwt');
	}else{
		if($_REQUEST['id']==10)
		{
			$smarty->display('bonus3.dwt');
		}else 
		{
			$smarty->display('bonus2.dwt');
		}
		
	}
}

//领取现金券 long
if($action == 'receive_bonus'){
	if(empty($user_id)){
		ecs_header("Location: user.php");
		exit;
	}else{
		$id = $_REQUEST['id'];

		do {
			$num = rand(10000000,100000000);
			$sql="select count(*) from ".$GLOBALS['ecs']->table('user_bonus')." where bonus_sn =".$num;
			$re= $GLOBALS['db']->getOne($sql);
		}while($re);

		$sql = "SELECT type_money FROM " . $GLOBALS['ecs']->table('bonus_type') ." WHERE type_id = '".$id."'";

		$bonus_money=$db->getOne($sql);

		/* 向会员现金卷表录入数据 */
		$sql = "INSERT INTO " . $ecs->table('user_bonus') .
			"(bonus_type_id, bonus_sn, user_id, used_time, order_id, emailed, bonus_money) " .
			"VALUES ('$id', '$num', '$user_id', 0, 0, " .BONUS_NOT_MAIL. ", '$bonus_money')";
		$db->query($sql);

		header('location: '.$_SERVER['HTTP_REFERER']);

	}
}


if($action == 'chuange_mobile')
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 0);
	$mobile = $_REQUEST['mobile'];
	$code = $_REQUEST['code'];
	$code_mobile = $code.' '.$mobile;
	$sql = "SELECT  count(*) FROM ".$ecs->table('users')." WHERE office_phone='".$mobile."' or home_phone='".$mobile."' or mobile_phone='".$mobile."' and 1<>1 ";
	$pan = $db->getOne($sql);
	$res['qty'] = $pan;
	$sql = "SELECT  count(*) FROM ".$ecs->table('order_info')." WHERE  (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."') ";
	$pan1 = $db->getOne($sql);
	$res['qty1'] = $pan1;
    $sql2 = "SELECT  aite_id FROM ".$ecs->table('users')." WHERE office_phone='".$mobile."' or home_phone='".$mobile."' or mobile_phone='".$mobile."' and 1<>1 ";
    $pan2 = $db->getOne($sql2);
    if(!empty($pan2)){
        $res['qty2'] = 1;
    }else{
        $res['qty2'] = 0;
    }
    die($json->encode($res));
}
if($action == 'chuange_mobile_pass')
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 0);
	$mobile = $_REQUEST['mobile'];
	$code = $_REQUEST['code'];
	$sql = "SELECT  count(*) FROM ".$ecs->table('users')." WHERE office_phone='".$mobile."' or home_phone='".$mobile."' or mobile_phone='".$mobile."' ";
	$pan = $db->getOne($sql);
	if(!empty($pan)){
		$res['qty'] = 1;
	}else{
		$res['qty'] = 0;
	}
	die($json->encode($res));
}

if($action == 'chuange_mobile_code')
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 0);
	$mobile_code = $_REQUEST['mobile_code'];
	if(isset($_SESSION['mobile_code'])&&$mobile_code==$_SESSION['mobile_code']){
		$res['qty'] = 1;
	}else{
		$res['qty'] = 0;
	}
	die($json->encode($res));
}

if ($action == 'vip_mobile')
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 0);
	$mobile = $_REQUEST['mobile'];
	$sql = "SELECT  image_er,user_id FROM ".$ecs->table('users')." WHERE (office_phone=".$mobile." or home_phone=".$mobile." or mobile_phone=".$mobile.") order by image_er desc";
	$pan = $db->getRow($sql);
	if(empty($pan['image_er']))
	{
		$res['err_msg'] = '該號碼還未有VIP卡。請聯繫客服。';
	}else 
	{
		$res['qty2'] = $pan['image_er'];
	}
	//print_r($pan);die(); 
	die($json->encode($res));
	
}
if($action == 'chuange_mobile2')
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 0);
	$mobile = $_REQUEST['mobile'];
	$code = $_REQUEST['code'];
	$code_mobile = $code.' '.$mobile;
	$sql = "SELECT  count(*) FROM ".$ecs->table('order_info')." WHERE  (tel = '".$mobile."' or mobile = '".$mobile."' or tel = '".$code_mobile."' or mobile = '".$code_mobile."') AND pay_status='0' and order_status in (0,1,5)";
	$res['qty']= $db->getOne($sql);
    die($json->encode($res));
}
/* 未登录处理 */
if (empty($_SESSION['user_id']))
{
    if (!in_array($action, $not_login_arr))
    {
        if (in_array($action, $ui_arr))
        {
            /* 如果需要登录,并是显示页面的操作，记录当前操作，用于登录后跳转到相应操作
            if ($action == 'login')
            {
                if (isset($_REQUEST['back_act']))
                {
                    $back_act = trim($_REQUEST['back_act']);
                }
            }
            else
            {}*/
            if (!empty($_SERVER['QUERY_STRING']))
            {
                $back_act = 'user.php?' . strip_tags($_SERVER['QUERY_STRING']);
            }
            $action = 'login';
        }
        else
        {
            //未登录提交数据。非正常途径提交数据！
            die($_LANG['require_login']);
        }
    }
}

/* 如果是显示页面，对页面进行相应赋值 */
if (in_array($action, $ui_arr))
{
    assign_template();
    $position = assign_ur_here(0, $_LANG['user_center']);
    $smarty->assign('page_title', $position['title']); // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);
    $sql = "SELECT value FROM " . $ecs->table('shop_config') . " WHERE id = 419";
    $row = $db->getRow($sql);
    $car_off = $row['value'];
    $smarty->assign('car_off',       $car_off);
    /* 是否显示积分兑换 */
    if (!empty($_CFG['points_rule']) && unserialize($_CFG['points_rule']))
    {
        $smarty->assign('show_transform_points',     1);
    }
    $smarty->assign('data_dir',   DATA_DIR);   // 数据目录
    $smarty->assign('action',     $action);
    $smarty->assign('lang',       $_LANG);
}

//用户中心欢迎页
if ($action == 'default')
{
    include_once(ROOT_PATH .'includes/lib_clips.php');
    $sql = "SELECT dl_pd FROM " . $ecs->table('user_rank') . " WHERE rank_id = '$_SESSION[user_rank]'";
    $row_rank = $db->getOne($sql);

    if($row_rank==1)//判断代理会员
    {
    	$sql = "SELECT Empower_img FROM ".$ecs->table('users')." WHERE user_id=".$_SESSION['user_id'];
    	$Empower_img = $db->getOne($sql);
    	if(empty($Empower_img)||$Empower_img=='')
    	{
    		$pan = add_dlimage($_SESSION['user_id']);
    		if($pan)//资料填全，生成代理证书
    		{
    			//生成二维码并插入数据库接口调用
    			//$bodys = curl_main('http://115.160.142.214/webposnet/Common/EmpowerAuthorizationByuid?user_id='.$_SESSION['user_id']);
    			//$bodys = curl_main('http://13.75.95.147/webpostest/Common/EmpowerAuthorizationByuid?user_id='.$_SESSION['user_id']);
    			//调用三石那边方法生成代理证书,并更新数据库
    		}
    	}
    }
    
    
    $smarty->assign('row_rank',        $row_rank);
    
    if ($rank = get_rank_info())
    {
    	
        $smarty->assign('rank_name', sprintf($_LANG['your_level'], $rank['rank_name']));
        if (!empty($rank['next_rank_name']))
        {
            $smarty->assign('next_rank_name', sprintf($_LANG['next_level'], $rank['next_rank'] ,$rank['next_rank_name']));
        }
    }
    $smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
    $smarty->assign('info',        get_user_default($user_id));
    $smarty->assign('user_notice', $_CFG['user_notice']);
    $smarty->assign('prompt',      get_user_prompt($user_id));
    
    $smarty->display('user_clips.dwt');
}
/* 显示代理幫下級註冊界面 */
if ($action == 'register_xj')
{
	if ((!isset($back_act)||empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
    {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    }
    $smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);

    /* 验证码相关设置 */
    if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
    {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand',            mt_rand());
    }

    /* 密码提示问题 */
    $smarty->assign('passwd_questions', $_LANG['passwd_questions']);

    /*店鋪列表*/
    $sql="select * from ".$ecs->table('area')." where areaid <>0 and state=1";
    $area_list = $db->getAll($sql);
    $smarty->assign('area_list', $area_list);

    /* 增加是否关闭注册 */
    $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
//    $smarty->assign('back_act', $back_act);
    $smarty->display('user_passport.dwt');
}

/* 显示会员注册界面 */
if ($action == 'register_old')
{
	
    if ((!isset($back_act)||empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
    {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    }
    $smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);

    /* 验证码相关设置 */
    if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
    {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand',            mt_rand());
    }

    /* 密码提示问题 */
    $smarty->assign('passwd_questions', $_LANG['passwd_questions']);
	//$facebookurl = login_facebook();
$smarty->assign('facebookurl', login_facebook());
    /*店鋪列表*/
    $sql="select * from ".$ecs->table('area')." where areaid <>0 and state=1";
    $area_list = $db->getAll($sql);
    $smarty->assign('area_list', $area_list);

    /* 增加是否关闭注册 */
    $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
//    $smarty->assign('back_act', $back_act);

    $smarty->display('user_passport.dwt');
}

/* 显示会员注册界面 */
if ($action == 'register')
{
	if(empty($_SESSION['send_code']))
		$_SESSION['send_code'] = random(6,1);

	if ((!isset($back_act)||empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
	{
		$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
	}
	$smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
	/* 取出注册扩展字段 */
	$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
	$extend_info_list = $db->getAll($sql);
	$smarty->assign('extend_info_list', $extend_info_list);

	/* 验证码相关设置 */
	if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
	{
		$smarty->assign('enabled_captcha', 1);
		$smarty->assign('rand',            mt_rand());
	}

	/* 密码提示问题 */
	$smarty->assign('passwd_questions', $_LANG['passwd_questions']);
	//$facebookurl = login_facebook();
	$smarty->assign('facebookurl', login_facebook());
	/*店鋪列表*/
	$sql="select * from ".$ecs->table('area')." where areaid <>0 and state=1";
	$area_list = $db->getAll($sql);
	$smarty->assign('area_list', $area_list);

	/* 增加是否关闭注册 */
	$smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
//    $smarty->assign('back_act', $back_act);

	$smarty->display('user_regist.dwt');
}

/* 验证用户邮箱地址是否被注册 */
elseif($action == 'check_name'){
	include_once('includes/cls_json.php');
	$json = new JSON;
	$name = trim($_GET['name']);
	if ($user->check_user($name))
	{
		$result = 1;
	}
	else
	{
		$result = 0;
	}

	die($json->encode($result));
}

elseif($action == 'check_email1')
{
	include_once('includes/cls_json.php');
	$json = new JSON;
	$email = trim($_GET['email']);
	if ($user->check_email($email))
	{
		$result = 1;
	}
	else
	{
		$result = 0;
	}

	die($json->encode($result));
}

/* 显示会员注册界面 */
if ($action == 'registerdlnew')
{
	if ((!isset($back_act)||empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
	{
		$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
	}
	$smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
	/* 取出注册扩展字段 */
	$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
	$extend_info_list = $db->getAll($sql);
	$smarty->assign('extend_info_list', $extend_info_list);

	/* 验证码相关设置 */
	if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
	{
		$smarty->assign('enabled_captcha', 1);
		$smarty->assign('rand',            mt_rand());
	}

	/* 密码提示问题 */
	$smarty->assign('passwd_questions', $_LANG['passwd_questions']);

	/*店鋪列表*/
	$sql="select * from ".$ecs->table('area')." where areaid <>0 and state=1";
	$area_list = $db->getAll($sql);
	$smarty->assign('area_list', $area_list);

	/* 增加是否关闭注册 */
	$smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
	//    $smarty->assign('back_act', $back_act);
	$smarty->display('user_registerdlnew.dwt');
}
/* 代理注册下級会员的处理 */
elseif ($action == 'act_register_xj')
{
	/* 增加是否关闭注册 */
	if ($_CFG['shop_reg_closed'])
	{
		$smarty->assign('action',     'register');
		$smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
		$smarty->display('user_passport.dwt');
	}
	else
	{
		clear_cart();
		include_once(ROOT_PATH . 'includes/lib_passport.php');
		
		$username = isset($_POST['username']) ? trim($_POST['username']) : '';
		$password = isset($_POST['password']) ? trim($_POST['password']) : '';
		$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
		$areaid   = isset($_POST['areaid']) ? intval($_POST['areaid']) : '';
		$user_type = isset($_POST['user_type']) ? intval($_POST['user_type']) : '0';
		$other['msn'] = isset($_POST['extend_field1']) ? $_POST['extend_field1'] : '';
		$other['qq'] = isset($_POST['extend_field2']) ? $_POST['extend_field2'] : '';
		$other['office_phone'] = isset($_POST['extend_field3']) ? $_POST['extend_field3'] : '';
		$other['home_phone'] = isset($_POST['extend_field4']) ? $_POST['extend_field4'] : '';
		$other['mobile_phone'] = isset($_POST['extend_field5']) ? $_POST['extend_field5'] : '';
		$sel_question = empty($_POST['sel_question']) ? '' : $_POST['sel_question'];
		$passwd_answer = isset($_POST['passwd_answer']) ? trim($_POST['passwd_answer']) : '';
		
		
		$back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';
		if(empty($_POST['agreement']))
		{
			show_message($_LANG['passport_js']['agreement']);
		}
		if(empty($_POST['extend_field5']))
		{
			show_message('手機號不能為空');
		}
		if(empty($_POST['extend_field8']))
		{
			//show_message('國家號不能為空');
		}
		
		$sql = "select count(*) from ".$GLOBALS['ecs']->table('users')." where user_name = '".$username."'";
		if($GLOBALS['db']->getOne($sql)){
			show_message('用户名重复');
		}
		
		if (strlen($username) < 3)
		{
			show_message($_LANG['passport_js']['username_shorter']);
		}
		
		if (strlen($password) < 6)
		{
			show_message($_LANG['passport_js']['password_shorter']);
		}
		
		if (strpos($password, ' ') > 0)
		{
			show_message($_LANG['passwd_balnk']);
		}
		
		/* 验证码检查 */
		if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
		{
			if (empty($_POST['captcha']))
			{
				show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
			}
		
			/* 检查验证码 */
			include_once('includes/cls_captcha.php');
		
			$validator = new captcha();
			if (!$validator->check_word($_POST['captcha']))
			{
				show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
			}
		}
		$other['areaid'] = $areaid;
		if($user_type==1)//代理增加生日
		{
			$birthday = $_POST['birthdayYear'] . '-' .  $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
			$other['birthday']   = $birthday;
		}
		//修改註冊方法。只插入用戶表，不修改任何session
		if (register_xj($username, $password, $email, $other) !== false)
		{
			 
			 
			if($user_type==1)//代理增加身份证号码和身份证照片
			{
				$sfz_number = $_POST['sfz_number'];
				$EnglishName = $_POST['EnglishName'];
				$ChineseName = $_POST['ChineseName'];
				//**上传图片测试功能
				$sfz_image_z   = $image->upload_image($_FILES['sfz_image_z'],'bonuslogo'); // 原始图片
				$sfz_image_b   = $image->upload_image($_FILES['sfz_image_b'],'bonuslogo'); // 原始图片
				//var_dump($original_img);
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `sfz_number`= '".$sfz_number."' , sfz_image_z='".$sfz_image_z.
				"',  sfz_image_b='".$sfz_image_b."',birthday='".$birthday."' ,EnglishName='".$EnglishName."' , ChineseName='".$ChineseName."'
        				WHERE `user_name`='" . $username . "'";
				$db->query($sql);
			}
			$sql =" SELECT user_id FROM ".$ecs->table('users')." WHERE `user_name`='" . $username . "'";
			$user_id_xj = $db->getOne($sql);
			 
			/*把新注册用户的扩展信息插入数据库*/
			$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
			$fields_arr = $db->getAll($sql);
		
			$extend_field_str = '';    //生成扩展字段的内容字符串
			foreach ($fields_arr AS $val)
			{
				$extend_field_index = 'extend_field' . $val['id'];
				if(!empty($_POST[$extend_field_index]))
				{
					$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
					$extend_field_str .= " ('" . $user_id_xj . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
				}
			}
			$extend_field_str = substr($extend_field_str, 0, -1);
		
			if ($extend_field_str)      //插入注册扩展数据
			{
				$sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
				$db->query($sql);
				
				
			}
			
			/* 写入店鋪ID */
			if (!empty($areaid))
			{
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `areaid`= $areaid ,`user_type`=$user_type  WHERE `user_id`='" . $user_id_xj . "'";
				$db->query($sql);
			}
		
						 
			$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = ".$_SESSION['user_id']." WHERE user_id = ".$user_id_xj;
			$db->query($sql);
					
			/* 写入密码提示问题和答案 */
			if (!empty($passwd_answer) && !empty($sel_question))
			{
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $user_id_xj . "'";
				$db->query($sql);
			}
		
			/* 判断是否需要自动发送注册邮件 */
			if ($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email'])
			{
				send_regiter_hash($user_id_xj);
			}
			$ucdata = empty($user->ucdata)? "" : $user->ucdata;
			show_message(sprintf($_LANG['register_success'], $username . $ucdata), array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act, 'user.php'), 'info');
		}
		else
		{
			$err->show($_LANG['sign_up'], 'user.php?act=register');
		}
	}
	
}
/* 注册会员的处理 */
elseif ($action == 'act_register')
{
	

	
    /* 增加是否关闭注册 */
    if ($_CFG['shop_reg_closed'])
    {
        $smarty->assign('action',     'register');
        $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
        $smarty->display('user_passport.dwt');
    }
    else
    {
        clear_cart();
        include_once(ROOT_PATH . 'includes/lib_passport.php');

        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
        $areaid   = isset($_POST['areaid']) ? intval($_POST['areaid']) : '';
        $user_type = isset($_POST['user_type']) ? intval($_POST['user_type']) : '0';
        $other['msn'] = isset($_POST['extend_field1']) ? $_POST['extend_field1'] : '';
        $other['qq'] = isset($_POST['extend_field2']) ? $_POST['extend_field2'] : '';
        $other['office_phone'] = isset($_POST['extend_field3']) ? $_POST['extend_field3'] : '';
        $other['home_phone'] = isset($_POST['extend_field4']) ? $_POST['extend_field4'] : '';
        $other['mobile_phone'] = isset($_POST['extend_field5']) ? $_POST['extend_field5'] : '';
        $sel_question = empty($_POST['sel_question']) ? '' : $_POST['sel_question'];
        $passwd_answer = isset($_POST['passwd_answer']) ? trim($_POST['passwd_answer']) : '';


        $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';

		$is_call = isset($_POST['is_call']) ? intval($_POST['is_call']) : 0;

        if(empty($_POST['agreement']))
        {
            show_message($_LANG['passport_js']['agreement']);
        }
        if(empty($_POST['extend_field5']))
        {
        	show_message('手機號不能為空');
        }
        if(empty($_POST['extend_field8']))
        {
        	//show_message('國家號不能為空');
        }

        $sql = "select count(*) from ".$GLOBALS['ecs']->table('users')." where user_name = '".$username."'";
        if($GLOBALS['db']->getOne($sql)){
            show_message('用户名重复');
        }
        
        if (strlen($username) < 3)
        {
            show_message($_LANG['passport_js']['username_shorter']);
        }

        if (strlen($password) < 6)
        {
            show_message($_LANG['passport_js']['password_shorter']);
        }

        if (strpos($password, ' ') > 0)
        {
            show_message($_LANG['passwd_balnk']);
        }

        /* 验证码检查 */
        if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
        {
            if (empty($_POST['captcha']))
            {
                show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
            }

            /* 检查验证码 */
            include_once('includes/cls_captcha.php');

            $validator = new captcha();
            if (!$validator->check_word($_POST['captcha']))
            {
                show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
            }
        }
        $other['areaid'] = $areaid;
        if($user_type==1)//代理增加生日
        {
        	$birthday = $_POST['birthdayYear'] . '-' .  $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
        	$other['birthday']   = $birthday;
        }
		if(empty($is_call)) {
			if (register($username, $password, $email, $other) !== false) {


				if ($user_type == 1)//代理增加身份证号码和身份证照片
				{
					$sfz_number = $_POST['sfz_number'];
					$EnglishName = $_POST['EnglishName'];
					$ChineseName = $_POST['ChineseName'];
					//**上传图片测试功能
					$sfz_image_z = $image->upload_image($_FILES['sfz_image_z'], 'bonuslogo'); // 原始图片
					$sfz_image_b = $image->upload_image($_FILES['sfz_image_b'], 'bonuslogo'); // 原始图片
					//var_dump($original_img);
					$sql = 'UPDATE ' . $ecs->table('users') . " SET `sfz_number`= '" . $sfz_number . "' , sfz_image_z='" . $sfz_image_z .
						"',  sfz_image_b='" . $sfz_image_b . "',birthday='" . $birthday . "' ,EnglishName='" . $EnglishName . "' , ChineseName='" . $ChineseName . "'
        				WHERE `user_id`='" . $_SESSION['user_id'] . "'";
					$db->query($sql);
				}


				/*把新注册用户的扩展信息插入数据库*/
				$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
				$fields_arr = $db->getAll($sql);

				$extend_field_str = '';    //生成扩展字段的内容字符串
				foreach ($fields_arr AS $val) {
					$extend_field_index = 'extend_field' . $val['id'];
					if (!empty($_POST[$extend_field_index])) {
						$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
						$extend_field_str .= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
					}
				}
				$extend_field_str = substr($extend_field_str, 0, -1);

				if ($extend_field_str)      //插入注册扩展数据
				{
					$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
					$db->query($sql);
				}

				/* 写入店鋪ID */
				if (!empty($areaid)) {
					$sql = 'UPDATE ' . $ecs->table('users') . " SET `areaid`= $areaid ,`user_type` =$user_type  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
					$db->query($sql);
				}
				//绑定推荐人
				if (!empty($_POST['parent_name'])) {
					$parent = $db->getRow("select user_id,parent_id,user_rank,rank_vip  from " . $ecs->table('users') . " where user_name = '" . $_POST['parent_name'] . "' or email = '" . $_POST['parent_name'] . "' or home_phone='" . $_POST['parent_name'] . "' or mobile_phone='" . $_POST['parent_name'] . "'");
					if (!empty($parent['user_id']) && !empty($_SESSION['user_id'])) {
						$wherestring = '';
						if ($parent['user_rank'] == 1 && $parent['rank_vip'] == 0) {
							$wherestring = " ,user_rank=1,rank_vip=1 ";
						}
						if (!empty($parent['parent_id'])) {

							if ($parent['parent_id'] != $_SESSION['user_id']) {

								$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $_SESSION['user_id'];
								$db->query($sql);
							}
						} else {

							$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $_SESSION['user_id'];
							$db->query($sql);
						}
					}
				}
				/* 写入密码提示问题和答案 */
				if (!empty($passwd_answer) && !empty($sel_question)) {
					$sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
					$db->query($sql);
				}

				/* 判断是否需要自动发送注册邮件 */
				if ($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email']) {
					send_regiter_hash($_SESSION['user_id']);
				}
				$ucdata = empty($user->ucdata) ? "" : $user->ucdata;
				show_message(sprintf($_LANG['register_success'], $username . $ucdata), array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act, 'user.php'), 'info');
			} else {
				$err->show($_LANG['sign_up'], 'user.php?act=register');
			}
		}else{
			if($_POST['tel_code'] == $_SESSION['mobile_code']) {

				$other['user_name'] = $username;
				$other['password'] = $password;
				$other['email'] = $email;
				if ($user_type == 1)//代理增加身份证号码和身份证照片
				{
					$other['sfz_number'] = $_POST['sfz_number'];
					$other['EnglishName'] = $_POST['EnglishName'];
					$other['ChineseName'] = $_POST['ChineseName'];
					//**上传图片测试功能
					$other['sfz_image_z'] = $image->upload_image($_FILES['sfz_image_z'], 'bonuslogo'); // 原始图片
					$other['sfz_image_b'] = $image->upload_image($_FILES['sfz_image_b'], 'bonuslogo'); // 原始图片
				}
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $other, 'UPDATE', "user_id = $is_call");

				/*把新注册用户的扩展信息插入数据库*/
				$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
				$fields_arr = $db->getAll($sql);

				$extend_field_str = '';    //生成扩展字段的内容字符串
				foreach ($fields_arr AS $val) {
					$extend_field_index = 'extend_field' . $val['id'];
					if (!empty($_POST[$extend_field_index])) {
						$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
						$extend_field_str .= " ('" . $is_call . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
					}
				}
				$extend_field_str = substr($extend_field_str, 0, -1);

				if ($extend_field_str)      //插入注册扩展数据
				{
					$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
					$db->query($sql);
				}
				//绑定推荐人
				if (!empty($_POST['parent_name'])) {
					$parent = $db->getRow("select user_id,parent_id,user_rank,rank_vip  from " . $ecs->table('users') . " where user_name = '" . $_POST['parent_name'] . "' or email = '" . $_POST['parent_name'] . "' or home_phone='" . $_POST['parent_name'] . "' or mobile_phone='" . $_POST['parent_name'] . "'");
					if (!empty($parent['user_id']) && !empty($is_call)) {
						$wherestring = '';
						if ($parent['user_rank'] == 1 && $parent['rank_vip'] == 0) {
							$wherestring = " ,user_rank=1,rank_vip=1 ";
						}
						if (!empty($parent['parent_id'])) {

							if ($parent['parent_id'] != $is_call) {

								$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $is_call;
								$db->query($sql);
							}
						} else {

							$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $is_call;
							$db->query($sql);
						}
					}
				}

				/* 写入密码提示问题和答案 */
				if (!empty($passwd_answer) && !empty($sel_question)) {
					$sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $is_call . "'";
					$db->query($sql);
				}

				$ucdata = empty($user->ucdata) ? "" : $user->ucdata;
				show_message(sprintf($_LANG['register_success'], $username . $ucdata), array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act, 'user.php'), 'info');
			}
			else{
				$err->show('验证码错误', 'user.php?act=register');
			}
		}
    }
}

/* 验证用户注册邮件 */
elseif ($action == 'validate_email')
{
    $hash = empty($_GET['hash']) ? '' : trim($_GET['hash']);
    if ($hash)
    {
        include_once(ROOT_PATH . 'includes/lib_passport.php');
        $id = register_hash('decode', $hash);
        if ($id > 0)
        {
            $sql = "UPDATE " . $ecs->table('users') . " SET is_validated = 1 WHERE user_id='$id'";
            $db->query($sql);
            $sql = 'SELECT user_name, email FROM ' . $ecs->table('users') . " WHERE user_id = '$id'";
            $row = $db->getRow($sql);
            show_message(sprintf($_LANG['validate_ok'], $row['user_name'], $row['email']),$_LANG['profile_lnk'], 'user.php');
        }
    }
    show_message($_LANG['validate_fail']);
}

/* 验证用户注册用户名是否可以注册 */
elseif ($action == 'is_registered')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    $username = trim($_GET['username']);
    $username = json_str_iconv($username);

    if ($user->check_user($username) || admin_registered($username))
    {
        echo 'false';
    }
    else
    {
        echo 'true';
    }
}

/* 验证用户邮箱地址是否被注册 */
elseif($action == 'check_email')
{
    $email = trim($_GET['email']);
    if ($user->check_email($email))
    {
        echo 'false';
    }
    else
    {
        echo 'ok';
    }
}

/* 验证用户注册电话是否可以注册 */
elseif($action == 'is_tel'){//改写这个方法，webpos线上填的客户资料有手机号码，没账户密码等信息。需要关联会
    include_once('includes/cls_json.php');
    $json  = new JSON;
    $result='';
    $tel=trim($_GET['tel']);
	$is_face = intval($_GET['is_face']);
    $sql = "select count(*) from ".$GLOBALS['ecs']->table('users')." where  home_phone ='".$tel."' or mobile_phone='".$tel."'";
    $re = $GLOBALS['db']->getOne($sql);
    if($re){
		$sql = "select user_id,aite_id,user_name from ".$GLOBALS['ecs']->table('users')." where  home_phone ='".$tel."' or mobile_phone='".$tel."'";
		$t=$GLOBALS['db']->getRow($sql);
		if(empty($is_face)) {
			if (empty($t['user_name'])) {
				$result = $t['user_id'];
			} else {
				$result = 1;
			}
		}
		else{
			if (empty($t['aite_id'])) {
				$result = $t['user_id'];
			} else {
				$result = 1;
			}
		}
    }else{
        $result = 0;
    }

    die($json->encode($result));
}
elseif($action == 'is_tel_new'){//改写这个方法，webpos线上填的客户资料有手机号码，没账户密码等信息。需要关联会
	include_once('includes/cls_json.php');
	$json  = new JSON;
	$result=array();
	$tel=trim($_GET['tel']);
	$is_face = intval($_GET['is_face']);
	$sql = "select count(*) from ".$GLOBALS['ecs']->table('users')." where  home_phone ='".$tel."' or mobile_phone='".$tel."'";
	$re = $GLOBALS['db']->getOne($sql);
	if($re){
		$sql = "select user_id,aite_id,user_name from ".$GLOBALS['ecs']->table('users')." where  home_phone ='".$tel."' or mobile_phone='".$tel."'";
		$t=$GLOBALS['db']->getRow($sql);
		if(empty($is_face)) {
			if (empty($t['user_name'])) {
				$result['content'] = $t['user_id'];
				$result['type'] = 2;
			} else {
				$result['content'] = 0;
				$result['type'] = 1;
			}
		}
		else{
			if (empty($t['aite_id'])) {
				$result['content'] = $t['user_id'];
				$result['type'] = 3;
			} else {
				$result['content'] = 0;
				$result['type'] = 1;
			}
		}
	}else{
		$result['content'] = 0;
		$result['type'] = 0;
	}

	die($json->encode($result));
}
elseif ($action == 'logindl')
{
	if (empty($back_act))
	{
		if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
		{
			$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
		}
		else
		{
			$back_act = 'user.php';
		}
	
	}
	
	$captcha = intval($_CFG['captcha']);
	if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
	{
		$GLOBALS['smarty']->assign('enabled_captcha', 1);
		$GLOBALS['smarty']->assign('rand', mt_rand());
	}
	
	$smarty->assign('back_act', $back_act);
	$smarty->display('user_logindl.dwt');
}
/* 用户登录界面 */
elseif ($action == 'login')
{
    if (empty($back_act))
    {
        if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
        {
            $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
        }
        else
        {
            $back_act = 'user.php';
        }

    }
$smarty->assign('facebookurl', login_facebook());
    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }

    $smarty->assign('back_act', $back_act);
    $smarty->display('user_login.dwt');
}
//  第三方登录接口facebook
elseif($action == 'fboath')
{
    $rate = $_SESSION['area_rate_id'];
    $send_code = $_SESSION['send_code'];
    $type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];

    if($type == "taobao"){
        header("location:includes/fbwebsite/tb_index.php");exit;
    }

    include_once(ROOT_PATH . 'includes/fbwebsite/jntoo.php');

    $c = &fbwebsite($type);
    $_SESSION['send_code'] = $send_code;
    if($c)
    {
        if (empty($_REQUEST['callblock']))
        {
            if (empty($_REQUEST['callblock']) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
            {
                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php?') ? 'index.php?' : $GLOBALS['_SERVER']['HTTP_REFERER'];
            }
            else
            {
                $back_act = 'index.php?';
            }
        }
        else
        {
            $back_act = trim($_REQUEST['callblock']);
        }
        $back_act .='&rate_id='.$rate;
        if($back_act[4] != ':') $back_act = $ecs->url().$back_act;
        $open = empty($_REQUEST['open']) ? 0 : intval($_REQUEST['open']);

        //facebook
        if($type == 'facebook')
        {
            /*
            callblock='.urlencode($back_act)   这个网址地址参数  在有些服务器设置出现错误
            修改：删除这个参数，ecs_header('Location: '.$_REQUEST['callblock']);  改为ecs_header('Location: ./index.php');

            2013-10-30  bysam  更新
            */
            $params = array(
                'scope' => 'email, read_stream, friends_likes',
                'redirect_uri' =>'http://www.icmarts.com/'.'user.php?act=fboath_login&type='.$type.'&callblock='.urlencode($back_act).'&open='.$open
            );
            $url = $c->getLoginUrl($params);
        }
        //other
        else{
            $url = $c->login($ecs->url().'user.php?act=fboath_login&type='.$type.'&callblock='.urlencode($back_act).'&open='.$open);
            if(!$url)
            {
                show_message( $c->get_error() , '首頁', $ecs->url() , 'error');
            }
        }

        header('Location: '.$url);
    }
    else
    {
        show_message('伺服器尚未註冊該插件！' , '首頁',$ecs->url() , 'error');
    }
}
//处理第三方登录接口facebook
elseif($action == 'fboath_login')
{
   
    $type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];

    include_once(ROOT_PATH . 'includes/fbwebsite/jntoo.php');
    //bysam test
    if(isset($c)){
        unset($c);
    }
    //bysam test
    $c = &fbwebsite($type);

    if($c || $type == 'js_facebook')
    {
        if($type == 'facebook')
        {
			
			include_once('./phpsdk5/autoload.php');
			


			$fb = new Facebook\Facebook([
			  'app_id' => '1397762930546864', // Replace {app-id} with your app id
			  'app_secret' => '01fddb435e8190d6ead0ba4939340ea2',
			  'default_graph_version' => 'v2.9',
			  ]);

			$helper = $fb->getRedirectLoginHelper();
			$_SESSION['FBRLH_state']=$_GET['state'];
			
			try {
  $accessToken = $helper->getAccessToken();
  
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
 echo 'Facebook SDK returned an error: ' . $e->getMessage();
}
 
if (isset($accessToken)) {
  // Logged in!
  //var_dump($accessToken->getValue());
  $_SESSION['facebook_access_token'] = (string) $accessToken;
  echo $_SESSION['facebook_access_token'];
  
} elseif ($helper->getError()) {
  // The user denied the request
}

try {
  // Returns a `Facebook\FacebookResponse` object
  $response = $fb->get('/me?fields=id,name,email,first_name,last_name,gender', $_SESSION['facebook_access_token']);
 
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

			
			
            // Get User ID
            $user_id = $c->getUser();


            if ($response)
            {
                try
                {
                    // Proceed knowing you have a logged in user who's authenticated.
$user_profile = $response->getGraphUser();

                    $user_profile = $user_profile;
					
                    //----
                    $info['name'] = $user_profile["first_name"].$user_profile["last_name"];
                    $info['sex'] = $user_profile['gender'] == 'male' ? '1' : '0';
                    $info['email'] = $user_profile["email"];
                    $info['user_id'] = $user_profile['id'];
                    $info['rank_id'] = RANK_ID;

                    $info_user_id = $type .'_'.$info['user_id']; //  加个标识！！！防止 其他的标识 一样

                    //---
                }
                catch(FacebookApiException $e){
                    error_log($e);
                    $user_id = null;
                }
            }
            else{
				$_SESSION['area_rate_id'] = 4;
				ecs_header('Location: user.php?act=login');
                //show_message( "Facebook 登錄失敗" , '首頁', $ecs->url() , 'error');
            }
        }
		elseif($type == 'js_facebook'){
			//var_dump($_REQUEST);die();
			if(!empty($_REQUEST['userid'])){
				$email='';
				if(!empty($_REQUEST['email']) && $_REQUEST['email'] !='undefined'){
					$email = $_REQUEST['email'];
				}
				$info['name'] = $_REQUEST['name'];
				$info['email'] = $email;
				$info['user_id'] = $_REQUEST['userid'];
				$info['rank_id'] = 10;

				$info_user_id = 'facebook_'.$info['user_id']; //  加个标识！！！防止 其他的标识 一样
			}
		}
        else{

            $access = $c->getAccessToken();
            if(!$access)
            {
                show_message( $c->get_error() , '首頁', $ecs->url() , 'error');
            }
            $c->setAccessToken($access);
            $info = $c->getMessage();
            if(!$info)
            {
                show_message($c->get_error() , '首頁' , $ecs->url() , 'error' , false);
            }
            if(!$info['user_id'])
                show_message($c->get_error() , '首頁' , $ecs->url() , 'error' , false);


            $info_user_id = $type .'_'.$info['user_id']; //  加个标识！！！防止 其他的标识 一样  // 以后的ID 标识 将以这种形式 辨认
            $info['name'] = str_replace("'" , "" , $info['name']); // 过滤掉 逗号 不然出错  很难处理   不想去  搞什么编码的了
            if(!$info['user_id'])
                show_message($c->get_error() , '首頁' , $ecs->url() , 'error' , false);
        }


        $sql = 'SELECT user_id, user_name,password,aite_id,userNo,areaid FROM '.$ecs->table('users').' WHERE aite_id = \''.$info_user_id.'\' OR aite_id=\''.$info['user_id'].'\'';

        $count = $db->getRow($sql);

	
        if(empty($count))   // 没有当前数据
        {
            if($user->check_user($info['name']))  // 重名处理
            {
                $info['name'] = $info['name'].'_'.$type.(rand(10000,99999));
            }
            $user_pass = $user->compile_password(array('password'=>$info['user_id'].(rand(10000,99999))));
            //$sql = 'INSERT INTO '.$ecs->table('users').'(user_name , password, aite_id , email, sex , reg_time , user_rank , is_validated, areaid) VALUES '.
             //   "('".$info['name']."' , '$user_pass' , '$info_user_id' , '".$info['email']."', '".$info['sex']."' , '".gmtime()."' , '".$info['rank_id']."' , '1', '3')" ;
            //$db->query($sql);
            $info['pass'] = $user_pass;
            $info['aite_id'] = $info_user_id;

			if ($user->check_email($info['email']))
			{
				$info['user_email'] = '';
			}
			else
			{
				$info['user_email'] = $info['email'];
			}

            $_SESSION['facebook_user'] = 1;
            $_SESSION['info'] = $info;
            if(empty($info['email'])){
                $_SESSION['facebook_email'] = 1;
            }
            $_SESSION['area_rate_id_user'] = $_REQUEST['rate_user'];
            //$user->set_session($info);
        }
        else
        {
            unset($_SESSION['facebook_user']);
            if($count['aite_id'] == $info['user_id'])
            {
                $sql = 'UPDATE '.$ecs->table('users')." SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
                $db->query($sql);
            }
            if(empty($count['userNo'])){
                $sql = "SELECT shopNo FROM ".$ecs->table('area')." WHERE areaid=".$count['areaid'];
                $shopNo = $db->getOne($sql);

                $sql = 'SELECT `GetUserKey`() AS `GetUserKey` ' ;
                $aid = $db->getOne($sql);
                $aid = str_repeat('0', 10 - strlen($aid)).$aid;
                $shopNo = $shopNo.$aid;

                $sql = "update ".$GLOBALS['ecs']->table("users")." set userNo ='".$shopNo."' where user_id = ".$count['user_id'];
                $GLOBALS['db']->query($sql);
            }
            if($info['name'] != $count['user_name'])   // 这段可删除
            {
                if($user->check_user($info['name']))  // 重名处理
                {
                    $info['name'] = $info['name'].'_'.$type.(rand()*1000);
                }
                /*$sql = 'UPDATE '.$ecs->table('users')." SET user_name = '$info[name]' WHERE aite_id = '$info_user_id'";
                $db->query($sql);*/
            }
            $user->set_session($count['user_name']);
        }
        $user->set_cookie($info['name']);
        update_user_info();
        recalculate_price();

        if(!empty($count))
        {
            ecs_header("Location: index.php\n");
        }
        else
        {
			//ecs_header("Location: index.php\n");
            ecs_header("Location: user.php?act=register");

        }

    }

}

//facebook绑定账号
elseif($action == 'face_user'){
	clear_cart();
	include_once(ROOT_PATH . 'includes/lib_passport.php');

	$username = isset($_POST['username']) ? trim($_POST['username']) : '';
	$password = isset($_POST['password']) ? trim($_POST['password']) : '';
	$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
	$areaid   = isset($_POST['areaid']) ? intval($_POST['areaid']) : '';
	$user_type = isset($_POST['user_type']) ? intval($_POST['user_type']) : '0';
	$other['msn'] = isset($_POST['extend_field1']) ? $_POST['extend_field1'] : '';
	$other['qq'] = isset($_POST['extend_field2']) ? $_POST['extend_field2'] : '';
	$other['office_phone'] = isset($_POST['extend_field3']) ? $_POST['extend_field3'] : '';
	$other['home_phone'] = isset($_POST['extend_field4']) ? $_POST['extend_field4'] : '';
	$other['mobile_phone'] = isset($_POST['extend_field5']) ? $_POST['extend_field5'] : '';
	$sel_question = empty($_POST['sel_question']) ? '' : $_POST['sel_question'];
	$passwd_answer = isset($_POST['passwd_answer']) ? trim($_POST['passwd_answer']) : '';


	$back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';
	$aite_id = isset($_POST['aite_id']) ? trim($_POST['aite_id']) : '';
	$is_call = isset($_POST['is_call']) ? intval($_POST['is_call']) : 0;

	if(empty($_POST['agreement']))
	{
		show_message($_LANG['passport_js']['agreement']);
	}
	if(empty($_POST['extend_field5']))
	{
		show_message('手機號不能為空');
	}
	if(empty($_POST['extend_field8']))
	{
		//show_message('國家號不能為空');
	}

	$sql = "select count(*) from ".$GLOBALS['ecs']->table('users')." where user_name = '".$username."'";
	if($GLOBALS['db']->getOne($sql)){
		show_message('用户名重复');
	}

	if (strlen($username) < 3)
	{
		show_message($_LANG['passport_js']['username_shorter']);
	}

	if (strlen($password) < 6)
	{
		show_message($_LANG['passport_js']['password_shorter']);
	}

	if (strpos($password, ' ') > 0)
	{
		show_message($_LANG['passwd_balnk']);
	}

	/* 验证码检查 */
	if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
	{
		if (empty($_POST['captcha']))
		{
			show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
		}

		/* 检查验证码 */
		include_once('includes/cls_captcha.php');

		$validator = new captcha();
		if (!$validator->check_word($_POST['captcha']))
		{
			show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
		}
	}

	if($user_type==1)//代理增加生日
	{
		$birthday = $_POST['birthdayYear'] . '-' .  $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
		$other['birthday']   = $birthday;
	}
	if(empty($is_call)) {
		$other['areaid'] = $areaid;
		if (register($username, $password, $email, $other) !== false) {


			if ($user_type == 1)//代理增加身份证号码和身份证照片
			{
				$sfz_number = $_POST['sfz_number'];
				$EnglishName = $_POST['EnglishName'];
				$ChineseName = $_POST['ChineseName'];
				//**上传图片测试功能
				$sfz_image_z = $image->upload_image($_FILES['sfz_image_z'], 'bonuslogo'); // 原始图片
				$sfz_image_b = $image->upload_image($_FILES['sfz_image_b'], 'bonuslogo'); // 原始图片
				//var_dump($original_img);
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `sfz_number`= '" . $sfz_number . "' , sfz_image_z='" . $sfz_image_z .
					"',  sfz_image_b='" . $sfz_image_b . "',birthday='" . $birthday . "' ,EnglishName='" . $EnglishName . "' , ChineseName='" . $ChineseName . "'
        				WHERE `user_id`='" . $_SESSION['user_id'] . "'";
				$db->query($sql);
			}


			/*把新注册用户的扩展信息插入数据库*/
			$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
			$fields_arr = $db->getAll($sql);

			$extend_field_str = '';    //生成扩展字段的内容字符串
			foreach ($fields_arr AS $val) {
				$extend_field_index = 'extend_field' . $val['id'];
				if (!empty($_POST[$extend_field_index])) {
					$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
					$extend_field_str .= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
				}
			}
			$extend_field_str = substr($extend_field_str, 0, -1);

			if ($extend_field_str)      //插入注册扩展数据
			{
				$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
				$db->query($sql);
			}

			/* 写入店鋪ID */
			if (!empty($areaid)) {
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `areaid`= $areaid ,`user_type` =$user_type  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
				$db->query($sql);
			}
			if (!empty($aite_id)) {
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `aite_id`= '".$aite_id."'   WHERE `user_id`='" . $_SESSION['user_id'] . "'";
				$db->query($sql);
			}
			//绑定推荐人
			if (!empty($_POST['parent_name'])) {
				$parent = $db->getRow("select user_id,parent_id,user_rank,rank_vip  from " . $ecs->table('users') . " where user_name = '" . $_POST['parent_name'] . "' or email = '" . $_POST['parent_name'] . "' or home_phone='" . $_POST['parent_name'] . "' or mobile_phone='" . $_POST['parent_name'] . "'");
				if (!empty($parent['user_id']) && !empty($_SESSION['user_id'])) {
					$wherestring = '';
					if ($parent['user_rank'] == 1 && $parent['rank_vip'] == 0) {
						$wherestring = " ,user_rank=1,rank_vip=1 ";
					}
					if (!empty($parent['parent_id'])) {

						if ($parent['parent_id'] != $_SESSION['user_id']) {

							$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $_SESSION['user_id'];
							$db->query($sql);
						}
					} else {

						$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $_SESSION['user_id'];
						$db->query($sql);
					}
				}
			}
			/* 写入密码提示问题和答案 */
			if (!empty($passwd_answer) && !empty($sel_question)) {
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
				$db->query($sql);
			}

			/* 判断是否需要自动发送注册邮件 */
			if ($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email']) {
				send_regiter_hash($_SESSION['user_id']);
			}
			unset($_SESSION['facebook_user']);
			unset($_SESSION['facebook_email']);
			$ucdata = empty($user->ucdata) ? "" : $user->ucdata;
			show_message(sprintf($_LANG['register_success'], $username . $ucdata), array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act, 'user.php'), 'info');
		} else {
			$err->show($_LANG['sign_up'], 'user.php?act=register');
		}
	}else{
		if($_POST['tel_code'] == $_SESSION['mobile_code']) {

			$sql = "select * from ".$GLOBALS['ecs']->table('users')." where user_id=".$is_call;
			$uinfo = $GLOBALS['db']->getRow($sql);

			if(empty($uinfo['email'])){
				$other['email'] = $email;
			}
			if(empty($uinfo['user_name'])){
				$other['user_name'] = $username;
			}else{
				$username = $uinfo['user_name'];
			}

			$other['password'] = $password;
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $other, 'UPDATE', "user_id = $is_call");

			/*把新注册用户的扩展信息插入数据库*/
			$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
			$fields_arr = $db->getAll($sql);

			$extend_field_str = '';    //生成扩展字段的内容字符串
			foreach ($fields_arr AS $val) {
				$extend_field_index = 'extend_field' . $val['id'];
				if (!empty($_POST[$extend_field_index])) {
					$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
					$extend_field_str .= " ('" . $is_call . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
				}
			}
			$extend_field_str = substr($extend_field_str, 0, -1);

			if ($extend_field_str)      //插入注册扩展数据
			{
				$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
				$db->query($sql);
			}
			//绑定推荐人
			if (!empty($_POST['parent_name'])) {
				$parent = $db->getRow("select user_id,parent_id,user_rank,rank_vip  from " . $ecs->table('users') . " where user_name = '" . $_POST['parent_name'] . "' or email = '" . $_POST['parent_name'] . "' or home_phone='" . $_POST['parent_name'] . "' or mobile_phone='" . $_POST['parent_name'] . "'");
				if (!empty($parent['user_id']) && !empty($is_call)) {
					$wherestring = '';
					if ($parent['user_rank'] == 1 && $parent['rank_vip'] == 0) {
						$wherestring = " ,user_rank=1,rank_vip=1 ";
					}
					if (!empty($parent['parent_id'])) {

						if ($parent['parent_id'] != $is_call) {

							$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $is_call;
							$db->query($sql);
						}
					} else {

						$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = " . $parent['user_id'] . $wherestring . " WHERE user_id = " . $is_call;
						$db->query($sql);
					}
				}
			}

			if (!empty($aite_id)) {
				$sql = 'UPDATE ' . $ecs->table('users') . " SET `aite_id`= '".$aite_id."'   WHERE `user_id`='" . $is_call . "'";
				$db->query($sql);
			}

			$user->set_session($username);
			$user->set_cookie($username);

			unset($_SESSION['facebook_user']);
			unset($_SESSION['facebook_email']);
			unset($_SESSION['mobile_code']);

			$ucdata = empty($user->ucdata) ? "" : $user->ucdata;
			show_message($username.'綁定FACEBOOK成功', $_LANG['profile_lnk'],  'user.php');
		}
		else{
			$err->show($_LANG['sign_up'], 'user.php?act=register');
		}
	}
}

//facebook绑定账号
elseif($action == 'face_user_old'){
    $info_user = $_SESSION['info'];
    $tel_code = intval($_POST['mobile_code1']);
    $tel_co = $_POST['code1'];
    $tel = $_POST['mobile1'];
    $area_id = intval($_POST['rate']);
    $user_i = intval($_POST['user_i']);
    if(!empty($_SESSION['facebook_email'])){
        if(empty($_POST['email'])){
            echo ' <script language="javascript"> alert("請輸入郵箱"); window.history.go(-1);</script>';
            exit;
        }else{
            $email = $_POST['email'];
        }
    }else{
        $email = $info_user['email'];
    }

    //var_dump($tel_code,$tel_co,$tel);var_dump($_SESSION);die();
    if(!empty($info_user)){
        if($user_i > 0){
            if($tel_code == $_SESSION['mobile_code'] &&  $tel_co.$tel == $_SESSION['mobile']){
                unset($_SESSION['mobile']);
                unset($_SESSION['mobile_code']);
            }else{
                echo ' <script language="javascript"> alert("手机验证码输入错误。"); window.history.go(-1);</script>';
                exit;
            }
            $sql = "update ".$GLOBALS['ecs']->table("users")." set aite_id = '".$info_user['aite_id']."' where user_id =".$user_i;
            $GLOBALS['db']->query($sql);
            $_SESSION['user_id']=$user_i;
        }else{
            $sql="select count(*) from ".$ecs->table('users')." where aite_id = '".$info_user['aite_id']."'";
            $re_num = $GLOBALS['db']->getOne($sql);
            if(empty($re_num)) {
                $sql = "SELECT shopNo FROM " . $GLOBALS['ecs']->table('area') . " WHERE areaid=" . $area_id;
                $shopNo = $GLOBALS['db']->getOne($sql);

                $sql = 'SELECT `GetUserKey`() AS `GetUserKey` ';
                $aid = $GLOBALS['db']->getOne($sql);
                $aid = str_repeat('0', 10 - strlen($aid)) . $aid;
                $shopNo = $shopNo . $aid;

                $sql = 'INSERT INTO ' . $ecs->table('users') . '(user_name , password, aite_id , email, sex , reg_time , user_rank ,mobile_phone, is_validated, areaid,userNo) VALUES ' .
                    "('" . $info_user['name'] . "' , '" . $info_user['pass'] . "' , '" . $info_user['aite_id'] . "' , '" . $email . "', '" . $info_user['sex'] . "' , '" . gmtime() . "' , '" . $info_user['rank_id'] . "', '$tel' , '1', '$area_id','$shopNo')";
                $GLOBALS['db']->query($sql);
                $_SESSION['user_id'] = $db->insert_id();

                $sql = "insert into " . $ecs->table('reg_extend_info') . " (user_id,reg_field_id,content) value ( " . $_SESSION['user_id'] . ", 8, '" . $tel_co . "')";
                $GLOBALS['db']->query($sql);
            }
        }
        unset($_SESSION['facebook_user']);
        unset($_SESSION['facebook_email']);
        update_user_info();
        recalculate_price();

        show_message('賬戶綁定成功', array('首頁', '用戶中心'), array('index.php','user.php'), 'info');
    }else{
        ecs_header('Location: index.php');
    }
}

elseif($action == 'get_user_vip'){

    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json   = new JSON;

    $mobile = $_GET['mobile'];

    $sql = "select user_id from ".$GLOBALS['ecs']->table("users")." where mobile_phone = '".$mobile."'";
    $us = $GLOBALS['db']->getRow($sql);

    if(!empty($us['user_id'])){
        $res = $us['user_id'];
    }else{
        $res = 0;
    }

    die($json->encode($res));
}

//  第三方登录接口
elseif($action == 'oath')
{
    $type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];

    if($type == "taobao"){
        header("location:includes/website/tb_index.php");exit;
    }

    include_once(ROOT_PATH . 'includes/website/jntoo.php');

    $c = &website($type);
    if($c)
    {
        if (empty($_REQUEST['callblock']))
        {
            if (empty($_REQUEST['callblock']) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
            {
                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? 'index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
            }
            else
            {
                $back_act = 'index.php';
            }
        }
        else
        {
            $back_act = trim($_REQUEST['callblock']);
        }

        if($back_act[4] != ':') $back_act = $ecs->url().$back_act;
        $open = empty($_REQUEST['open']) ? 0 : intval($_REQUEST['open']);

        $url = $c->login($ecs->url().'user.php?act=oath_login&type='.$type.'&callblock='.urlencode($back_act).'&open='.$open);
        if(!$url)
        {
            show_message( $c->get_error() , '首页', $ecs->url() , 'error');
        }
        header('Location: '.$url);
    }
    else
    {
        show_message('服务器尚未注册该插件！' , '首页',$ecs->url() , 'error');
    }
}
//  处理第三方登录接口
elseif($action == 'oath_login')
{
    $type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];

    include_once(ROOT_PATH . 'includes/website/jntoo.php');
    $c = &website($type);
    if($c)
    {
        $access = $c->getAccessToken();
        if(!$access)
        {
            show_message( $c->get_error() , '首页', $ecs->url() , 'error');
        }
        $c->setAccessToken($access);
        $info = $c->getMessage();
        if(!$info)
        {
            show_message($c->get_error() , '首页' , $ecs->url() , 'error' , false);
        }
        if(!$info['user_id'])
            show_message($c->get_error() , '首页' , $ecs->url() , 'error' , false);


        $info_user_id = $type .'_'.$info['user_id']; //  加个标识！！！防止 其他的标识 一样  // 以后的ID 标识 将以这种形式 辨认
        $info['name'] = str_replace("'" , "" , $info['name']); // 过滤掉 逗号 不然出错  很难处理   不想去  搞什么编码的了
        if(!$info['user_id'])
            show_message($c->get_error() , '首页' , $ecs->url() , 'error' , false);


        $sql = 'SELECT user_name,password,aite_id FROM '.$ecs->table('users').' WHERE aite_id = \''.$info_user_id.'\' OR aite_id=\''.$info['user_id'].'\'';

        $count = $db->getRow($sql);
        if(!$count)   // 没有当前数据
        {
            if($user->check_user($info['name']))  // 重名处理
            {
                $info['name'] = $info['name'].'_'.$type.(rand(10000,99999));
            }
            $user_pass = $user->compile_password(array('password'=>$info['user_id']));
            $sql = 'INSERT INTO '.$ecs->table('users').'(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES '.
                "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '".gmtime()."' , '$info[rank_id]' , '1')" ;
            $db->query($sql);
        }
        else
        {
            $sql = '';
            if($count['aite_id'] == $info['user_id'])
            {
                $sql = 'UPDATE '.$ecs->table('users')." SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
                $db->query($sql);
            }
            if($info['name'] != $count['user_name'])   // 这段可删除
            {
                if($user->check_user($info['name']))  // 重名处理
                {
                    $info['name'] = $info['name'].'_'.$type.(rand()*1000);
                }
                $sql = 'UPDATE '.$ecs->table('users')." SET user_name = '$info[name]' WHERE aite_id = '$info_user_id'";
                $db->query($sql);
            }
        }
        $user->set_session($info['name']);
        $user->set_cookie($info['name']);
        update_user_info();
        recalculate_price();

        if(!empty($_REQUEST['open']))
        {
            die('<script>window.opener.window.location.reload(); window.close();</script>');
        }
        else
        {
            ecs_header('Location: '.$_REQUEST['callblock']);

        }

    }

}
//  处理其它登录接口
elseif($action == 'other_login')
{
    $type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];
    session_start();
    $info = $_SESSION['user_info'];

    if(empty($info)){
        show_message("非法访问或请求超时！" , '首页' , $ecs->url() , 'error' , false);
    }
    if(!$info['user_id'])
        show_message("非法访问或访问出错，请联系管理员！", '首页' , $ecs->url() , 'error' , false);


    $info_user_id = $type .'_'.$info['user_id']; //  加个标识！！！防止 其他的标识 一样  // 以后的ID 标识 将以这种形式 辨认
    $info['name'] = str_replace("'" , "" , $info['name']); // 过滤掉 逗号 不然出错  很难处理   不想去  搞什么编码的了


    $sql = 'SELECT user_name,password,aite_id FROM '.$ecs->table('users').' WHERE aite_id = \''.$info_user_id.'\' OR aite_id=\''.$info['user_id'].'\'';

    $count = $db->getRow($sql);
    $login_name = $info['name'];
    if(!$count)   // 没有当前数据
    {
        if($user->check_user($info['name']))  // 重名处理
        {
            $info['name'] = $info['name'].'_'.$type.(rand()*1000);
        }
        $login_name = $info['name'];
        $user_pass = $user->compile_password(array('password'=>$info['user_id']));
        $sql = 'INSERT INTO '.$ecs->table('users').'(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES '.
            "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '".gmtime()."' , '$info[rank_id]' , '1')" ;
        $db->query($sql);
    }
    else
    {
        $login_name = $count['user_name'];
        $sql = '';
        if($count['aite_id'] == $info['user_id'])
        {
            $sql = 'UPDATE '.$ecs->table('users')." SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
            $db->query($sql);
        }
    }



    $user->set_session($login_name);
    $user->set_cookie($login_name);
    update_user_info();
    recalculate_price();

    $redirect_url =  "http://".$_SERVER["HTTP_HOST"].str_replace("user.php", "index.php", $_SERVER["REQUEST_URI"]);
    header('Location: '.$redirect_url);

}

elseif($action == 'refund_list'){

    $sql="select og.*, o.order_sn, o.shop_pay_yun, o.user_pay_account, o.user_pay_account_ture from ".$GLOBALS['ecs']->table("order_goods")." as og, ".$GLOBALS['ecs']->table("order_info").
        " as o where og.order_id=o.order_id and o.user_id =".$_SESSION['user_id']." and og.refund_status <>0 order by og.refund_add_time desc";

    $list = $GLOBALS['db']->getAll($sql);

    foreach($list as $k=>$v){
        $list[$k]['refund_reason'] = $GLOBALS['db']->getOne("select storereturns_name from ".$GLOBALS['ecs']->table("storereturns")." where storereturns_id = ".$v['refund_reason']);
        if($v['user_pay_account']){
            if($v['user_pay_account_ture']){
                $list[$k]['user_maney']=0;
            }else{
                $list[$k]['user_maney']=$v['user_pay_account'];
            }
        }else{
            $list[$k]['user_maney']=0;
        }
    }
    $smarty->assign('helps',      get_shop_help());
    $smarty->assign('list', $list);
    $smarty->assign('action', 'refund_list');
    $smarty->display("user_transaction.dwt");
}

elseif($action == 'get_re_goods' ){
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json   = new JSON;
    $keyword = $_GET['name'];
    $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
        " WHERE goods_name LIKE '%" . mysql_like_quote($keyword) . "%' LIMIT 50";
    $arr = $db->getAll($sql);
    $str='<option value="">請選擇商品</option>';
    foreach($arr as $value){
        $str.="<option value='".$value['id']."'>".$value['name']."</option>";
    }
    die($json->encode($str));
}
elseif($action == 'get_goods_cat' ){
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json   = new JSON;
    $keyword = $_GET['name'];
    $sql="select * from ".$GLOBALS['ecs']->table("products")." where goods_id = ".$keyword." and areaid = 0";//." and product_number > 0";
    $re_cat = $GLOBALS['db']->getAll($sql);
    foreach( $re_cat as $key=>$vre){
        if(empty($vre['goods_attr'])){
            $re_cat = array();
        }else{
            $re_cat[$key]['goods_attr'] = str_replace('|',',',$vre['goods_attr']);
            $re_cat[$key]['goods_attr_value'] = get_product_value($vre['goods_attr']);
        }
    }
    $str='';
    foreach($re_cat as $value){
        $str.="<option value='".$value['goods_attr']."'>".$value['goods_attr_value']."</option>";
    }
    die($json->encode($str));
}

elseif($action == 'refund_pay'){
    $order_id = intval($_GET['order_id']);
    //用戶賬戶支付
    $sql="select order_sn, user_pay_account,user_pay_account_ture from ".$GLOBALS['ecs']->table("order_info")." where order_id = $order_id";
    $res = $GLOBALS['db']->getRow($sql);
    $order_sn = $res['order_sn'];
    if($res['user_pay_account_ture'] == 0){

        $sql="select user_money from ".$GLOBALS['ecs']->table('users')." where user_id = ".$_SESSION['user_id'];
        $money = $GLOBALS['db']->getOne($sql);
        if(floatval($money) < floatval($res['user_pay_account'])){
            show_message("餘額不足，請充值再支付", "售後服務", "user.php?act=refund_list");
        }else{
            $change_desc = "订单{$order_sn}商品退換運費支付";
            $refund_money = "-".floatval($res['user_pay_account']);
            log_account_change($_SESSION['user_id'], $refund_money, 0, 0, 0, $change_desc, ACT_OTHER);
            $GLOBALS['db']->query("update ".$GLOBALS['ecs']->table("order_info")." set user_pay_account_ture = 1  where order_id=$order_id");
            show_message("付款成功", "售後服務", "user.php?act=refund_list");
        }
    }
}

elseif($action == 'refund_goods'){
    $order_sn = trim($_REQUEST['order_sn']);
    $smarty->assign('order_sn', $order_sn);
    $sql="select order_id from ".$GLOBALS['ecs']->table("order_info")." where order_sn = '".$order_sn."' and user_id = ".$_SESSION['user_id'];
    $order_id = $GLOBALS['db']->getOne($sql);
    if($order_id){
        $order = order_info($order_id);
        $goods_list = order_goods($order_id);
        $order['shipping_status_t'] = ($order['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $order['shipping_status'];
        $order['order_status_v'] = $GLOBALS['_LANG']['os'][$order['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$order['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$order['shipping_status_t']];
        if($order['shipping_status'] != 2){
            foreach($goods_list as $k=>$v){
                $goods_list[$k]['refund_status'] = '-1';
            }
        }else{
            if(intval($order['confirm_time'])+3*86400 < gmtime() ){
                foreach($goods_list as $k=>$v){
                    $goods_list[$k]['refund_status'] = '-2';

                }
            }

        }
        $smarty->assign('order', $order);
        $smarty->assign('goods_list', $goods_list);

    }
    else{
        //該用戶沒有該訂單
    }
    $smarty->assign('action', 'refund_goods');
    $smarty->display("user_transaction.dwt");
}

//mod by coolvee.com 酷唯软件出品
elseif($action == 'refund')
{
    $rec_id = $_REQUEST['rec_id'];
    $goods = get_order_goods_info($rec_id);
    if($goods['refund_status']>0)
    {
        die("invalid");
    }
    if(!can_refund($goods['order_id']) )
    {
        die("invalid");
    }

    //$refund_reason_arr = array("无理由退货", "质量问题", "与描述不符");
    $sql="select * from ".$GLOBALS['ecs']->table("storereturns");
    $refund_reason_arr = $GLOBALS['db']->getAll($sql);
    $options = array();
    foreach($refund_reason_arr as $v)
    {
        $options[$v['storereturns_id']] = $v['storereturns_name'];
    }

    $sql="select * from ".$GLOBALS['ecs']->table("products")." where goods_id = ".$goods['goods_id']." and areaid = ".$goods['areaid'];//." and product_number > 0";
    $re_cat = $GLOBALS['db']->getAll($sql);

    $cat = str_replace(',','|',$goods['goods_attr_id']);
    foreach( $re_cat as $key=>$vre){
        if(empty($vre['goods_attr'])){
            $re_cat = array();
        }else{
            $re_cat[$key]['goods_attr'] = str_replace('|',',',$vre['goods_attr']);
            $re_cat[$key]['goods_attr_value'] = get_product_value($vre['goods_attr']);
        }
    }

    $smarty->assign('refund_cat', $re_cat);
    $smarty->assign('cat', $cat);
    $smarty->assign('refund_reason_options', $options );
    $smarty->assign('refund_goods', $goods);
    $smarty->assign('order_sn', $goods['order_sn']);
    $smarty->assign('action', 'refund');
    $smarty->display("user_transaction.dwt");
}

//mod by coolvee.com 酷唯软件出品
elseif('act_refund' == $action)
{
    $rec_id = $_POST['rec_id'];
    $refund = array();
    $tui_num = intval($_POST['tui_num']);
    if(empty($tui_num)){
        echo ' <script language="javascript"> alert("填寫退換貨數量"); window.history.go(-1);</script>';
        exit;
    }else{
        $r_num = $GLOBALS['db']->getOne("select goods_number from ".$GLOBALS['ecs']->table("order_goods")." where rec_id='$rec_id'");
        if($r_num < $tui_num){
            echo ' <script language="javascript"> alert("退換貨數量大於商品數量"); window.history.go(-1);</script>';
            exit;
        }
    }
    //换货 2
    $refund['refund_status'] = 2;
    if($_POST['re_g']){
        //其它商品
        if(!empty($_POST['to_goods'])){
            $refund['goods_id'] = $_POST['to_goods'];
            $refund['tui_num'] = $tui_num;
            $refund['refund_num'] = intval($_POST['get_num']);
            if(empty($_POST['to_goods_cat'])){
                $refund['cat'] = '';
            }else{
                $refund['cat'] = $_POST['to_goods_cat'];
            }
        }else{
            echo ' <script language="javascript"> alert("請選擇要換貨的商品"); window.history.go(-1);</script>';
            exit;
        }
    }else{
        //原來商品
        $refund['cat'] = $_POST['re_cat'];
        $refund['tui_num'] = $refund['refund_num'] = $tui_num;
        $refund['goods_id'] = $GLOBALS['db']->getOne("select goods_id from ".$GLOBALS['ecs']->table("order_goods")." where rec_id='$rec_id'");
    }

    if (empty($_POST['refund_reason']))
    {
        echo ' <script language="javascript"> alert("必須選擇換原因"); window.history.go(-1);</script>';
        exit;
    }else{
        $refund['refund_reason'] = $_POST['refund_reason'];
    }
    $refund['refund_desc'] = $_POST['refund_desc'];
/*    unset($refund['rec_id']);
    $refund['refund_pic1'] = (isset($_FILES['refund_pic1']['error']) && $_FILES['refund_pic1']['error'] == 0) || (!isset($_FILES['refund_pic1']['error']) && isset($_FILES['refund_pic1']['tmp_name']) && $_FILES['refund_pic1']['tmp_name'] != 'none')
        ? $_FILES['refund_pic1'] : array();
    $refund['refund_pic2'] = (isset($_FILES['refund_pic2']['error']) && $_FILES['refund_pic2']['error'] == 0) || (!isset($_FILES['refund_pic2']['error']) && isset($_FILES['refund_pic2']['tmp_name']) && $_FILES['refund_pic2']['tmp_name'] != 'none')
        ? $_FILES['refund_pic2'] : array();
    $refund['refund_pic3'] = (isset($_FILES['refund_pic3']['error']) && $_FILES['refund_pic3']['error'] == 0) || (!isset($_FILES['refund_pic3']['error']) && isset($_FILES['refund_pic3']['tmp_name']) && $_FILES['refund_pic3']['tmp_name'] != 'none')
        ? $_FILES['refund_pic3'] : array();
*/
    if(refund_order_goods($refund, $rec_id) )
    {
        show_message("成功申请換貨", "售後服務", "user.php?act=refund_list");
    }
    else
    {
        //$GLOBALS['err']->show("订单列表", 'user.php?act=order_list&type=order_list');
    }

}


/* 处理会员的登录 */
elseif ($action == 'act_login')
{
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';
    
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
    {}
    	$sql ="select user_name from ".$ecs->table('users')." where mobile_phone='".$username."'";
    	$username_e = $db->getOne($sql);
    	if($username_e) $username=$username_e;
    
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
}


/* 处理会员的登录 */
elseif ($action == 'act_logindl')
{
	
	$username = isset($_POST['username']) ? trim($_POST['username']) : '';
	$password = isset($_POST['password']) ? trim($_POST['password']) : '';
	$back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';

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
	/*if(is_telephone($username))
	 {
	$sql ="select user_name from ".$ecs->table('users')." where mobile_phone='".$username."'";
	$username_e = $db->getOne($sql);
	if($username_e) $username=$username_e;
	}*/
	if ($user->login($username, $password,isset($_POST['remember'])))
	{
		update_user_info();
		recalculate_price();

		$ucdata = isset($user->ucdata)? $user->ucdata : '';
		ecs_header("Location: agent_mobile.php");
		//show_message($_LANG['login_success'] . $ucdata , '/agent_mobile.php');
	}
	else
	{
		$_SESSION['login_fail'] ++ ;
		show_message($_LANG['login_failure'], $_LANG['relogin_lnk'], 'user.php', 'error');
	}
}

/* 处理 ajax 的登录请求 */
elseif ($action == 'signin')
{
    include_once('includes/cls_json.php');
    $json = new JSON;

    $username = !empty($_POST['username']) ? json_str_iconv(trim($_POST['username'])) : '';
    $password = !empty($_POST['password']) ? trim($_POST['password']) : '';
    $captcha = !empty($_POST['captcha']) ? json_str_iconv(trim($_POST['captcha'])) : '';
    $result   = array('error' => 0, 'content' => '');

    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($captcha))
        {
            $result['error']   = 1;
            $result['content'] = $_LANG['invalid_captcha'];
            die($json->encode($result));
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {

            $result['error']   = 1;
            $result['content'] = $_LANG['invalid_captcha'];
            die($json->encode($result));
        }
    }

    if ($user->login($username, $password))
    {
        update_user_info();  //更新用户信息
        recalculate_price(); // 重新计算购物车中的商品价格
        $smarty->assign('user_info', get_user_info());
        $ucdata = empty($user->ucdata)? "" : $user->ucdata;
        $result['ucdata'] = $ucdata;
        $result['content'] = $smarty->fetch('library/member_info.lbi');
    }
    else
    {
        $_SESSION['login_fail']++;
        if ($_SESSION['login_fail'] > 2)
        {
            $smarty->assign('enabled_captcha', 1);
            $result['html'] = $smarty->fetch('library/member_info.lbi');
        }
        $result['error']   = 1;
        $result['content'] = $_LANG['login_failure'];
    }
    die($json->encode($result));
}

/* 退出会员中心 */
elseif ($action == 'logout')
{
    if ((!isset($back_act)|| empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
    {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    }
    $user_rate = $_SESSION['area_rate_id'];
    $user->logout();
    //var_dump($_SESSION['area_rate_id']);
    $ucdata = empty($user->ucdata)? "" : $user->ucdata;
    //show_message($_LANG['logout'] . $ucdata, array($_LANG['back_up_page'], $_LANG['back_home_lnk']), array($back_act, 'index.php'), 'info');
    ecs_header("Location: index.php?&rate_id=$user_rate\n");
}

/* 个人资料页面 */
elseif ($action == 'profile')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $user_info = get_profile($user_id);

    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);

    $sql = 'SELECT reg_field_id, content ' .
           'FROM ' . $ecs->table('reg_extend_info') .
           " WHERE user_id = $user_id";
    $extend_info_arr = $db->getAll($sql);

    $temp_arr = array();
    foreach ($extend_info_arr AS $val)
    {
        $temp_arr[$val['reg_field_id']] = $val['content'];
    }

    foreach ($extend_info_list AS $key => $val)
    {
        switch ($val['id'])
        {
            case 1:     $extend_info_list[$key]['content'] = $user_info['msn']; break;
            case 2:     $extend_info_list[$key]['content'] = $user_info['qq']; break;
            case 3:     $extend_info_list[$key]['content'] = $user_info['office_phone']; break;
            case 4:     $extend_info_list[$key]['content'] = $user_info['home_phone']; break;
            case 5:     $extend_info_list[$key]['content'] = $user_info['mobile_phone']; break;
            default:    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']] ;
        }
    }
    if(!empty($user_info['parent_id'])){
    	$parent['name'] = $db->getOne("select user_name from ".$ecs->table('users')." where user_id = ".$user_info['parent_id']);
    	$parent['id'] = $user_info['parent_id'];
    	$smarty->assign('parent', $parent['name']);
    }
    
    $sql = " SELECT dl_pd FROM ".$ecs->table('user_rank')." WHERE rank_id=".$_SESSION['user_rank'];
    $dl_pd = $db->getOne($sql);
    $smarty->assign('dl_pd',$dl_pd);
    $smarty->assign('extend_info_list', $extend_info_list);
    $smarty->assign('helps',      get_shop_help());
    
    /* 密码提示问题 */
    $smarty->assign('passwd_questions', $_LANG['passwd_questions']);

    $smarty->assign('profile', $user_info);
    $smarty->display('user_transaction.dwt');
}

/*解除facebook綁定*/
elseif ($action == 'del_aite'){
    $sql = "update ".$ecs->table('users')." set aite_id = '' where user_id = ".$user_id;
    $db->query($sql);
    show_message('解除facebook綁定成功', $_LANG['profile_lnk'], 'user.php?act=profile', 'info');
}

/* 修改个人资料的处理 */
elseif ($action == 'act_edit_profile')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $birthday = trim($_POST['birthdayYear']) .'-'. trim($_POST['birthdayMonth']) .'-'.
    trim($_POST['birthdayDay']);
    $email = trim($_POST['email']);
    $sfz_number = $_POST['sfz_number'];
    $other['msn'] = $msn = isset($_POST['extend_field1']) ? trim($_POST['extend_field1']) : '';
    $other['qq'] = $qq = isset($_POST['extend_field2']) ? trim($_POST['extend_field2']) : '';
    $other['office_phone'] = $office_phone = isset($_POST['extend_field3']) ? trim($_POST['extend_field3']) : '';
    $other['home_phone'] = $home_phone = isset($_POST['extend_field4']) ? trim($_POST['extend_field4']) : '';
    $other['mobile_phone'] = $mobile_phone = isset($_POST['extend_field5']) ? trim($_POST['extend_field5']) : '';
    $sel_question = empty($_POST['sel_question']) ? '' : $_POST['sel_question'];
    $passwd_answer = isset($_POST['passwd_answer']) ? trim($_POST['passwd_answer']) : '';

    if(!empty($sfz_number))
    {
	    $sql = 'UPDATE ' . $ecs->table('users') . " SET  sfz_number='".$sfz_number."'
	        				WHERE `user_id`='" . $_SESSION['user_id'] . "'";
	    $db->query($sql);
    }
    $EnglishName = $_POST['EnglishName'];
    
    if(!empty($EnglishName))
    {
    	$sql = 'UPDATE ' . $ecs->table('users') . " SET  EnglishName='".$EnglishName."'
        				WHERE `user_id`='" . $_SESSION['user_id'] . "'";
    	$db->query($sql);
    }
    
    $ChineseName = $_POST['ChineseName'];
    
    if(!empty($ChineseName))
    {
    	$sql = 'UPDATE ' . $ecs->table('users') . " SET  ChineseName='".$ChineseName."'
        				WHERE `user_id`='" . $_SESSION['user_id'] . "'";
    	$db->query($sql);
    }
    
    if(!empty($_FILES['sfz_image_z']['name'])&&$_FILES['sfz_image_z']['name']!='')
    {
    	$sfz_image_z   = $image->upload_image($_FILES['sfz_image_z'],'bonuslogo'); // 原始图片
    	//var_dump($original_img);
    	$sql = 'UPDATE ' . $ecs->table('users') . " SET  sfz_image_z='".$sfz_image_z."'
        				WHERE `user_id`='" . $_SESSION['user_id'] . "'";
    	$db->query($sql);
    }
    if(!empty($_FILES['sfz_image_b']['name'])&&$_FILES['sfz_image_b']['name']!='')
    {
    	$sfz_image_b   = $image->upload_image($_FILES['sfz_image_b'],'bonuslogo'); // 原始图片
    	//var_dump($original_img);
    	$sql = 'UPDATE ' . $ecs->table('users') . " SET   sfz_image_b='".$sfz_image_b."'
        				WHERE `user_id`='" . $_SESSION['user_id'] . "'";
    	$db->query($sql);
    }
    
    
    
   
    
    
    
    
    /* 更新用户扩展字段的数据 */
    $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
    $fields_arr = $db->getAll($sql);

    foreach ($fields_arr AS $val)       //循环更新扩展用户信息
    {
        $extend_field_index = 'extend_field' . $val['id'];
        if(isset($_POST[$extend_field_index]))
        {
            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
            $sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            if ($db->getOne($sql))      //如果之前没有记录，则插入
            {
                $sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            }
            else
            {
                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
            }
            $db->query($sql);
        }
    }
    //绑定推荐人
    if(!empty($_POST['parent_name'])){
    	$parent = $db->getRow("select user_id,parent_id,user_rank,rank_vip  from ".$ecs->table('users')." where user_name = '".$_POST['parent_name']."' or email = '".$_POST['parent_name']."' or home_phone='".$_POST['parent_name']."' or mobile_phone='".$_POST['parent_name']."'");
    	$user_rank = $db->getRow("select user_rank,rank_vip  from ".$ecs->table('users')." where user_id=$user_id");
    	
    	if(!empty($parent['user_id']) && !empty($user_id)) {
    		$wherestring = '';
    		if($user_rank['user_rank'] == 1 && $user_rank['$rank_vip']==0)
    		{}else 
    		{
	    		if ($parent['user_rank'] == 1&&$parent['rank_vip'] == 0) {
	    			$wherestring = " ,user_rank=1,rank_vip=1 ";
	    		}
    		}
    		if(!empty($parent['parent_id'])){
    			 
    			if($parent['parent_id'] !=$user_id){
    				 
    				$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = ".$parent['user_id'].$wherestring." WHERE user_id = ".$user_id;
    				$db->query($sql);
    			}
    		}else{
    			 
    			$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = ".$parent['user_id'].$wherestring." WHERE user_id = ".$user_id;
    			$db->query($sql);
    		}
    	}
    }
    /* 写入密码提示问题和答案 */
    if (!empty($passwd_answer) && !empty($sel_question))
    {
        $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
        $db->query($sql);
    }

    if (!empty($office_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $office_phone ) )
    {
        show_message($_LANG['passport_js']['office_phone_invalid']);
    }
    if (!empty($home_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $home_phone) )
    {
         show_message($_LANG['passport_js']['home_phone_invalid']);
    }
    if (!is_email($email))
    {
        show_message($_LANG['msg_email_format']);
    }
    if (!empty($msn) && !is_email($msn))
    {
         show_message($_LANG['passport_js']['msn_invalid']);
    }
    if (!empty($qq) && !preg_match('/^\d+$/', $qq))
    {
         show_message($_LANG['passport_js']['qq_invalid']);
    }
    if (!empty($mobile_phone) && !preg_match('/^[\d-\s]+$/', $mobile_phone))
    {
        show_message($_LANG['passport_js']['mobile_phone_invalid']);
    }


    $profile  = array(
        'user_id'  => $user_id,
        'email'    => isset($_POST['email']) ? trim($_POST['email']) : '',
        'sex'      => isset($_POST['sex'])   ? intval($_POST['sex']) : 0,
        'birthday' => $birthday,
        'other'    => isset($other) ? $other : array()
        );


    if (edit_profile($profile))
    {
        show_message($_LANG['edit_profile_success'], $_LANG['profile_lnk'], 'user.php?act=profile', 'info');
    }
    else
    {
        if ($user->error == ERR_EMAIL_EXISTS)
        {
            $msg = sprintf($_LANG['email_exist'], $profile['email']);
        }
        else
        {
            $msg = $_LANG['edit_profile_failed'];
        }
        show_message($msg, '', '', 'info');
    }
}

/* 密码找回-->修改密码界面 */
elseif ($action == 'get_password')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    if (isset($_GET['code']) && isset($_GET['uid'])) //从邮件处获得的act
    {
        $code = trim($_GET['code']);
        $uid  = intval($_GET['uid']);

        /* 判断链接的合法性 */
        $user_info = $user->get_profile_by_id($uid);
        if (empty($user_info) || ($user_info && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) != $code))
        {
            show_message($_LANG['parm_error'], $_LANG['back_home_lnk'], './', 'info');
        }

        $smarty->assign('uid',    $uid);
        $smarty->assign('code',   $code);
        $smarty->assign('action', 'reset_password');
        $smarty->display('user_passport.dwt');
    }elseif((isset($_POST['mobile_code'])&&isset($_SESSION['mobile_code'])&&$_POST['mobile_code']==$_SESSION['mobile_code']))//短信找回密码
    {
    	$mobile = $_POST['mobile'];
    	$sql = "SELECT  user_id,password,reg_time FROM ".$ecs->table('users')." WHERE office_phone='".$mobile."' or home_phone='".$mobile."' or mobile_phone='".$mobile."' ";
    	$message_user = $db->getRow($sql);
    	$uid = $message_user['user_id'];
    	$code = $code = md5($message_user['user_id'] . $_CFG['hash_code'] . $message_user['reg_time']);
    	$smarty->assign('uid',    $uid);
    	$smarty->assign('code',   $code);
    	$smarty->assign('action', 'reset_password');
    	$smarty->display('user_passport.dwt');
    	
    }
    else
    {
        //显示用户名和email表单
        $smarty->display('user_passport.dwt');
    }
}

/* 密码找回-->输入用户名界面 */
elseif ($action == 'qpassword_name')
{
	if(empty($_SESSION['send_code']))
		$_SESSION['send_code'] = random(6,1);
	$smarty->assign('mobile', $_SESSION['mobile']);
	
	
	
	
	
	
	$smarty->assign('mobile', $_SESSION['mobile']);
	$smarty->assign('send_code', $_SESSION['send_code']);
	
    //显示输入要找回密码的账号表单
    $smarty->display('user_passport.dwt');
}

/* 密码找回-->根据注册用户名取得密码提示问题界面 */
elseif ($action == 'get_passwd_question')
{
    if (empty($_POST['user_name']))
    {
        show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
    }
    else
    {
        $user_name = trim($_POST['user_name']);
    }

    //取出会员密码问题和答案
    $sql = 'SELECT user_id, user_name, passwd_question, passwd_answer FROM ' . $ecs->table('users') . " WHERE user_name = '" . $user_name . "'";
    $user_question_arr = $db->getRow($sql);

    //如果没有设置密码问题，给出错误提示
    if (empty($user_question_arr['passwd_answer']))
    {
        show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
    }

    $_SESSION['temp_user'] = $user_question_arr['user_id'];  //设置临时用户，不具有有效身份
    $_SESSION['temp_user_name'] = $user_question_arr['user_name'];  //设置临时用户，不具有有效身份
    $_SESSION['passwd_answer'] = $user_question_arr['passwd_answer'];   //存储密码问题答案，减少一次数据库访问

    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }

    $smarty->assign('passwd_question', $_LANG['passwd_questions'][$user_question_arr['passwd_question']]);
    $smarty->display('user_passport.dwt');
}

/* 密码找回-->根据提交的密码答案进行相应处理 */
elseif ($action == 'check_answer')
{
    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
        }
    }

    if (empty($_POST['passwd_answer']) || $_POST['passwd_answer'] != $_SESSION['passwd_answer'])
    {
        show_message($_LANG['wrong_passwd_answer'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'info');
    }
    else
    {
        $_SESSION['user_id'] = $_SESSION['temp_user'];
        $_SESSION['user_name'] = $_SESSION['temp_user_name'];
        unset($_SESSION['temp_user']);
        unset($_SESSION['temp_user_name']);
        $smarty->assign('uid',    $_SESSION['user_id']);
        $smarty->assign('action', 'reset_password');
        $smarty->display('user_passport.dwt');
    }
}

/* 发送密码修改确认邮件 */
elseif ($action == 'send_pwd_email')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    /* 初始化会员用户名和邮件地址 */
    $user_name = !empty($_POST['user_name']) ? trim($_POST['user_name']) : '';
    $email     = !empty($_POST['email'])     ? trim($_POST['email'])     : '';

    //用户名和邮件地址是否匹配
    $user_info = $user->get_user_info($user_name);

    if ($user_info && $user_info['email'] == $email)
    {
        //生成code
         //$code = md5($user_info[0] . $user_info[1]);

        $code = md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']);
        //发送邮件的函数
        if (send_pwd_email($user_info['user_id'], $user_name, $email, $code))
        {
            show_message($_LANG['send_success'] . $email, $_LANG['back_home_lnk'], './', 'info');
        }
        else
        {
            //发送邮件出错
            show_message($_LANG['fail_send_password'], $_LANG['back_page_up'], './', 'info');
        }
    }
    else
    {
        //用户名与邮件地址不匹配
        show_message($_LANG['username_no_email'], $_LANG['back_page_up'], '', 'info');
    }
}

/* 重置新密码 */
elseif ($action == 'reset_password')
{
    //显示重置密码的表单
    $smarty->display('user_passport.dwt');
}

/* 修改会员密码 */
elseif ($action == 'act_edit_password')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : null;
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $user_id      = isset($_POST['uid'])  ? intval($_POST['uid']) : $user_id;
    $code         = isset($_POST['code']) ? trim($_POST['code'])  : '';

    if (strlen($new_password) < 6)
    {
        show_message($_LANG['passport_js']['password_shorter']);
    }

    $user_info = $user->get_profile_by_id($user_id); //论坛记录

    if (($user_info && (!empty($code) && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) == $code)) ||
        ($_SESSION['user_id']>0 && $_SESSION['user_id'] == $user_id && $user->check_user($_SESSION['user_name'], $old_password)))
    {

        if ($user->edit_user(array('username'=> (empty($code) ? $_SESSION['user_name'] : $user_info['user_name']), 'old_password'=>$old_password, 'password'=>$new_password), empty($code) ? 0 : 1))
        {
			$sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0' WHERE user_id= '".$user_id."'";
			$db->query($sql);
            $rate_id = $_SESSION['area_rate_id'];
            $user->logout();
			$_SESSION['user_id']     = 0;
            $_SESSION['user_name']   = '';
            $_SESSION['email']       = '';
            $_SESSION['user_rank']   = 0;
            $_SESSION['discount']    = 1.00;
            $_SESSION['area_rate_id'] = $rate_id;
            $_SESSION['area_rate_id'] = $rate_id;
            show_message($_LANG['edit_password_success'], $_LANG['relogin_lnk'], 'user.php?act=login', 'info');
        }
        else
        {
            show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
        }
    }
    else
    {
        show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
    }

}

/* 添加一个现金卷 */
elseif ($action == 'act_add_bonus')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $bouns_sn = isset($_POST['bonus_sn']) ? intval($_POST['bonus_sn']) : '';

    if (add_bonus($user_id, $bouns_sn))
    {
        show_message($_LANG['add_bonus_sucess'], $_LANG['back_up_page'], 'user.php?act=bonus', 'info');
    }
    else
    {
        $err->show($_LANG['back_up_page'], 'user.php?act=bonus');
    }
}

/* 查看订单列表 */
elseif ($action == 'order_list')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $type = $_REQUEST['type'];

    $sql = "SELECT order_id, order_sn, order_status, shipping_status, pay_status, add_time,referer, " .
        "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
        " ,(dl_surplus+dl_surplus_no) as dl_total ,(fdl_surplus_no+surplus) as fdl_total ".
        " FROM " .$GLOBALS['ecs']->table('order_info') .
        " WHERE user_id = '$user_id' ";

    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'");
    $record_count_pay = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'".order_query_sql('await_pay_long'));
    $record_count_ship = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'".order_query_sql('await_ship'));
    $record_count_shipped = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'".order_query_sql('await_shipped'));
    $record_count_shipped_x = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE user_id = '$user_id' ". "AND referer!='' AND referer!='本站' AND referer!='管理員添加'");
    $sql1 = "SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " AS o , ".$GLOBALS['ecs']->table('order_goods')." as g  WHERE o.user_id = '$user_id' ". "  and o.order_id = g.order_id and g.fuwu=1  GROUP BY o.order_id";
    $record_count_fuwu_order = count($GLOBALS['db']->getAll($sql1));
    $smarty->assign('record_count_fuwu_order',  $record_count_fuwu_order);

    $smarty->assign('record_count',  $record_count);
    $smarty->assign('record_count_pay',  $record_count_pay);
    $smarty->assign('record_count_ship',  $record_count_ship);
    $smarty->assign('record_count_shipped',  $record_count_shipped);
    $smarty->assign('record_count_shipped_x',  $record_count_shipped_x);
    $res = array();
    $orders = array();
    switch($type){
        case 'order_list':
            $pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count, $page);
            $orders = get_user_orders($user_id, $pager['size'], $pager['start']);
            break;
        case 'await_pay_long':
            $pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count_pay, $page);
            $res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_pay_long')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
            break;
        case 'await_ship':
            $pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count_ship, $page);
            $res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_ship')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
            break;
        case 'await_shipped':
            $pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count_shipped, $page);
            $res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_shipped')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
            break;
        case 'await_shipped_x':
            $pager  = get_pager('reg.php', array('act' => $action), $record_count_shipped_x, $page);
            $res = $GLOBALS['db']->SelectLimit($sql." AND referer!='' AND referer!='本站' AND referer!='管理員添加' "." ORDER BY add_time DESC", $pager['size'], $pager['start']);
            break;
        case 'fuwu_order':
            $pager  = get_pager('reg.php', array('act' => $action), $record_count_fuwu_order, $page);
            $sql_fuwu = "SELECT o.order_id, o.order_sn,o.order_status, o.shipping_status,o.pay_status, o.add_time,o.referer, " . "(o.goods_amount + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee + o.tax - o.discount) AS total_fee "." FROM " .$GLOBALS['ecs']->table('order_info') .
                " as o, ".$GLOBALS['ecs']->table('order_goods')." as g  WHERE o.user_id = '$user_id' and o.order_id = g.order_id and g.fuwu = 1  GROUP BY o.order_id  ORDER BY add_time DESC";
            $res = $GLOBALS['db']->SelectLimit($sql_fuwu, $pager['size'], $pager['start']);
            break;
    }

    if(!empty($res)){
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
            $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];

            $num = $GLOBALS['db']->getOne("select sum(goods_number) from".$GLOBALS['ecs']->table('order_goods')." where order_id = ".$row['order_id']);

            switch($type){
                case 'await_pay_long':
                    $order_pay = get_order_detail($row['order_id'], $user_id);
                    $row['handler'] = $order_pay['pay_online'];
                    break;
                case 'await_ship':
                    $row['handler'] = '<span style="color:red">'.'已確認' .'</span>';
                    break;
                case 'await_shipped':
                    $row['handler'] = "<a href=\"user.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_received']."')) return false;\">".$GLOBALS['_LANG']['received']."</a>";
                    break;
                case 'fuwu_order':
                    $sql = "select * from ".$GLOBALS['ecs']->table('order_goods')." where order_id = ".$row['order_id']." and fuwu = 1";
                    $fuwu_list = $GLOBALS['db']->getAll($sql);
                    $row['is_fuwu'] = 0;
                    foreach($fuwu_list as $v){
                        if($v['goods_number'] != $v['use_number']){
                            $row['is_fuwu'] = 1;
                            break;
                        }
                    }
                    $num = $GLOBALS['db']->getOne("select sum(goods_number) from".$GLOBALS['ecs']->table('order_goods')." where fuwu=1 and order_id = ".$row['order_id']);
                    break;
            }
            $pd_dl = 0;
			if($row['dl_total']>0)
			{
				$pd_dl = 1;
			}
            $orders[] = array('order_id'       => $row['order_id'],
                            'order_sn'       => $row['order_sn'],
                            'order_num'      => $num,
            				'pd_dl'          =>$pd_dl,
            				'dl_total'       =>'HKD $'.$row['dl_total'],
            				'fdl_total'      =>price_format($row['fdl_total']),
                            'is_fuwu'        =>$row['is_fuwu'],
            				'referer'        =>$row['referer'],
                            'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
                            'order_status'   => $row['order_status'],
                            'total_fee'      => price_format($row['total_fee']),
                            'handler'        => $row['handler']);
           
        }
    }
    $smarty->assign('helps',      get_shop_help());
    $smarty->assign('action_type',  $type);
    $merge  = get_user_merge($user_id);
    $smarty->assign('merge',  $merge);
    $smarty->assign('pager',  $pager);
    $smarty->assign('orders', $orders);
	
	
    $smarty->display('user_transaction.dwt');
}
elseif ($action == 'order_listdl')
{

    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $record_count = $db->getOne("select count(*) from ".$ecs->table('order_info')." as o, ".$ecs->table('users')." as u where o.user_id=u.user_id and u.parent_id = ".$user_id." and (o.parent_fc>0 or o.tj_fc>0 )");

    $smarty->assign('record_count',  $record_count);

    $sql = "select o.order_id, o.order_sn, o.order_status, o.shipping_status, o.pay_status, o.add_time,o.referer,o.parent_fc, o.tj_fc, o.dd_fl,(o.goods_amount + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee + o.tax - o.discount) AS total_fee from ".
        $ecs->table('order_info')." as o, ".$ecs->table('users')." as u where o.user_id=u.user_id and u.parent_id = ".$user_id." and (o.parent_fc>0 or o.tj_fc>0 )";

    $pager  = get_pager('user.php', array('act' => $action), $record_count, $page);

    $res = $GLOBALS['db']->SelectLimit($sql." ORDER BY add_time DESC", $pager['size'], $pager['start']);
	$orders = array();

	if(!empty($res)){
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
            $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];


            $num = $GLOBALS['db']->getOne("select sum(goods_number) from".$GLOBALS['ecs']->table('order_goods')." where order_id = ".$row['order_id']);

            $orders[] = array('order_id'       => $row['order_id'],
                'order_sn'       => $row['order_sn'],
                'order_num'      => $num,
                'referer'        =>$row['referer'],
                'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
                'order_status'   => $row['order_status'],
                'total_fee'      => $row['total_fee'],
                'dl_fee'         =>$row['parent_fc']+$row['tj_fc'],
                'handler'        => '');
        }
    }

	$smarty->assign('helps',      get_shop_help());
	$merge  = get_user_merge($user_id);
	$smarty->assign('merge',  $merge);
	$smarty->assign('pager',  $pager);
	$smarty->assign('orders', $orders);

	$smarty->display('user_transaction.dwt');
}
elseif ($action == 'order_listfl')
{

	include_once(ROOT_PATH . 'includes/lib_transaction.php');
	include_once(ROOT_PATH . 'includes/lib_payment.php');
	include_once(ROOT_PATH . 'includes/lib_order.php');
	include_once(ROOT_PATH . 'includes/lib_clips.php');

	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

	$type = $_REQUEST['type'];

	$sql = "SELECT order_id, order_sn, order_status, shipping_status, pay_status, add_time,referer, " .
			"(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
			" FROM " .$GLOBALS['ecs']->table('order_info') .
			" WHERE user_id = '$user_id' ";

	$record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'");
	$record_count_pay = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'".order_query_sql('await_pay_long'));
	$record_count_ship = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'".order_query_sql('await_ship'));
	$record_count_shipped = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'".order_query_sql('await_shipped'));
	$record_count_shipped_x = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE user_id = '$user_id' ". "AND referer!='' AND referer!='本站' AND referer!='管理員添加'");
	$sql1 = "SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " AS o , ".$GLOBALS['ecs']->table('order_goods')." as g  WHERE o.user_id = '$user_id' ". "  and o.order_id = g.order_id and g.fuwu=1  GROUP BY o.order_id";
	$record_count_fuwu_order = count($GLOBALS['db']->getAll($sql1));
	$smarty->assign('record_count_fuwu_order',  $record_count_fuwu_order);

	$smarty->assign('record_count',  $record_count);
	$smarty->assign('record_count_pay',  $record_count_pay);
	$smarty->assign('record_count_ship',  $record_count_ship);
	$smarty->assign('record_count_shipped',  $record_count_shipped);
	$smarty->assign('record_count_shipped_x',  $record_count_shipped_x);
	$res = array();
	$orders = array();
	switch($type){
		case 'order_list':
			$pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count, $page);
			$orders = get_user_orders($user_id, $pager['size'], $pager['start']);
			break;
		case 'await_pay_long':
			$pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count_pay, $page);
			$res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_pay_long')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
			break;
		case 'await_ship':
			$pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count_ship, $page);
			$res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_ship')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
			break;
		case 'await_shipped':
			$pager  = get_pager('user.php', array('act' => $action,'type'=>$type), $record_count_shipped, $page);
			$res = $GLOBALS['db']->SelectLimit($sql.order_query_sql('await_shipped')." ORDER BY add_time DESC", $pager['size'], $pager['start']);
			break;
		case 'await_shipped_x':
			$pager  = get_pager('reg.php', array('act' => $action), $record_count_shipped_x, $page);
			$res = $GLOBALS['db']->SelectLimit($sql." AND referer!='' AND referer!='本站' AND referer!='管理員添加' "." ORDER BY add_time DESC", $pager['size'], $pager['start']);
			break;
		case 'fuwu_order':
			$pager  = get_pager('reg.php', array('act' => $action), $record_count_fuwu_order, $page);
			$sql_fuwu = "SELECT o.order_id, o.order_sn,o.order_status, o.shipping_status,o.pay_status, o.add_time,o.referer, " . "(o.goods_amount + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee + o.tax - o.discount) AS total_fee "." FROM " .$GLOBALS['ecs']->table('order_info') .
			" as o, ".$GLOBALS['ecs']->table('order_goods')." as g  WHERE o.user_id = '$user_id' and o.order_id = g.order_id and g.fuwu = 1  GROUP BY o.order_id  ORDER BY add_time DESC";
			$res = $GLOBALS['db']->SelectLimit($sql_fuwu, $pager['size'], $pager['start']);
			break;
	}

	if(!empty($res)){
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
			$row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];

			$num = $GLOBALS['db']->getOne("select sum(goods_number) from".$GLOBALS['ecs']->table('order_goods')." where order_id = ".$row['order_id']);

			switch($type){
				case 'await_pay_long':
					$order_pay = get_order_detail($row['order_id'], $user_id);
					$row['handler'] = $order_pay['pay_online'];
					break;
				case 'await_ship':
					$row['handler'] = '<span style="color:red">'.'已確認' .'</span>';
					break;
				case 'await_shipped':
					$row['handler'] = "<a href=\"user.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_received']."')) return false;\">".$GLOBALS['_LANG']['received']."</a>";
					break;
				case 'fuwu_order':
					$sql = "select * from ".$GLOBALS['ecs']->table('order_goods')." where order_id = ".$row['order_id']." and fuwu = 1";
					$fuwu_list = $GLOBALS['db']->getAll($sql);
					$row['is_fuwu'] = 0;
					foreach($fuwu_list as $v){
						if($v['goods_number'] != $v['use_number']){
							$row['is_fuwu'] = 1;
							break;
						}
					}
					$num = $GLOBALS['db']->getOne("select sum(goods_number) from".$GLOBALS['ecs']->table('order_goods')." where fuwu=1 and order_id = ".$row['order_id']);
					break;
			}

			$orders[] = array('order_id'       => $row['order_id'],
					'order_sn'       => $row['order_sn'],
					'order_num'      => $num,
					'is_fuwu'        =>$row['is_fuwu'],
					'referer'        =>$row['referer'],
					'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
					'order_status'   => $row['order_status'],
					'total_fee'      => $row['total_fee'],
					'handler'        => $row['handler']);
		}
	}

	$smarty->assign('helps',      get_shop_help());
	$smarty->assign('action_type',  $type);
	$merge  = get_user_merge($user_id);
	$smarty->assign('merge',  $merge);
	$smarty->assign('pager',  $pager);
	$smarty->assign('orders', $orders);

	$smarty->display('user_transaction.dwt');
}
/* 查看订单详情 */
elseif ($action == 'order_detail')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    /* 订单详情 */
    $order = get_order_detail($order_id, $user_id);
	
    
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

    $sql = "select * from ".$GLOBALS['ecs']->table('order_goods')." where order_id = $order_id and fuwu = 1";
    $fuwu_list = $GLOBALS['db']->getAll($sql);
    $is_fuwu = 0;
    foreach($fuwu_list as $v){
        if($v['goods_number'] != $v['use_number']){
            $is_fuwu = 1;
            break;
        }
    }
    $smarty->assign('is_fuwu', $is_fuwu);
    /* 订单商品 */
    $goods_list = order_goods($order_id);

	$dl_pd = 0;
	if($order['dl_surplus']>0||$order['dl_surplus_no']>0)
	{
		$dl_pd = 1;
	}
	$order['dl_pd'] = $dl_pd;
	$dl_goods_total = 0;
	$fdl_goods_total = 0;
    foreach ($goods_list AS $key => $value)
    {
    	$dl_goods = 0;
    	$sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$value['goods_id'];
    	$dl_goods = $GLOBALS['db']->getOne($sql);
    	if($dl_pd==1&&$dl_goods==1)
        {
        	$dl_goods_total = $dl_goods_total + ($value['goods_price']*$value['goods_number']);
        	$goods_list[$key]['market_price'] ='HKD $'.$value['market_price'];
        	$goods_list[$key]['goods_price']  = 'HKD $'.$value['goods_price'];
        	$goods_list[$key]['subtotal']     = 'HKD $'.$value['subtotal'];
        	
        }else 
        {
        	$fdl_goods_total = $fdl_goods_total + ($value['goods_price']*$value['goods_number']);
        	$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
        	$goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
        	$goods_list[$key]['subtotal']     = price_format($value['subtotal'], false);
        }
        
        $goods_list[$key]['yh']           = floatval($value['market_price']) - floatval($value['goods_price']);
        $goods_list[$key]['img']          = get_image_path($value['goods_id'],$GLOBALS['db']->getOne("select goods_thumb from ".$GLOBALS['ecs']->table('goods').
            " where goods_id =".$value['goods_id']));
        $goods_list[$key]['frwu_num'] = 0;
        if(!empty($value['fuwu']) && !empty($value['goods_sn'])){
            $goods_list[$key]['frwu_num'] = $value['goods_number'] - $value['use_number'];
        }
    }
    
    
   
     /* 设置能否修改使用余额数 */
    if ($order['order_amount'] > 0)
    {
        if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED)
        {
            $user = user_info($order['user_id']);
            if ($user['user_money'] + $user['credit_line'] > 0)
            {
                $smarty->assign('allow_edit_surplus', 1);
                $smarty->assign('max_surplus', sprintf($_LANG['max_surplus'], price_format($user['user_money'])));
            }
        }
    }
    /*能使用代理预存款扣预存款扣费*/
    if($dl_pd==1)
    {
    	if($order['dl_surplus_no']>0)
    	{
    		$user = user_info($order['user_id']);
    		if($user['dl_money']>0)
    		{
    			$smarty->assign('allow_edit_surplus_dl', 1);
    			$smarty->assign('max_surplus_dl','HKD $'.$user['dl_money']);
    		}
    	}
    }
    if($order['dl_surplus_no']>0||$order['fdl_surplus_no']>0)
    {
    	$user = user_info($order['user_id']);
    	if($user['dlfcmoney']>0)
    	{
    		$smarty->assign('allow_edit_surplus_dlfc', 1);
    		$smarty->assign('max_surplus_dlfc','HKD $'.$user['dlfcmoney']);
    	}
    }
    
    $order['dlfc_surplus'] = $order['dlfcmoney_hk'] + $order['dlfcmoney_area_hk'];
    $order['dlfc_surplus_format'] = 'HKD $'.$order['dlfc_surplus']; 
    $order['dl_goods_total'] = 'HKD $'.$dl_goods_total;
    $order['fdl_goods_total'] = price_format($fdl_goods_total);
    $order['dl_surplus_no'] = 'HKD $'.$order['dl_surplus_no'];
    $order['fdl_surplus_no'] = price_format($order['fdl_surplus_no']);
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
   $smarty->display('user_transaction.dwt');
   // $smarty->display('user_orderetails.dwt');
}

/* 取消订单 */
elseif ($action == 'cancel_order')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (cancel_order($order_id, $user_id))
    {
        ecs_header("Location: user.php?act=order_list&type=order_list\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list&type=order_list');
    }
}

/* 收货地址列表界面*/
elseif ($action == 'address_list')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
    $smarty->assign('lang',  $_LANG);

    /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
    $smarty->assign('country_list',       get_regions());
    $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

    /* 获得用户所有的收货人信息 */
    $consignee_list = get_consignee_list($_SESSION['user_id']);

    
    if (count($consignee_list) < 5 && $_SESSION['user_id'] > 0)
    {
        /* 如果用户收货人信息的总数小于5 则增加一个新的收货人信息 */
        $consignee_list[] = array('country' => $_CFG['shop_country'], 'email' => isset($email) ? $email : '');
    }

    $smarty->assign('countAddress', count($consignee_list));

    foreach ($consignee_list as $region_id => $consignee)
    {
        if(!empty($consignee['best_time'])) {
            $bt_list = explode(',', $consignee['best_time']);
            $bt_value = '';
            foreach($bt_list as $btt){
                $bt_value = $bt_value."<li><span>$btt</span></li>";
            }
            $consignee_list[$region_id]['bt_list'] = $bt_value;
        }
    }
   
    $smarty->assign('consignee_list', $consignee_list);
    

    //取得国家列表，如果有收货人列表，取得省市区列表
    foreach ($consignee_list AS $region_id => $consignee)
    {
        $consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
        $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
        $consignee['city']     = isset($consignee['city'])     ? intval($consignee['city'])     : 0;

        $province_list[$region_id] = get_regions(1, $consignee['country']);
        $city_list[$region_id]     = get_regions(2, $consignee['province']);
        $district_list[$region_id] = get_regions(3, $consignee['city']);
    }

    /* 获取默认收货ID */
    $address_id  = $db->getOne("SELECT address_id FROM " .$ecs->table('users'). " WHERE user_id='$user_id'");
    $smarty->assign('defaultAdress', $address_id);

    $smarty->assign('helps',      get_shop_help());
    //赋值于模板
    $smarty->assign('real_goods_count', 1);
    $smarty->assign('shop_country',     $_CFG['shop_country']);
    $smarty->assign('shop_province',    get_regions(1, $_CFG['shop_country']));
    $smarty->assign('province_list',    $province_list);
    $smarty->assign('address',          $address_id);
    $smarty->assign('city_list',        $city_list);
    $smarty->assign('district_list',    $district_list);
    $smarty->assign('currency_format',  $_CFG['currency_format']);
    $smarty->assign('integral_scale',   $_CFG['integral_scale']);
    $smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));

    $smarty->display('user_transaction.dwt');
}

elseif ($action == 'set_default_address')
{

    $address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : 0;

    if ($address_id > 0)
    {
        $sql = "update ".$GLOBALS['ecs']->table("users")." set address_id ='".$address_id."' where user_id = ".$user_id;
	    $GLOBALS['db']->query($sql);
    } 
   
   // ecs_header("Location: user.php?act=address_list\n");
     show_message($_LANG['edit_address_success'], $_LANG['address_list_lnk'], 'user.php?act=address_list');
    
}

/* 添加/编辑收货地址的处理 */
elseif ($action == 'act_edit_address')
{
	
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
    $smarty->assign('lang', $_LANG);

    $address = array(
        'user_id'    => $user_id,
        'address_id' => intval($_POST['address_id']),
        'country'    => isset($_POST['country'])   ? intval($_POST['country'])  : 0,
        'province'   => isset($_POST['province'])  ? intval($_POST['province']) : 0,
        'city'       => isset($_POST['city'])      ? intval($_POST['city'])     : 0,
        'district'   => isset($_POST['district'])  ? intval($_POST['district']) : 0,
        'address'    => isset($_POST['address'])   ? trim($_POST['address'])    : '',
        'consignee'  => isset($_POST['consignee']) ? trim($_POST['consignee'])  : '',
        'email'      => isset($_POST['email'])     ? trim($_POST['email'])      : '',
        'tel'        => isset($_POST['tel'])       ? make_semiangle(trim($_POST['tel'])) : '',
        'mobile'     => isset($_POST['mobile'])    ? make_semiangle(trim($_POST['mobile'])) : '',
        'best_time'  => isset($_POST['best_time']) ? trim($_POST['best_time'])  : '',
        'sign_building' => isset($_POST['sign_building']) ? trim($_POST['sign_building']) : '',
        'zipcode'       => isset($_POST['zipcode'])       ? make_semiangle(trim($_POST['zipcode'])) : '',
        );

    if (update_address($address))
    {
        show_message($_LANG['edit_address_success'], $_LANG['address_list_lnk'], 'user.php?act=address_list');
    }
}

/* 删除收货地址 */
elseif ($action == 'drop_consignee')
{

    include_once('includes/lib_transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        ecs_header("Location: user.php?act=address_list\n");
        exit;
    }
    else
    {
        show_message($_LANG['del_address_false']);
    }
}

/* 显示收藏商品列表 */
elseif ($action == 'collection_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('collect_goods').
                                " WHERE user_id='$user_id' ORDER BY add_time DESC");

    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);
    $smarty->assign('pager', $pager);
    $smarty->assign('goods_list', get_collection_goods($user_id, $pager['size'], $pager['start']));
    $smarty->assign('url',        $ecs->url());
    $lang_list = array(
        'UTF8'   => $_LANG['charset']['utf8'],
        'GB2312' => $_LANG['charset']['zh_cn'],
        'BIG5'   => $_LANG['charset']['zh_tw'],
    );
    $smarty->assign('helps',      get_shop_help());
    $smarty->assign('lang_list',  $lang_list);
    $smarty->assign('user_id',  $user_id);
    $smarty->display('user_clips.dwt');
}

/* 删除收藏的商品 */
elseif ($action == 'delete_collection')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : 0;

    if ($collection_id > 0)
    {
        $db->query('DELETE FROM ' .$ecs->table('collect_goods'). " WHERE rec_id='$collection_id' AND user_id ='$user_id'" );
    }

    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}

/* 添加关注商品 */
elseif ($action == 'add_to_attention')
{
    $rec_id = (int)$_GET['rec_id'];
    if ($rec_id)
    {
        $db->query('UPDATE ' .$ecs->table('collect_goods'). "SET is_attention = 1 WHERE rec_id='$rec_id' AND user_id ='$user_id'" );
    }
    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}
/* 取消关注商品 */
elseif ($action == 'del_attention')
{
    $rec_id = (int)$_GET['rec_id'];
    if ($rec_id)
    {
        $db->query('UPDATE ' .$ecs->table('collect_goods'). "SET is_attention = 0 WHERE rec_id='$rec_id' AND user_id ='$user_id'" );
    }
    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}
/* 显示留言列表 */
elseif ($action == 'message_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
    $order_info = array();
    
    /* 获取用户留言的数量 */
    if ($order_id)
    {
        $sql = "SELECT COUNT(*) FROM " .$ecs->table('feedback').
                " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id'";
        $order_info = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE order_id = '$order_id' AND user_id = '$user_id'");
        $order_info['url'] = 'user.php?act=order_detail&order_id=' . $order_id;
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " .$ecs->table('feedback').
           " WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
    }

    $record_count = $db->getOne($sql);
   
    $act = array('act' => $action);

    if ($order_id != '')
    {
        $act['order_id'] = $order_id;
       
    }
    
    $pager = get_pager('user.php', $act, $record_count, $page, 5);

    $smarty->assign('helps',      get_shop_help());
    $smarty->assign('message_list', get_message_list($user_id, $_SESSION['user_name'], $pager['size'], $pager['start'], $order_id));
    $smarty->assign('pager',        $pager);
    $smarty->assign('order_info',   $order_info);
    $smarty->display('user_clips.dwt');
}

/* 显示评论列表 */
elseif ($action == 'comment_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取用户留言的数量 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('comment').
           " WHERE parent_id = 0 AND user_id = '$user_id'";
    $record_count = $db->getOne($sql);
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page, 5);

    $smarty->assign('comment_list', get_comment_list($user_id, $pager['size'], $pager['start']));
    $smarty->assign('pager',        $pager);
    $smarty->display('user_clips.dwt');
}

/* 添加我的留言 */
elseif ($action == 'act_add_message')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $message = array(
        'user_id'     => $user_id,
        'user_name'   => $_SESSION['user_name'],
        'user_email'  => $_SESSION['email'],
        'msg_type'    => isset($_POST['msg_type']) ? intval($_POST['msg_type'])     : 0,
        'msg_title'   => isset($_POST['msg_title']) ? trim($_POST['msg_title'])     : '',
        'msg_content' => isset($_POST['msg_content']) ? trim($_POST['msg_content']) : '',
        'order_id'=>empty($_POST['order_id']) ? 0 : intval($_POST['order_id']),
        'upload'      => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none')
         ? $_FILES['message_img'] : array()
     );

    if (add_message($message))
    {
        show_message($_LANG['add_message_success'], $_LANG['message_list_lnk'], 'user.php?act=message_list&order_id=' . $message['order_id'],'info');
    }
    else
    {
        $err->show($_LANG['message_list_lnk'], 'user.php?act=message_list');
    }
}

/* 标签云列表 */
elseif ($action == 'tag_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $good_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $smarty->assign('tags',      get_user_tags($user_id));
    $smarty->assign('tags_from', 'user');
    $smarty->display('user_clips.dwt');
}

/* 删除标签云的处理 */
elseif ($action == 'act_del_tag')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $tag_words = isset($_GET['tag_words']) ? trim($_GET['tag_words']) : '';
    delete_tag($tag_words, $user_id);

    ecs_header("Location: user.php?act=tag_list\n");
    exit;

}

/* 显示缺货登记列表 */
elseif ($action == 'booking_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取缺货登记的数量 */
    $sql = "SELECT COUNT(*) " .
            "FROM " .$ecs->table('booking_goods'). " AS bg, " .
                     $ecs->table('goods') . " AS g " .
            "WHERE bg.goods_id = g.goods_id AND user_id = '$user_id'";
    $record_count = $db->getOne($sql);
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);

    $smarty->assign('booking_list', get_booking_list($user_id, $pager['size'], $pager['start']));
    $smarty->assign('pager',        $pager);
    $smarty->display('user_clips.dwt');
}
/* 添加缺货登记页面 */
elseif ($action == 'add_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $goods_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($goods_id == 0)
    {
        show_message($_LANG['no_goods_id'], $_LANG['back_page_up'], '', 'error');
    }

    /* 根据规格属性获取货品规格信息 */
    $goods_attr = '';
    if ($_GET['spec'] != '')
    {
        $goods_attr_id = $_GET['spec'];

        $attr_list = array();
        $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $ecs->table('goods_attr') . " AS g, " .
                    $ecs->table('attribute') . " AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($goods_attr_id);
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);
    }
    $smarty->assign('goods_attr', $goods_attr);

    $smarty->assign('info', get_goodsinfo($goods_id));
    $smarty->display('user_clips.dwt');

}

/* 添加缺货登记的处理 */
elseif ($action == 'act_add_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $booking = array(
        'goods_id'     => isset($_POST['id'])      ? intval($_POST['id'])     : 0,
        'goods_amount' => isset($_POST['number'])  ? intval($_POST['number']) : 0,
        'desc'         => isset($_POST['desc'])    ? trim($_POST['desc'])     : '',
        'linkman'      => isset($_POST['linkman']) ? trim($_POST['linkman'])  : '',
        'email'        => isset($_POST['email'])   ? trim($_POST['email'])    : '',
        'tel'          => isset($_POST['tel'])     ? trim($_POST['tel'])      : '',
        'booking_id'   => isset($_POST['rec_id'])  ? intval($_POST['rec_id']) : 0
    );

    // 查看此商品是否已经登记过
    $rec_id = get_booking_rec($user_id, $booking['goods_id']);
    if ($rec_id > 0)
    {
        show_message($_LANG['booking_rec_exist'], $_LANG['back_page_up'], '', 'error');
    }

    if (add_booking($booking))
    {
        show_message($_LANG['booking_success'], $_LANG['back_booking_list'], 'user.php?act=booking_list',
        'info');
    }
    else
    {
        $err->show($_LANG['booking_list_lnk'], 'user.php?act=booking_list');
    }
}

/* 删除缺货登记 */
elseif ($action == 'act_del_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        ecs_header("Location: user.php?act=booking_list\n");
        exit;
    }

    $result = delete_booking($id, $user_id);
    if ($result)
    {
        ecs_header("Location: user.php?act=booking_list\n");
        exit;
    }
}

/* 确认收货 */
elseif ($action == 'affirm_received')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (affirm_received($order_id, $user_id))
    {
        ecs_header("Location: user.php?act=order_list&type=order_list\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list&type=order_list');
    }
}

/* 会员退款申请界面 */
elseif ($action == 'account_raply')
{
    $smarty->display('user_transaction.dwt');
}

/* 会员预付款界面 */
elseif ($action == 'account_deposit')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $account    = get_surplus_info($surplus_id);
	

    $smarty->assign('payment', get_online_payment_list(false));
    $smarty->assign('order',   $account);
    $smarty->display('user_transaction.dwt');
}/* 会员预付款界面 */
elseif ($action == 'account_depositdl')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $account    = get_surplus_info($surplus_id);
	
    
    $smarty->assign('payment', get_online_payment_list(false));
    $smarty->assign('order',   $account);
    $smarty->display('user_depositdl.dwt');
}

/* 会员账目明细界面 */
elseif ($action == 'account_detail')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $account_type = 'user_money';

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('account_log').
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 ";
    
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);

    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    $user_money = get_user_money($user_id);

    $smarty->assign('user_money',          $user_money);

    //获取余额记录
    $account_log = array();
    $sql = "SELECT * FROM " . $ecs->table('account_log') .
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 " .
           " ORDER BY log_id DESC";
   
    $res = $GLOBALS['db']->selectLimit($sql, $pager['size'], $pager['start']);
    while ($row = $db->fetchRow($res))
    {
        $row['change_time'] = local_date($_CFG['date_format'], $row['change_time']);
        $row['type'] = $row[$account_type] > 0 ? $_LANG['account_inc'] : $_LANG['account_dec'];
        if($row['dl_use'] == 1)
        {
        	$row['user_money'] = 'HKD $ '.abs($row['user_money']);
        }else 
        {
        	$row['user_money'] = price_format(abs($row['user_money']), false);
        }
        $row['frozen_money'] = price_format(abs($row['frozen_money']), false);
        $row['rank_points'] = abs($row['rank_points']);
        $row['pay_points'] = abs($row['pay_points']);
        $row['short_change_desc'] = sub_str($row['change_desc'], 60);
        $row['amount'] = $row[$account_type];
        $account_log[] = $row;
    }

  
    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_clips.dwt');
}

/* 会员充值和提现申请记录 */
elseif ($action == 'account_log')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('user_account').
           " WHERE user_id = '$user_id'" .
           " AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);
	
    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    //余额 long

    $user_money = get_user_money($user_id);
    $smarty->assign('user_money',          $user_money);
    //获取余额记录
    $account_log = get_account_log($user_id, $pager['size'], $pager['start']);
    $smarty->assign('helps',      get_shop_help());
   
    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_clips.dwt');
}
/* 会员奖金记录 */
elseif ($action == 'dlfc_log')
{
	include_once(ROOT_PATH . 'includes/lib_clips.php');

	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

	/* 获取记录条数 */
	$sql = "SELECT COUNT(*) FROM " .$ecs->table('user_account').
	" WHERE user_id = '$user_id'" .
	" AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
	$record_count = $db->getOne($sql);

	//分页函数
	$pager = get_pager('user.php', array('act' => $action), $record_count, $page);

	//获取剩余余额
	$surplus_amount = get_user_surplus($user_id);
	if (empty($surplus_amount))
	{
		$surplus_amount = 0;
	}

	$sql = "SELECT sum(money) as mon,huobi FROM ".$ecs->table('dlfc_log')." WHERE is_done=1 and user_id=".$_SESSION['user_id']."  group by huobi";//查询已发总奖金数  //按地区统计在算汇率
	$yfjj = $db->getAll($sql);

	$yf_all = 0;
	foreach($yfjj as $y){
		if($y['huobi'] == 2){
			$yf_all +=$y['mon'];
		}else{
			$h=huo_lu_currency($y['huobi'],2);
			$money_y = round($y['mon']*$h,2);
			$yf_all +=$money_y;
		}
	}
	
	$sql = "SELECT sum(money) as mon,huobi FROM ".$ecs->table('dlfc_log')." WHERE is_done=0 and user_id=".$_SESSION['user_id']."  group by huobi";//查询未发奖金数      //按地区统计在算汇率
	$wfjj = $db->getAll($sql);

	$wf_all = 0;
	foreach($wfjj as $y){
		if($y['huobi'] == 2){
			$wf_all +=$y['mon'];
		}else{
			$h=huo_lu_currency($y['huobi'],2);
			$money_y = round($y['mon']*$h,2);
			$wf_all +=$money_y;
		}
	}
	$smarty->assign('yf',          'HKD $ '.$yf_all);
	$smarty->assign('wf',          'HKD $ '.$wf_all);

	//余额 long

	$user_money = get_user_money($user_id);

	$smarty->assign('user_money',          $user_money);
	//获取余额记录
	$account_log = get_account_log($user_id, $pager['size'], $pager['start']);
	$smarty->assign('helps',      get_shop_help());
	 
	//模板赋值
	$smarty->assign('surplus_amount', price_format($surplus_amount, false));
	$smarty->assign('account_log',    $account_log);
	$smarty->assign('pager',          $pager);
	$smarty->display('user_clips.dwt');
}
/* 对会员余额申请的处理 */
elseif ($action == 'act_account')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    if ($amount <= 0)
    {
        show_message($_LANG['amount_gt_zero']);//請在「金額」欄輸入大於0的數字
    }

    /* 变量初始化 */
    $surplus = array(
            'user_id'      => $user_id,
            'rec_id'       => !empty($_POST['rec_id'])      ? intval($_POST['rec_id'])       : 0,
            'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,
            'payment_id'   => isset($_POST['payment_id'])   ? intval($_POST['payment_id'])   : 0,
            'user_note'    => isset($_POST['user_note'])    ? trim($_POST['user_note'])      : '',
			'is_dl'        => isset($_POST['dl_type'])      ? intval($_POST['dl_type'])      : 0,
            'amount'       => $amount
    );

    /* 退款申请的处理 */
    if ($surplus['process_type'] == 1)
    {
        /* 判断是否有足够的余额的进行退款的操作 */
       // $sur_amount = get_user_surplus($user_id);
    	$sur_amount = get_user_surplus_dlfc($user_id);
    	if ($amount > $sur_amount)
        {
            $content = $_LANG['surplus_amount_error'];//您要申請提現的金額超過了您現有的餘額，此操作將不可進行！
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }

        //插入会员账目明细
        $amount = '-'.$amount;
        $surplus['payment'] = '提現獎金HKD$ '.$amount;
        $surplus['rec_id']  = insert_user_account($surplus, $amount);

        /* 如果成功提交 */
        if ($surplus['rec_id'] > 0)
        {
            $content = $_LANG['surplus_appl_submit'];//您的提現申請已成功提交，請等待管理員的審核！
            show_message($content, $_LANG['back_account_log'], 'user.php?act=account_log', 'info');
        }
        else
        {
            $content = $_LANG['process_false'];//此次操作失敗，請返回重試！
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }
    }
    /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
    else
    {
        if ($surplus['payment_id'] <= 0)
        {
            show_message($_LANG['select_payment_pls']);//請選擇支付方式
        }

        include_once(ROOT_PATH .'includes/lib_payment.php');

        //获取支付方式名称
        $payment_info = array();
        $payment_info = payment_info($surplus['payment_id']);
        $surplus['payment'] = $payment_info['pay_name'];

        if ($surplus['rec_id'] > 0)
        {
            //更新会员账目明细
            $surplus['rec_id'] = update_user_account($surplus);
        }
        else
        {
            //插入会员账目明细
            $surplus['rec_id'] = insert_user_account($surplus, $amount);
        }

        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号, 不足的时候补0
        $order = array();
        $order['order_sn']       = $surplus['rec_id'];
        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $amount;

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $amount + $payment_info['pay_fee'];

        //记录支付log
        $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type=PAY_SURPLUS, 0);

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  'HKD $'.$amount);
        $smarty->assign('order',   $order);
        $smarty->display('user_transaction.dwt');
    }
}

/* 删除会员余额 */
elseif ($action == 'cancel')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }

    $result = del_user_account($id, $user_id);
    if ($result)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }
}

/* 会员通过帐目明细列表进行再付款的操作 */
elseif ($action == 'pay')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');

    //变量初始化
    $surplus_id = isset($_GET['id'])  ? intval($_GET['id'])  : 0;
    $payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

    if ($surplus_id == 0)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }

    //如果原来的支付方式已禁用或者已删除, 重新选择支付方式
    if ($payment_id == 0)
    {
        ecs_header("Location: user.php?act=account_deposit&id=".$surplus_id."\n");
        exit;
    }

    //获取单条会员帐目信息
    $order = array();
    $order = get_surplus_info($surplus_id);

    //支付方式的信息
    $payment_info = array();
    $payment_info = payment_info($payment_id);

    /* 如果当前支付方式没有被禁用，进行支付的操作 */
    if (!empty($payment_info))
    {
        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号
        $order['order_sn'] = $surplus_id;

        //获取需要支付的log_id
        $order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);

        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $order['amount'];

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $order['surplus_amount'] + $payment_info['pay_fee'];

        //如果支付费用改变了，也要相应的更改pay_log表的order_amount
        $order_amount = $db->getOne("SELECT order_amount FROM " .$ecs->table('pay_log')." WHERE log_id = '$order[log_id]'");
        if ($order_amount <> $order['order_amount'])
        {
            $db->query("UPDATE " .$ecs->table('pay_log').
                       " SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
        }

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('order',   $order);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($order['surplus_amount'], false));
        $smarty->assign('action',  'act_account');
        $smarty->display('user_transaction.dwt');
    }
    /* 重新选择支付方式 */
    else
    {
        include_once(ROOT_PATH . 'includes/lib_clips.php');

        $smarty->assign('payment', get_online_payment_list());
        $smarty->assign('order',   $order);
        $smarty->assign('action',  'account_deposit');
        $smarty->display('user_transaction.dwt');
    }
}

/* 添加标签(ajax) */
elseif ($action == 'add_tag')
{
    include_once('includes/cls_json.php');
    include_once('includes/lib_clips.php');

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tag    = isset($_POST['tag']) ? json_str_iconv(trim($_POST['tag'])) : '';

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['tag_anonymous'];
    }
    else
    {
        add_tag($id, $tag); // 添加tag
        clear_cache_files('goods'); // 删除缓存

        /* 重新获得该商品的所有缓存 */
        $arr = get_tags($id);

        foreach ($arr AS $row)
        {
            $result['content'][] = array('word' => htmlspecialchars($row['tag_words']), 'count' => $row['tag_count']);
        }
    }

    $json = new JSON;

    echo $json->encode($result);
    exit;
}

/* 添加收藏商品(ajax) */
elseif ($action == 'collect')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '');
    $goods_id = $_GET['id'];

    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }
    else
    {
        /* 检查是否已经存在于用户的收藏夹 */
        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('collect_goods') .
            " WHERE user_id='$_SESSION[user_id]' AND goods_id = '$goods_id'";
        if ($GLOBALS['db']->GetOne($sql) > 0)
        {
            $result['error'] = 1;
            $result['message'] = $GLOBALS['_LANG']['collect_existed'];
            die($json->encode($result));
        }
        else
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";

            if ($GLOBALS['db']->query($sql) === false)
            {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['db']->errorMsg();
                die($json->encode($result));
            }
            else
            {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['collect_success'];
                die($json->encode($result));
            }
        }
    }
}

/* 删除收藏商品(ajax) */
elseif ($action == 'del_collect'){
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '');
    $goods_id = $_GET['id'];
    $is_collection = $_GET['is_collection'];

    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }
    else{
        $sql="delete from ".$GLOBALS['ecs']->table('collect_goods')." where user_id =".$_SESSION['user_id']." and goods_id =".$goods_id;

        if($GLOBALS['db']->query($sql)){
            $result['error'] = 0;
            $result['message'] = "商品收藏刪除成功！";
            die($json->encode($result));
        }
    }
}

/* 添加收藏商品(ajax) */
elseif ($action == 'del_collect'){
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '');
    $goods_id = $_GET['id'];
}

/* 删除留言 */
elseif ($action == 'del_msg')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);

    if ($id > 0)
    {
        $sql = 'SELECT user_id, message_img FROM ' .$ecs->table('feedback'). " WHERE msg_id = '$id' LIMIT 1";
        $row = $db->getRow($sql);
        if ($row && $row['user_id'] == $user_id)
        {
            /* 验证通过，删除留言，回复，及相应文件 */
            if ($row['message_img'])
            {
                @unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/'. $row['message_img']);
            }
            $sql = "DELETE FROM " .$ecs->table('feedback'). " WHERE msg_id = '$id' OR parent_id = '$id'";
            $db->query($sql);
        }
    }
    ecs_header("Location: user.php?act=message_list&order_id=$order_id\n");
    exit;
}

/* 删除评论 */
elseif ($action == 'del_cmt')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id > 0)
    {
        $sql = "DELETE FROM " .$ecs->table('comment'). " WHERE comment_id = '$id' AND user_id = '$user_id'";
        $db->query($sql);
    }
    ecs_header("Location: user.php?act=comment_list\n");
    exit;
}

/* 合并订单 */
elseif ($action == 'merge_order')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    include_once(ROOT_PATH .'includes/lib_order.php');
    $from_order = isset($_POST['from_order']) ? trim($_POST['from_order']) : '';
    $to_order   = isset($_POST['to_order']) ? trim($_POST['to_order']) : '';
    if (merge_user_order($from_order, $to_order, $user_id))
    {
        show_message($_LANG['merge_order_success'],$_LANG['order_list_lnk'],'user.php?act=order_list&type=order_list', 'info');
    }
    else
    {
        $err->show($_LANG['order_list_lnk']);
    }
}
/* 将指定订单中商品添加到购物车 */
elseif ($action == 'return_to_cart')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    $json = new JSON();

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if ($order_id == 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['order_id_empty'];
        die($json->encode($result));
    }

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }

    /* 检查订单是否属于该用户 */
    $order_user = $db->getOne("SELECT user_id FROM " .$ecs->table('order_info'). " WHERE order_id = '$order_id'");
    if (empty($order_user))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }
    else
    {
        if ($order_user != $user_id)
        {
            $result['error'] = 1;
            $result['message'] = $_LANG['no_priv'];
            die($json->encode($result));
        }
    }

    $message = return_to_cart($order_id);

    if ($message === true)
    {
        $result['error'] = 0;
        $result['message'] = $_LANG['return_to_cart_success'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }

}
/* 编辑使用預存款余额支付的处理 */
elseif ($action == 'act_edit_surplus_dl')
{
	/* 检查是否登录 */
	if ($_SESSION['user_id'] <= 0)
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
	/* 检查余额 */
	$surplus = floatval($_POST['surplus_dl']);
	if ($surplus <= 0)
	{
		$err->add($_LANG['error_surplus_invalid']);
		$err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	include_once(ROOT_PATH . 'includes/lib_order.php');
	
	/* 取得订单 */
	$order = order_info($order_id);
	if (empty($order))
	{
		ecs_header("Location: ./\n");
		exit;
	}
	
	/* 检查订单用户跟当前用户是否一致 */
	if ($_SESSION['user_id'] != $order['user_id'])
	{
		ecs_header("Location: ./\n");
		exit;
	}
	
	/* 检查订单是否未付款，检查应付款金额是否大于0 */
	if ($order['pay_status'] != PS_UNPAYED || $order['dl_surplus_no'] <= 0)
	{
		$err->add($_LANG['error_order_is_paid']);
		$err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	
	/* 计算应付款金额（减去支付费用） */
	$order['order_amount'] -= $order['pay_fee'];
	
	/* 余额是否超过了应付款金额，改为应付款金额 */
	if ($surplus > $order['order_amount'])
	{
		$surplus = $order['order_amount'];
	}
	
	/* 取得用户信息 */
	$user = user_info($_SESSION['user_id']);
	
	/* 用户帐户余额是否足够 */
	if ($surplus > $user['dl_money'] )
	{
		$err->add($_LANG['error_surplus_not_enough']);
		$err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	
	$order['dl_surplus'] += $surplus;
	$order['order_amount'] -= $surplus;
	$order['dl_surplus_no'] -= $surplus;
	if ($order['order_amount'] > 0)
	{
		$cod_fee = 0;
		if ($order['shipping_id'] > 0)
		{
			$regions  = array($order['country'], $order['province'], $order['city'], $order['district']);
			$shipping = shipping_area_info($order['shipping_id'], $regions);
			if ($shipping['support_cod'] == '1')
			{
				$cod_fee = $shipping['pay_fee'];
			}
		}
	
		$pay_fee = 0;
		if ($order['pay_id'] > 0)
		{
			$pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
		}
	
		$order['pay_fee'] = $pay_fee;
		$order['order_amount'] += $pay_fee;
	}
	
	/* 如果全部支付，设为已确认、已付款 */
	if ($order['order_amount'] == 0)
	{
		if ($order['order_status'] == OS_UNCONFIRMED)
		{
			$order['order_status'] = OS_CONFIRMED;
			$order['confirm_time'] = gmtime();
		}
		$order['pay_status'] = PS_PAYED;
		$order['pay_time'] = gmtime();
	}
	$order = addslashes_deep($order);
	update_order($order_id, $order);
	
	/* 更新用户余额 *///这里需要修改预存款的余额
	$change_desc = sprintf($_LANG['pay_order_by_surplus'].'減少加盟金', $order['order_sn']);
	log_account_change_dl($user['user_id'], (-1) * $surplus, 0, 0, 0, $change_desc);
	
	/* 跳转 */
	ecs_header('Location: user.php?act=order_detail&order_id=' . $order_id . "\n");
	exit;
}

/* 编辑使用獎金余额支付的处理 */
elseif ($action == 'act_edit_surplus_dlfc')
{
	/* 检查是否登录 */
	if ($_SESSION['user_id'] <= 0)
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
	/* 检查余额 */
	$surplus = floatval($_POST['surplus_dlfc']);
	if ($surplus <= 0)
	{
		$err->add($_LANG['error_surplus_invalid']);
		$err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	include_once(ROOT_PATH . 'includes/lib_order.php');

	/* 取得订单 */
	$order = order_info($order_id);
	if (empty($order))
	{
		ecs_header("Location: ./\n");
		exit;
	}

	/* 检查订单用户跟当前用户是否一致 */
	if ($_SESSION['user_id'] != $order['user_id'])
	{
		ecs_header("Location: ./\n");
		exit;
	}

	/* 检查订单是否未付款，检查应付款金额是否大于0 */
	if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0)
	{
		$err->add($_LANG['error_order_is_paid']);
		$err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}

	/* 计算应付款金额（减去支付费用） */
	$order['order_amount'] -= $order['pay_fee'];

	//$surplus 只是港幣   $order['order_amount']可能含有港幣和其他貨幣結合
	$order_amount = $order['order_amount'] ;
	$order_amount = huo_lu_order($_SESSION['area_rate_id'],4)*$order_amount;  //剩下部分轉成港幣
	/* 余额是否超过了应付款金额，改为应付款金额 */
	if ($surplus > $order_amount)
	{
		$surplus = $order_amount;
	}

	/* 取得用户信息 */
	$user = user_info($_SESSION['user_id']);

	/* 用户帐户余额是否足够 */
	if ($surplus > $user['dlfcmoney'] )
	{
		$err->add($_LANG['error_surplus_not_enough']);
		$err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	$change_surplus = 0;
	if($order['dl_surplus_no'] >= $surplus)//代理商品沒給的錢大於輸入的數
	{
		$dlfcmoney_hk = $surplus;
		$order['dlfcmoney_hk'] +=  $dlfcmoney_hk;
		$order['dl_surplus_no'] -= $dlfcmoney_hk;
		$order['order_amount'] -= $dlfcmoney_hk;
		$change_surplus = $dlfcmoney_hk;
	}else 
	{
		$surplus = $surplus - $order['dl_surplus_no']; //減去代理未給錢部分，剩餘部分準備給其他商品的錢
		$order['order_amount'] -=$order['dl_surplus_no'];//減去代理商品的數，總數減
		$order['dlfcmoney_hk'] += $order['dl_surplus_no'];//使用了多少獎金，在代理商品上
		$order['dl_surplus_no'] = 0;
		$change_surplus = $order['dl_surplus_no'];
		if($order['order_amount']>0)//剩下其他非代理商品還沒給的錢
		{
			$order_amount = $order['order_amount'] ;
			$order_amount = huo_lu_order($_SESSION['area_rate_id'],4)*$order_amount;  //剩下部分轉成港幣
			if($order_amount<=$surplus)//剩餘部分小於剩下要給的錢，把這部分錢的數替換成剩餘數
			{
				$surplus = $order_amount;
				$order['dlfcmoney_area_hk']  += round($surplus);
				$order['dlfcmoney_area_qt']  += $order['order_amount'];
				$order['fdl_surplus_no'] = 0;
				$order['order_amount'] = 0;  //給完錢了
				$change_surplus = round($change_surplus+$surplus);
			}else //剩餘部分還不夠給非代理的商品情況
			{
				$surplus_qt = huo_lu_order(4,$_SESSION['area_rate_id'])*$surplus;
				$order['order_amount'] -= round($surplus_qt);
				$order['dlfcmoney_area_hk'] += round($surplus);
				$order['dlfcmoney_area_qt'] += round($surplus_qt);
				$order['fdl_surplus_no'] -= round($surplus_qt);
				$change_surplus = round($change_surplus+$surplus);
			}
		}
		
	}
	
	
	
	/*$order['dl_surplus'] += $surplus;
	$order['order_amount'] -= $surplus;
	$order['dl_surplus_no'] -= $surplus;*/
	if ($order['order_amount'] > 0)
	{
		$cod_fee = 0;
		if ($order['shipping_id'] > 0)
		{
			$regions  = array($order['country'], $order['province'], $order['city'], $order['district']);
			$shipping = shipping_area_info($order['shipping_id'], $regions);
			if ($shipping['support_cod'] == '1')
			{
				$cod_fee = $shipping['pay_fee'];
			}
		}

		$pay_fee = 0;
		if ($order['pay_id'] > 0)
		{
			$pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
		}

		$order['pay_fee'] = $pay_fee;
		$order['order_amount'] += $pay_fee;
	}

	/* 如果全部支付，设为已确认、已付款 */
	if ($order['order_amount'] == 0)
	{
		if ($order['order_status'] == OS_UNCONFIRMED)
		{
			$order['order_status'] = OS_CONFIRMED;
			$order['confirm_time'] = gmtime();
		}
		$order['pay_status'] = PS_PAYED;
		$order['pay_time'] = gmtime();
	}
	
	$order = addslashes_deep($order);
	update_order($order_id, $order);

	/* 更新用户余额 *///这里需要修改预存款的余额
	$change_desc = sprintf($_LANG['pay_order_by_surplus'].'減少獎金', $order['order_sn']);
	log_account_change_dlfc($user['user_id'], (-1) * $change_surplus, 0, 0, 0, $change_desc);

	/* 跳转 */
	ecs_header('Location: user.php?act=order_detail&order_id=' . $order_id . "\n");
	exit;
}
/* 编辑使用余额支付的处理 */
elseif ($action == 'act_edit_surplus')
{
    /* 检查是否登录 */
    if ($_SESSION['user_id'] <= 0)
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

    /* 检查余额 */
    $surplus = floatval($_POST['surplus']);
    if ($surplus <= 0)
    {
        $err->add($_LANG['error_surplus_invalid']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    include_once(ROOT_PATH . 'includes/lib_order.php');

    /* 取得订单 */
    $order = order_info($order_id);
    if (empty($order))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单用户跟当前用户是否一致 */
    if ($_SESSION['user_id'] != $order['user_id'])
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单是否未付款，检查应付款金额是否大于0 */
    if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0)
    {
        $err->add($_LANG['error_order_is_paid']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    /* 计算应付款金额（减去支付费用） */
    $order['order_amount'] -= $order['pay_fee'];

    /* 余额是否超过了应付款金额，改为应付款金额 */
    if ($surplus > $order['order_amount'])
    {
        $surplus = $order['order_amount'];
    }

    /* 取得用户信息 */
    $user = user_info($_SESSION['user_id']);

    /* 用户帐户余额是否足够 */
    if ($surplus > $user['user_money'] + $user['credit_line'])
    {
        $err->add($_LANG['error_surplus_not_enough']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }
    
    /* 修改订单，重新计算支付费用 */
    $order['surplus'] += $surplus;
    $order['order_amount'] -= $surplus;
    $order['fdl_surplus_no'] -= $surplus;
    if ($order['order_amount'] > 0)
    {
        $cod_fee = 0;
        if ($order['shipping_id'] > 0)
        {
            $regions  = array($order['country'], $order['province'], $order['city'], $order['district']);
            $shipping = shipping_area_info($order['shipping_id'], $regions);
            if ($shipping['support_cod'] == '1')
            {
                $cod_fee = $shipping['pay_fee'];
            }
        }

        $pay_fee = 0;
        if ($order['pay_id'] > 0)
        {
            $pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
        }

        $order['pay_fee'] = $pay_fee;
        $order['order_amount'] += $pay_fee;
    }

    /* 如果全部支付，设为已确认、已付款 */
    if ($order['order_amount'] == 0)
    {
        if ($order['order_status'] == OS_UNCONFIRMED)
        {
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = gmtime();
        }
        $order['pay_status'] = PS_PAYED;
        $order['pay_time'] = gmtime();
    }
    $order = addslashes_deep($order);
    update_order($order_id, $order);

    /* 更新用户余额 */
    $change_desc = sprintf($_LANG['pay_order_by_surplus'], $order['order_sn']);
    log_account_change($user['user_id'], (-1) * $surplus, 0, 0, 0, $change_desc);

    /* 跳转 */
    ecs_header('Location: user.php?act=order_detail&order_id=' . $order_id . "\n");
    exit;
}

/* 编辑使用余额支付的处理 */
elseif ($action == 'act_edit_payment')
{
    /* 检查是否登录 */
    if ($_SESSION['user_id'] <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查支付方式 */
    $pay_id = intval($_POST['pay_id']);
    if ($pay_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    include_once(ROOT_PATH . 'includes/lib_order.php');
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

    /* 检查订单用户跟当前用户是否一致 */
    if ($_SESSION['user_id'] != $order['user_id'])
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变*/
    if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id)
    {
        ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
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
    ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
    exit;
}

/* 保存订单详情收货地址 */
elseif ($action == 'save_order_address')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    $address = array(
        'consignee' => isset($_POST['consignee']) ? trim($_POST['consignee'])  : '',
        'email'     => isset($_POST['email'])     ? trim($_POST['email'])      : '',
        'address'   => isset($_POST['address'])   ? trim($_POST['address'])    : '',
        'zipcode'   => isset($_POST['zipcode'])   ? make_semiangle(trim($_POST['zipcode'])) : '',
        'tel'       => isset($_POST['tel'])       ? trim($_POST['tel'])        : '',
        'mobile'    => isset($_POST['mobile'])    ? trim($_POST['mobile'])     : '',
        'sign_building' => isset($_POST['sign_building']) ? trim($_POST['sign_building']) : '',
        'best_time' => isset($_POST['best_time']) ? trim($_POST['best_time'])  : '',
        'order_id'  => isset($_POST['order_id'])  ? intval($_POST['order_id']) : 0
        );
    if (save_order_address($address, $user_id))
    {
        ecs_header('Location: user.php?act=order_detail&order_id=' .$address['order_id']. "\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list&type=order_list');
    }
}

/* 我的现金卷列表 */
elseif ($action == 'bonus')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('user_bonus'). " WHERE user_id = '$user_id'");

    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);
    $bonus = get_user_bouns_list($user_id, $pager['size'], $pager['start']);
    $smarty->assign('helps',      get_shop_help());
    $smarty->assign('pager', $pager);
    $smarty->assign('bonus', $bonus);
    $smarty->display('user_transaction.dwt');
}

/* 我的团购列表 */
elseif ($action == 'group_buy')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    //待议
    $smarty->display('user_transaction.dwt');
}

/* 团购订单详情 */
elseif ($action == 'group_buy_detail')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    //待议
    $smarty->display('user_transaction.dwt');
}

// 用户推荐页面
elseif ($action == 'affiliate')
{
    $goodsid = intval(isset($_REQUEST['goodsid']) ? $_REQUEST['goodsid'] : 0);
    if(empty($goodsid))
    {
        //我的推荐页面

        $page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
        $size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

        empty($affiliate) && $affiliate = array();

        if(empty($affiliate['config']['separate_by']))
        {
            //推荐注册分成
            $affdb = array();
            $num = count($affiliate['item']);
            $up_uid = "'$user_id'";
            $all_uid = "'$user_id'";
            for ($i = 1 ; $i <=$num ;$i++)
            {
                $count = 0;
                if ($up_uid)
                {
                    $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
                    $query = $db->query($sql);
                    $up_uid = '';
                    while ($rt = $db->fetch_array($query))
                    {
                        $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                        if($i < $num)
                        {
                            $all_uid .= ", '$rt[user_id]'";
                        }
                        $count++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $affiliate['item'][$i-1]['level_point'];
                $affdb[$i]['money'] = $affiliate['item'][$i-1]['level_money'];
            }
            $smarty->assign('affdb', $affdb);

            $sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o".
        " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
        " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";

            $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)".
                    " ORDER BY order_id DESC" ;

            /*
                SQL解释：

                订单、用户、分成记录关联
                一个订单可能有多个分成记录

                1、订单有效 o.user_id > 0
                2、满足以下之一：
                    a.直接下线的未分成订单 u.parent_id IN ($all_uid) AND o.is_separate = 0
                        其中$all_uid为该ID及其下线(不包含最后一层下线)
                    b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

            */

            $affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_register_all'], $affiliate['config']['level_register_up'], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
        }
        else
        {
            //推荐订单分成
            $sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";


            $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" .
                    " ORDER BY order_id DESC" ;

            /*
                SQL解释：

                订单、用户、分成记录关联
                一个订单可能有多个分成记录

                1、订单有效 o.user_id > 0
                2、满足以下之一：
                    a.订单下线的未分成订单 o.parent_id = '$user_id' AND o.is_separate = 0
                    b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

            */

            $affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));

        }

        $count = $db->getOne($sqlcount);

        $max_page = ($count> 0) ? ceil($count / $size) : 1;
        if ($page > $max_page)
        {
            $page = $max_page;
        }

        $res = $db->SelectLimit($sql, $size, ($page - 1) * $size);
        $logdb = array();
        while ($rt = $GLOBALS['db']->fetchRow($res))
        {
            if(!empty($rt['suid']))
            {
                //在affiliate_log有记录
                if($rt['separate_type'] == -1 || $rt['separate_type'] == -2)
                {
                    //已被撤销
                    $rt['is_separate'] = 3;
                }
            }
            $rt['order_sn'] = substr($rt['order_sn'], 0, strlen($rt['order_sn']) - 5) . "***" . substr($rt['order_sn'], -2, 2);
            $logdb[] = $rt;
        }

        $url_format = "user.php?act=affiliate&page=";

        $pager = array(
                    'page'  => $page,
                    'size'  => $size,
                    'sort'  => '',
                    'order' => '',
                    'record_count' => $count,
                    'page_count'   => $max_page,
                    'page_first'   => $url_format. '1',
                    'page_prev'    => $page > 1 ? $url_format.($page - 1) : "javascript:;",
                    'page_next'    => $page < $max_page ? $url_format.($page + 1) : "javascript:;",
                    'page_last'    => $url_format. $max_page,
                    'array'        => array()
                );
        for ($i = 1; $i <= $max_page; $i++)
        {
            $pager['array'][$i] = $i;
        }

        $smarty->assign('url_format', $url_format);
        $smarty->assign('pager', $pager);


        $smarty->assign('affiliate_intro', $affiliate_intro);
        $smarty->assign('affiliate_type', $affiliate['config']['separate_by']);

        $smarty->assign('logdb', $logdb);
    }
    else
    {
        //单个商品推荐
        $smarty->assign('userid', $user_id);
        $smarty->assign('goodsid', $goodsid);

        $types = array(1,2,3,4,5);
        $smarty->assign('types', $types);

        $goods = get_goods_info($goodsid);
        $shopurl = $ecs->url();
        $goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? $shopurl . $goods['goods_img'] : $goods['goods_img'];
        $goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? $shopurl . $goods['goods_thumb'] : $goods['goods_thumb'];
        $goods['shop_price'] = price_format($goods['shop_price']);

        $smarty->assign('goods', $goods);
    }

    $smarty->assign('shopname', $_CFG['shop_name']);
    $smarty->assign('userid', $user_id);
    $smarty->assign('shopurl', $ecs->url());
    $smarty->assign('logosrc', 'themes/' . $_CFG['template'] . '/images/logo.gif');

    $smarty->display('user_clips.dwt');
}

//首页邮件订阅ajax操做和验证操作
elseif ($action =='email_list')
{
    $job = $_GET['job'];

    if($job == 'add' || $job == 'del')
    {
        if(isset($_SESSION['last_email_query']))
        {
            if(time() - $_SESSION['last_email_query'] <= 30)
            {
                die($_LANG['order_query_toofast']);
            }
        }
        $_SESSION['last_email_query'] = time();
    }

    $email = trim($_GET['email']);
    $email = htmlspecialchars($email);

    if (!is_email($email))
    {
        $info = sprintf($_LANG['email_invalid'], $email);
        die($info);
    }
    $ck = $db->getRow("SELECT * FROM " . $ecs->table('email_list') . " WHERE email = '$email'");
    if ($job == 'add')
    {
        if (empty($ck))
        {
            $hash = substr(md5(time()), 1, 10);
            $rate_id= 4;
            $sql = "INSERT INTO " . $ecs->table('email_list') . " (email, stat, hash,rate_id) VALUES ('$email', 0, '$hash',$rate_id)";
            $db->query($sql);
            $info = $_LANG['email_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1,$rate_id);
        }
        elseif ($ck['stat'] == 1)
        {
            $info = sprintf($_LANG['email_alreadyin_list'], $email);
        }
        else
        {
            $hash = substr(md5(time()),1 , 10);
            $sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $info = $_LANG['email_re_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        die($info);
    }
    elseif ($job == 'del')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_notin_list'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            $hash = substr(md5(time()),1,10);
            $sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $info = $_LANG['email_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        die($info);
    }
    elseif ($job == 'add_check')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_notin_list'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            $info = $_LANG['email_checked'];
        }
        else
        {
            if ($_GET['hash'] == $ck['hash'])
            {
                $sql = "UPDATE " . $ecs->table('email_list') . "SET stat = 1 WHERE email = '$email'";
                $db->query($sql);
                $info = $_LANG['email_checked'];
            }
            else
            {
                $info = $_LANG['hash_wrong'];
            }
        }
        show_message($info, $_LANG['back_home_lnk'], 'index.php');
    }
    elseif ($job == 'del_check')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_invalid'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            if ($_GET['hash'] == $ck['hash'])
            {
                $sql = "DELETE FROM " . $ecs->table('email_list') . "WHERE email = '$email'";
                $db->query($sql);
                $info = $_LANG['email_canceled'];
            }
            else
            {
                $info = $_LANG['hash_wrong'];
            }
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        show_message($info, $_LANG['back_home_lnk'], 'index.php');
    }
}

/* ajax 发送验证邮件 */
elseif ($action == 'send_hash_mail')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    include_once(ROOT_PATH .'includes/lib_passport.php');
    $json = new JSON();
	
    $result = array('error' => 0, 'message' => '', 'content' => '');

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }

    if (send_regiter_hash($user_id))
    {
        $result['message'] = $_LANG['validate_mail_ok'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $GLOBALS['err']->last_message();
    }

    die($json->encode($result));
}
else if ($action == 'track_packages')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH .'includes/lib_order.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $orders = array();

    $sql = "SELECT order_id,order_sn,invoice_no,shipping_id FROM " .$ecs->table('order_info').
            " WHERE user_id = '$user_id' AND shipping_status = '" . SS_SHIPPED . "'";
    $res = $db->query($sql);
    $record_count = 0;
    while ($item = $db->fetch_array($res))
    {
        $shipping   = get_shipping_object($item['shipping_id']);

        if (method_exists ($shipping, 'query'))
        {
            $query_link = $shipping->query($item['invoice_no']);
        }
        else
        {
            $query_link = $item['invoice_no'];
        }

        if ($query_link != $item['invoice_no'])
        {
            $item['query_link'] = $query_link;
            $orders[]  = $item;
            $record_count += 1;
        }
    }
    $pager  = get_pager('user.php', array('act' => $action), $record_count, $page);
    $smarty->assign('pager',  $pager);
    $smarty->assign('orders', $orders);
    $smarty->display('user_transaction.dwt');
}
else if ($action == 'order_query')
{
    $_GET['order_sn'] = trim(substr($_GET['order_sn'], 1));
    $order_sn = empty($_GET['order_sn']) ? '' : addslashes($_GET['order_sn']);
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();

    $result = array('error'=>0, 'message'=>'', 'content'=>'');

    if(isset($_SESSION['last_order_query']))
    {
        if(time() - $_SESSION['last_order_query'] <= 10)
        {
            $result['error'] = 1;
            $result['message'] = $_LANG['order_query_toofast'];
            die($json->encode($result));
        }
    }
    $_SESSION['last_order_query'] = time();

    if (empty($order_sn))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['invalid_order_sn'];
        die($json->encode($result));
    }

    $sql = "SELECT order_id, order_status, shipping_status, pay_status, ".
           " shipping_time, shipping_id, invoice_no, user_id ".
           " FROM " . $ecs->table('order_info').
           " WHERE order_sn = '$order_sn' LIMIT 1";

    $row = $db->getRow($sql);
    if (empty($row))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['invalid_order_sn'];
        die($json->encode($result));
    }

    $order_query = array();
    $order_query['order_sn'] = $order_sn;
    $order_query['order_id'] = $row['order_id'];
    $order_query['order_status'] = $_LANG['os'][$row['order_status']] . ',' . $_LANG['ps'][$row['pay_status']] . ',' . $_LANG['ss'][$row['shipping_status']];

    if ($row['invoice_no'] && $row['shipping_id'] > 0)
    {
        $sql = "SELECT shipping_code FROM " . $ecs->table('shipping') . " WHERE shipping_id = '$row[shipping_id]'";
        $shipping_code = $db->getOne($sql);
        $plugin = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';
        if (file_exists($plugin))
        {
            include_once($plugin);
            $shipping = new $shipping_code;
            $order_query['invoice_no'] = $shipping->query((string)$row['invoice_no']);
        }
        else
        {
            $order_query['invoice_no'] = (string)$row['invoice_no'];
        }
    }

    $order_query['user_id'] = $row['user_id'];
    /* 如果是匿名用户显示发货时间 */
    if ($row['user_id'] == 0 && $row['shipping_time'] > 0)
    {
        $order_query['shipping_date'] = local_date($GLOBALS['_CFG']['date_format'], $row['shipping_time']);
    }
    $smarty->assign('order_query',    $order_query);
    $result['content'] = $smarty->fetch('library/order_query.lbi');
    die($json->encode($result));
}
elseif ($action == 'transform_points')
{
    $rule = array();
    if (!empty($_CFG['points_rule']))
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    $cfg = array();
    if (!empty($_CFG['integrate_config']))
    {
        $cfg = unserialize($_CFG['integrate_config']);
        $_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0])? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
        $_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0])? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
    }
    $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users')  . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    if ($_CFG['integrate_code'] == 'ucenter')
    {
        $exchange_type = 'ucenter';
        $to_credits_options = array();
        $out_exchange_allow = array();
        foreach ($rule as $credit)
        {
            $out_exchange_allow[$credit['appiddesc'] . '|' . $credit['creditdesc'] . '|' . $credit['creditsrc']] = $credit['ratio'];
            if (!array_key_exists($credit['appiddesc']. '|' .$credit['creditdesc'], $to_credits_options))
            {
                $to_credits_options[$credit['appiddesc']. '|' .$credit['creditdesc']] = $credit['title'];
            }
        }
        $smarty->assign('selected_org', $rule[0]['creditsrc']);
        $smarty->assign('selected_dst', $rule[0]['appiddesc']. '|' .$rule[0]['creditdesc']);
        $smarty->assign('descreditunit', $rule[0]['unit']);
        $smarty->assign('orgcredittitle', $_LANG['exchange_points'][$rule[0]['creditsrc']]);
        $smarty->assign('descredittitle', $rule[0]['title']);
        $smarty->assign('descreditamount', round((1 / $rule[0]['ratio']), 2));
        $smarty->assign('to_credits_options', $to_credits_options);
        $smarty->assign('out_exchange_allow', $out_exchange_allow);
    }
    else
    {
        $exchange_type = 'other';

        $bbs_points_name = $user->get_points_name();
        $total_bbs_points = $user->get_points($row['user_name']);

        /* 论坛积分 */
        $bbs_points = array();
        foreach ($bbs_points_name as $key=>$val)
        {
            $bbs_points[$key] = array('title'=>$_LANG['bbs'] . $val['title'], 'value'=>$total_bbs_points[$key]);
        }

        /* 兑换规则 */
        $rule_list = array();
        foreach ($rule as $key=>$val)
        {
            $rule_key = substr($key, 0, 1);
            $bbs_key = substr($key, 1);
            $rule_list[$key]['rate'] = $val;
            switch ($rule_key)
            {
                case TO_P :
                    $rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] = $_LANG['pay_points'];
                    break;
                case TO_R :
                    $rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] = $_LANG['rank_points'];
                    break;
                case FROM_P :
                    $rule_list[$key]['from'] = $_LANG['pay_points'];$_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] =$_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    break;
                case FROM_R :
                    $rule_list[$key]['from'] = $_LANG['rank_points'];
                    $rule_list[$key]['to'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    break;
            }
        }
        $smarty->assign('bbs_points', $bbs_points);
        $smarty->assign('rule_list',  $rule_list);
    }
    $smarty->assign('shop_points', $row);
    $smarty->assign('exchange_type',     $exchange_type);
    $smarty->assign('action',     $action);
    $smarty->assign('lang',       $_LANG);
    $smarty->display('user_transaction.dwt');
}
elseif ($action == 'act_transform_points')
{
    $rule_index = empty($_POST['rule_index']) ? '' : trim($_POST['rule_index']);
    $num = empty($_POST['num']) ? 0 : intval($_POST['num']);


    if ($num <= 0 || $num != floor($num))
    {
        show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }

    $num = floor($num); //格式化为整数

    $bbs_key = substr($rule_index, 1);
    $rule_key = substr($rule_index, 0, 1);

    $max_num = 0;

    /* 取出用户数据 */
    $sql = "SELECT user_name, user_id, pay_points, rank_points FROM " . $ecs->table('users') . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    $bbs_points = $user->get_points($row['user_name']);
    $points_name = $user->get_points_name();

    $rule = array();
    if ($_CFG['points_rule'])
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    list($from, $to) = explode(':', $rule[$rule_index]);

    $max_points = 0;
    switch ($rule_key)
    {
        case TO_P :
            $max_points = $bbs_points[$bbs_key];
            break;
        case TO_R :
            $max_points = $bbs_points[$bbs_key];
            break;
        case FROM_P :
            $max_points = $row['pay_points'];
            break;
        case FROM_R :
            $max_points = $row['rank_points'];
    }

    /* 检查积分是否超过最大值 */
    if ($max_points <=0 || $num > $max_points)
    {
        show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points' );
    }

    switch ($rule_key)
    {
        case TO_P :
            $result_points = floor($num * $to / $from);
            $user->set_points($row['user_name'], array($bbs_key=>0 - $num)); //调整论坛积分
            log_account_change($row['user_id'], 0, 0, 0, $result_points, $_LANG['transform_points'], ACT_OTHER);
            show_message(sprintf($_LANG['to_pay_points'],  $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');

        case TO_R :
            $result_points = floor($num * $to / $from);
            $user->set_points($row['user_name'], array($bbs_key=>0 - $num)); //调整论坛积分
            log_account_change($row['user_id'], 0, 0, $result_points, 0, $_LANG['transform_points'], ACT_OTHER);
            show_message(sprintf($_LANG['to_rank_points'], $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');

        case FROM_P :
            $result_points = floor($num * $to / $from);
            log_account_change($row['user_id'], 0, 0, 0, 0-$num, $_LANG['transform_points'], ACT_OTHER); //调整商城积分
            $user->set_points($row['user_name'], array($bbs_key=>$result_points)); //调整论坛积分
            show_message(sprintf($_LANG['from_pay_points'], $num, $result_points,  $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');

        case FROM_R :
            $result_points = floor($num * $to / $from);
            log_account_change($row['user_id'], 0, 0, 0-$num, 0, $_LANG['transform_points'], ACT_OTHER); //调整商城积分
            $user->set_points($row['user_name'], array($bbs_key=>$result_points)); //调整论坛积分
            show_message(sprintf($_LANG['from_rank_points'], $num, $result_points, $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
    }
}
elseif ($action == 'act_transform_ucenter_points')
{
    $rule = array();
    if ($_CFG['points_rule'])
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    $shop_points = array(0 => 'rank_points', 1 => 'pay_points');
    $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users')  . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    $exchange_amount = intval($_POST['amount']);
    $fromcredits = intval($_POST['fromcredits']);
    $tocredits = trim($_POST['tocredits']);
    $cfg = unserialize($_CFG['integrate_config']);
    if (!empty($cfg))
    {
        $_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0])? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
        $_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0])? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
    }
    list($appiddesc, $creditdesc) = explode('|', $tocredits);
    $ratio = 0;

    if ($exchange_amount <= 0)
    {
        show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    if ($exchange_amount > $row[$shop_points[$fromcredits]])
    {
        show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    foreach ($rule as $credit)
    {
        if ($credit['appiddesc'] == $appiddesc && $credit['creditdesc'] == $creditdesc && $credit['creditsrc'] == $fromcredits)
        {
            $ratio = $credit['ratio'];
            break;
        }
    }
    if ($ratio == 0)
    {
        show_message($_LANG['exchange_deny'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    $netamount = floor($exchange_amount / $ratio);
    include_once(ROOT_PATH . './includes/lib_uc.php');
    $result = exchange_points($row['user_id'], $fromcredits, $creditdesc, $appiddesc, $netamount);
    if ($result === true)
    {
        $sql = "UPDATE " . $ecs->table('users') . " SET {$shop_points[$fromcredits]}={$shop_points[$fromcredits]}-'$exchange_amount' WHERE user_id='{$row['user_id']}'";
        $db->query($sql);
        $sql = "INSERT INTO " . $ecs->table('account_log') . "(user_id, {$shop_points[$fromcredits]}, change_time, change_desc, change_type)" . " VALUES ('{$row['user_id']}', '-$exchange_amount', '". gmtime() ."', '" . $cfg['uc_lang']['exchange'] . "', '98')";
        $db->query($sql);
        show_message(sprintf($_LANG['exchange_success'], $exchange_amount, $_LANG['exchange_points'][$fromcredits], $netamount, $credit['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    else
    {
        show_message($_LANG['exchange_error_1'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
}
/* 清除商品浏览历史 */
elseif ($action == 'clear_history')
{
    setcookie('ECS[history]',   '', 1);
}
function is_telephone($phone){
	$chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/";
	if (preg_match($chars, $phone)){
		return true;
	}
}

//判断代理是否填写全资料
function add_dlimage($user_id)
{
	$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$user_id;
	$users = $GLOBALS['db']->getRow($sql);
	if($users['EnglishName']==''&&$users['ChineseName']=='')
	{
		return false;
	}
	if($users['sfz_number']=='')
	{
		return false;
	}
	if($users['office_phone']==''&&$users['home_phone']==''&&$users['mobile_phone']=='')
	{
		return false;
	}
	$sql = "SELECT content FROM ".$GLOBALS['ecs']->table('reg_extend_info')." WHERE user_id=".$user_id." AND reg_field_id=10";
	$weixin = $GLOBALS['db']->getOne($sql);
	if(empty($weixin))
	{
		return false;
	}
	return true;
}

// 基础采集 wuxurong
function curl_main($url) {
	$curl = curl_init ();
	// 设置你需要抓取的URL
	curl_setopt ( $curl, CURLOPT_URL, $url );
	// 设置header
	curl_setopt ( $curl, CURLOPT_HEADER, 1 );
	// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
	curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
	// 禁用后cURL将终止从服务端进行验证。使用CURLOPT_CAINFO选项设置证书使用CURLOPT_CAPATH选项设置证书目录 如果CURLOPT_SSL_VERIFYPEER(默认值为2)被启用，CURLOPT_SSL_VERIFYHOST需要被设置成TRUE否则设置为FALSE。
	curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false );
	// 1 检查服务器SSL证书中是否存在一个公用名(common name)。译者注：公用名(Common Name)一般来讲就是填写你将要申请SSL证书的域名 (domain)或子域名(sub domain)。2 检查公用名是否存在，并且是否与提供的主机名匹配。
	curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, false );
	// 模拟浏览器
	curl_setopt ( $curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)" );
	// 运行cURL，请求网页
	$data = curl_exec ( $curl );
	// 关闭URL请求
	curl_close ( $curl );
	// 显示获得的数据
	return $data;
}
function login_facebook()
{
	include_once('./phpsdk5/autoload.php');

	$loginUrl='';

$fb = new Facebook\Facebook([
  'app_id' => '1397762930546864', // Replace {app-id} with your app id
  'app_secret' => '01fddb435e8190d6ead0ba4939340ea2',
  'default_graph_version' => 'v2.9',
  ]);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['email']; // Optional permissions
$loginUrl = $helper->getLoginUrl('https://www.icmarts.com/user.php?act=fboath_login&type=facebook', $permissions);
return $loginUrl;
}
?>