<?php

/**
 * ECSHOP 购物流程
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: yehuaixiao $
 * $Id: flow.php 17218 2011-01-24 04:10:41Z yehuaixiao $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

require(ROOT_PATH . 'includes/lib_order.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

if (!isset($_REQUEST['step']))
{
    $_REQUEST['step'] = "cart";
}



/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

assign_template();
assign_dynamic('flow');


$sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";

$ad_hd_ban = $db->getRow($sql);
if($ad_hd_ban)
{
	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
}
$smarty->assign('ad_hd_ban', $ad_hd_ban);

//facebook 统计代码
$sql = " SELECT * FROM ".$ecs->table("shop_config")." WHERE code='facebook' ";
$facebook_code = $db->getRow($sql);
$facebook_code['value'] = str_replace('PageView', 'AddToCart', $facebook_code['value']);
$smarty->assign('facebook_code', $facebook_code);
//facebook 统计代码

$position = assign_ur_here(0, $_LANG['shopping_flow']);
$smarty->assign('page_title',       $position['title']);    // 页面标题
$smarty->assign('ur_here',          $position['ur_here']);  // 当前位置
$smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
$smarty->assign('categories',       get_categories_tree()); // 分类树
$smarty->assign('helps',            get_shop_help());       // 网店帮助
$smarty->assign('lang',             $_LANG);
$smarty->assign('show_marketprice', $_CFG['show_marketprice']);
$smarty->assign('data_dir',    DATA_DIR);       // 数据目录
$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());

/*------------------------------------------------------ */
//-- 添加商品到购物车
/*------------------------------------------------------ */
if ($_REQUEST['step'] == 'add_to_cart')
{
    include_once('includes/cls_json.php');
   
    $_POST['goods']=strip_tags(urldecode($_POST['goods']));
    $_POST['goods'] = json_str_iconv($_POST['goods']);

   
    
    if (!empty($_REQUEST['goods_id']) && empty($_POST['goods']))
    {
        if (!is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0)
        {
            ecs_header("Location:./\n");
        }
        $goods_id = intval($_REQUEST['goods_id']);
        exit;
    }

    $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
    $json  = new JSON;

    if (empty($_POST['goods']))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }

    $goods = $json->decode($_POST['goods']);
	
    /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
    if (empty($goods->spec) AND empty($goods->quick))
    {
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ".
            "g.goods_attr_id, g.attr_value, g.attr_price " .
        'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
        "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " .
        'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';

        $res = $GLOBALS['db']->getAll($sql);

        if (!empty($res))
        {
            $spe_arr = array();
            foreach ($res AS $row)
            {
                $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                $spe_arr[$row['attr_id']]['name']     = $row['attr_name'];
                $spe_arr[$row['attr_id']]['attr_id']     = $row['attr_id'];
                $spe_arr[$row['attr_id']]['values'][] = array(
                                                            'label'        => $row['attr_value'],
                                                            'price'        => $row['attr_price'],
                                                            'format_price' => price_format($row['attr_price'], false),
                                                            'id'           => $row['goods_attr_id']);
            }
            $i = 0;
            $spe_array = array();
            foreach ($spe_arr AS $row)
            {
                $spe_array[]=$row;
            }
            $result['error']   = ERR_NEED_SELECT_ATTR;
            $result['goods_id'] = $goods->goods_id;
            $result['parent'] = $goods->parent;
            $result['message'] = $spe_array;

            die($json->encode($result));
        }
    }

    /* 更新：如果是一步购物，先清空购物车 */
    if ($_CFG['one_step_buy'] == '1')
    {
        clear_cart();
    }

    /* 检查：商品数量是否合法 */
    if (!is_numeric($goods->number) || intval($goods->number) <= 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['invalid_number'];
    }
    /* 更新：购物车 */
    else
    {
    	
    	if($goods->type){
            $goods_id_text = $goods->goods_id;
            $goods_spec_text = $goods->spec; //配件属性数组
           
            $goods_id_list = explode(",",$goods_id_text);
            if (addto_cart($goods->parent, 1, $goods->parentspec, 0)) //插入基件
            {
            	if ($_CFG['cart_confirm'] > 2)
            	{
            		$result['message'] = '';
            	}
            	else
            	{
            		$result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            	}
            }
            else
            {
            
            	$result['message']  = $err->last_message();
            	$result['error']    = $err->error_no;
            	$result['goods_id'] = stripslashes($goods->goods_id);
            
            }
            foreach($goods_id_list as $key=>$v){ //循环插入配件
                if($v){
                    if (addto_cart($v, $goods->number, $goods_spec_text[$key], $goods->parent))
                    {
                    	
                        if ($_CFG['cart_confirm'] > 2)
                        {
                            $result['message'] = '';
                        }
                        else
                        {
                            $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
                        }
                    }
                    else
                    {

                        $result['message']  = $err->last_message();
                        $result['error']    = $err->error_no;
                        $result['goods_id'] = stripslashes($goods->goods_id);
                        
                    }
                }
            }
           
            $result['content'] = insert_cart_info();
            $result['content1'] = insert_cart_order(array('id'=>'left'));
            $result['content2'] = insert_cart_order(array('id'=>'right'));
            $result['one_step_buy'] = $_CFG['one_step_buy'];
        }
        else{
        	
            // 更新：添加到购物车
            if (addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent))
            {
                if ($_CFG['cart_confirm'] > 2)
                {
                    $result['message'] = '';
                }
                else
                {
                    $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
                }

                $result['content'] = insert_cart_info();
                $result['content1'] = insert_cart_order(array('id'=>'left'));
                $result['content2'] = insert_cart_order(array('id'=>'right'));
                $result['one_step_buy'] = $_CFG['one_step_buy'];

            }
            else
            {

                $result['message']  = $err->last_message();
                $result['error']    = $err->error_no;
                $result['goods_id'] = stripslashes($goods->goods_id);
                if (is_array($goods->spec))
                {
                    $result['product_spec'] = implode(',', $goods->spec);
                }
                else
                {
                    $result['product_spec'] = $goods->spec;
                }
            }
        }
    }

    $result['confirm_type'] = !empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    if(isset($goods->sty))
    {
    	$result['sty'] = $goods->sty;//直接入購物車
    }
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'link_buy')
{
    $goods_id = intval($_GET['goods_id']);

    if (!cart_goods_exists($goods_id,array()))
    {
        addto_cart($goods_id);
    }
    ecs_header("Location:./flow.php\n");
    exit;
}
elseif ($_REQUEST['step'] == 'login')
{
    include_once('languages/'. $_CFG['lang']. '/user.php');

    if($_SESSION['user_id'] > 0)
    {
    	ecs_header("Location: flow.php?step=checkout\n");
    	exit;
    }
	$smarty->assign('facebookurl', login_facebook());
	
	
    /*
     * 用户登录注册
     */
    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        $smarty->assign('anonymous_buy', $_CFG['anonymous_buy']);

        /* 检查是否有赠品，如果有提示登录后重新选择赠品 */
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' AND is_gift > 0";
        if ($db->getOne($sql) > 0)
        {
            $smarty->assign('need_rechoose_gift', 1);
        }

        /* 检查是否需要注册码 */
        $captcha = intval($_CFG['captcha']);
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
        {
            $smarty->assign('enabled_login_captcha', 1);
            $smarty->assign('rand', mt_rand());
        }
        if ($captcha & CAPTCHA_REGISTER)
        {
            $smarty->assign('enabled_register_captcha', 1);
            $smarty->assign('rand', mt_rand());
        }
    }
    else
    {
        include_once('includes/lib_passport.php');
        if (!empty($_POST['act']) && $_POST['act'] == 'signin')
        {
            $captcha = intval($_CFG['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
            {
                if (empty($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }

                /* 检查验证码 */
                include_once('includes/cls_captcha.php');

                $validator = new captcha();
                $validator->session_word = 'captcha_login';
                if (!$validator->check_word($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }
            }

            if ($user->login($_POST['username'], $_POST['password'],isset($_POST['remember'])))
            {
                update_user_info();  //更新用户信息
                recalculate_price(); // 重新计算购物车中的商品价格

                /* 检查购物车中是否有商品 没有商品则跳转到首页 */
                $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') . " WHERE session_id = '" . SESS_ID . "' ";
                if ($db->getOne($sql) > 0)
                {
                    ecs_header("Location: flow.php?step=checkout\n");
                }
                else
                {
                    ecs_header("Location:index.php\n");
                }

                exit;
            }
            else
            {
                $_SESSION['login_fail']++;
                show_message($_LANG['signin_failed'], '', 'flow.php?step=login');
            }
        }
        elseif (!empty($_POST['act']) && $_POST['act'] == 'signup')
        {
            if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
            {
                if (empty($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }

                /* 检查验证码 */
                include_once('includes/cls_captcha.php');

                $validator = new captcha();
                if (!$validator->check_word($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }
            }

            if (register(trim($_POST['username']), trim($_POST['password']), trim($_POST['email'])))
            {
                /* 用户注册成功 */
                ecs_header("Location: flow.php?step=consignee\n");
                exit;
            }
            else
            {
                $err->show();
            }
        }
        else
        {
            // TODO: 非法访问的处理
        }
    }
}
elseif ($_REQUEST['step'] == 'consignee')
{
	
    /*------------------------------------------------------ */
    //-- 收货人信息
    /*------------------------------------------------------ */
    include_once('includes/lib_transaction.php');
    include_once('languages/'. $_CFG['lang']. '/shopping_flow.php');

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        /*
         * 收货人信息填写界面
         */

        if (isset($_REQUEST['direct_shopping']))
        {
            $_SESSION['direct_shopping'] = 1;
        }
        $consignee = array();
        if(isset($_REQUEST['id'])) {
            if (empty($_REQUEST['id'])) {
                $consignee = $_SESSION['flow_consignee'];
            } else {
                $sql = "SELECT * from" . $GLOBALS['ecs']->table('user_address') . " where address_id =" . $_REQUEST['id'] . " and user_id=" . $_SESSION['user_id'];
                $consignee = $GLOBALS['db']->getRow($sql);
            }
            $consignee['tel']= explode(' ',$consignee['tel']);
            if(count($consignee['tel'])==2)
            {
            	$consignee['tel'] =  $consignee['tel'][1];
            }else
            {
            	$consignee['tel'] =  $consignee['tel'][0];
            }
            
            $smarty->assign('consignee',       $consignee);
        }
        /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
        $smarty->assign('country_list',       get_regions());
        $smarty->assign('shop_country',       $_CFG['shop_country']);
        $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

    if(isset($consignee['dqcontent']))
		{
			$smarty->assign('dq_rate',$consignee['dqcontent']);
			
		}else 
		{
			$smarty->assign('dq_rate',$_SESSION['area_rate_id']);
			
		}
        $smarty->assign('email', $_SESSION['email']);
        $arelist = $db->getAll("SELECT areaid,areaname FROM ".$ecs->table('area')." WHERE AreaExchangeRateId=1   AND state=1  ");
        $smarty->assign('arealist', $arelist); //选择哪个店发货
        /* 取得每个收货地址的省市区列表 */
        $province_list = array();
        $city_list = array();
        $district_list = array();
        
        if(!empty($consignee)) {

            $consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 0;
            $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
            $consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : 0;

            $province_list[$region_id] = get_regions(1, $consignee['country']);
            $city_list[$region_id] = get_regions(2, $consignee['province']);
            $district_list[$region_id] = get_regions(3, $consignee['city']);
            
            $sql="select * from ".$ecs->table('shipping_sf')." where three_id = '".$consignee['city']."' and sf_name <> '順豐站' and sf_name <> '服務中心' ";
            
            $sf_list = $db->getAll($sql);
            $fds = '';
            foreach ($sf_list as $key=>$value)
            {
            	$fds .='<input type="radio"  value="'.$value['id'].'" name="fsf" >'.$value['sf_name'].' 點碼:'.$value['code'].' '.$value['address']
            	.'<font style="color:red;">('.$value['businesshours'].') </font><br />';
            }
            if($fds=='')
            {
            	$fds ='該地區暫時無自取點';
            }
            $sql="select * from ".$ecs->table('shipping_sf')." where three_id = '".$consignee['city']."' and sf_name in('順豐站','服務中心')";
            
            $sf_lists = $db->getAll($sql);
            $ds = '';
            foreach ($sf_lists as $key=>$value)
            {
            	$ds .='<input type="radio"  value="'.$value['id'].'" name="sf" >'.$value['sf_name'].' 點碼:'.$value['code'].' '.$value['address']
            	.'<font style="color:red;">('.$value['businesshours'].') </font><br />';
            }
            if($ds=='')
            {
            	$ds ='該地區暫時無自取點';
            }
        }
        $smarty->assign('fds', $fds);
        $smarty->assign('ds', $ds);
       
        $consignee['tel']= explode(' ',$consignee['tel']);
        if(count($consignee['tel'])==2)
        {
        	$consignee['tel'] =  $consignee['tel'][1];
        }else
        {
        	$consignee['tel'] =  $consignee['tel'][0];
        }
        
        
        $address_type = 2;
        if($_SESSION['flow_consignee']['fsf']>0)//重新編輯順豐要帶回數據顯示
        {
        	$address_type = 3;
        }else if($_SESSION['flow_consignee']['sf']>0)
        {
        	$address_type = 1;
        }else if($_SESSION['flow_consignee']['areaname']>0)
        {
        	$address_type = 4;
        }
		
        $smarty->assign('address_type', $address_type);
        $smarty->assign('province_list', $province_list);
        $smarty->assign('city_list',     $city_list);
        $smarty->assign('district_list', $district_list);
		
        /* 返回收货人页面代码 */
        $smarty->assign('real_goods_count', exist_real_goods(0, $flow_type) ? 1 : 0);
       
    }
    else
    {
    	
    	
    	
    	/*增加選擇順豐自取點功能*/
        /*
         * 保存收货人信息
         */
        $consignee = array(
            'address_id'    => empty($_POST['address_id']) ? 0  : intval($_POST['address_id']),
            'consignee'     => empty($_POST['consignee'])  ? '' : trim($_POST['consignee']),
            'country'       => empty($_POST['country'])    ? '' : $_POST['country'],
            'province'      => empty($_POST['province'])   ? '' : $_POST['province'],
            'city'          => empty($_POST['city'])       ? '' : $_POST['city'],
            'district'      => empty($_POST['district'])   ? '' : $_POST['district'],
            'email'         => empty($_POST['email'])      ? ' ' : $_POST['email'],
            'address'       => empty($_POST['address'])    ? '' : $_POST['address'],
            'zipcode'       => empty($_POST['zipcode'])    ? '' : make_semiangle(trim($_POST['zipcode'])),
            'tel'           => empty($_POST['tel'])        ? '' : make_semiangle(trim($_POST['tel'])),
            'mobile'        => empty($_POST['mobile'])     ? '' : make_semiangle(trim($_POST['mobile'])),
        	'dqcontent'        => empty($_POST['dqcontent'])     ? '' : make_semiangle(trim($_POST['dqcontent'])),
            'sign_building' => empty($_POST['sign_building']) ? '' : $_POST['sign_building'],
            'best_time'     => empty($_POST['best_time'])  ? '' : $_POST['best_time'],
        );
        $consignee['tel'] = str_replace(' ', '', $consignee['tel']);
        $consignee['mobile'] = str_replace(' ', '', $consignee['mobile']);

        /*增加選擇順豐自取點功能*/
        if(isset($_POST['shdz'])&&$_POST['shdz']==1)//順豐站
        {
        	$sql = " select * from ".$GLOBALS['ecs']->table('shipping_sf')." where id=".$_POST['sf'];
        	$sf_one =  $GLOBALS['db']->getRow($sql);
        	$consignee['sf'] = $_POST['sf'];
        	$consignee['fsf'] = -1;
        	$consignee['areaname'] = null;
        }else if(isset($_POST['shdz'])&&$_POST['shdz']==2)//地址
        {
        	$consignee['fsf'] = -1;
        	$consignee['sf'] = -1;
        	$consignee['areaname'] = null;
        }else if(isset($_POST['shdz'])&&$_POST['shdz']==3)//順豐自取點
        {
        	$sql = " select * from ".$GLOBALS['ecs']->table('shipping_sf')." where id=".$_POST['fsf'];
        	$sf_two =  $GLOBALS['db']->getRow($sql);
        	$consignee['fsf'] = $_POST['fsf'];
        	$consignee['sf'] = -1; 
        	$consignee['areaname'] = null;
        }else if(isset($_POST['shdz'])&&$_POST['shdz']==4)//门店地址111111111111111
        {
        	$consignee['areaname'] = $_POST['areaname'];
        	$consignee['fsf'] = -1;
        	$consignee['sf'] = -1;
        }
        
        if ($_SESSION['user_id'] > 0)
        {
            include_once(ROOT_PATH . 'includes/lib_transaction.php');

            /* 如果用户已经登录，则保存收货人信息 */
            $consignee['user_id'] = $_SESSION['user_id'];

            $add_id = save_consignee($consignee, true);
            $consignee['address_id'] = $add_id;
        }
      
        /* 保存到session */
        $_SESSION['flow_consignee'] = stripslashes_deep($consignee);

        ecs_header("Location: flow.php?step=checkout\n");
        exit;
    }
   
}
elseif ($_REQUEST['step'] == 'select_consignee'){

    $id = intval($_REQUEST['id']);

    $sql = "select * from ".$GLOBALS['ecs']->table('user_address')." where address_id=".$id;
    $consignee = $GLOBALS['db']->getRow($sql);
    $_SESSION['flow_consignee'] = stripslashes_deep($consignee);

    ecs_header("Location: flow.php?step=checkout\n");
    exit;
}
elseif ($_REQUEST['step'] == 'select_sf')
{
	include('includes/cls_json.php');
	//11111111111111111111111111111
	$json   = new JSON;
	$res    = array();
	
	/*順豐快遞自取點列表*/
		//var_dump($_SESSION['flow_consignee']);die;
		$se_sf = trim($_REQUEST['se_sf']);
		$sql="select * from ".$ecs->table('shipping_sf')." where  address like '%".$se_sf."%' or code like '%".$se_sf."%' and sf_name <> '順豐站' and sf_name <> '服務中心'  ";
		
		$sf_list = $db->getAll($sql);
		$mess = '';
		foreach ($sf_list as $key=>$value)
		{
			$mess .='<input type="radio"  value="'.$value['id'].'" name="fsf"> '.$value['sf_name'].' 點碼: '.$value['code'].' '.$value['address'].' <font style="color:red;">('.$value['businesshours'].') </font><br />'; 
		}
		$res['mess'] = $mess;
		$res['pand'] = 1;
	
	
	die($json->encode($res));
}
elseif ($_REQUEST['step'] == 'drop_consignee')
{
    /*------------------------------------------------------ */
    //-- 删除收货人信息
    /*------------------------------------------------------ */
    include_once('includes/lib_transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        ecs_header("Location: flow.php?step=consignee\n");
        exit;
    }
    else
    {
        show_message($_LANG['not_fount_consignee']);
    }
}
elseif ($_REQUEST['step'] == 'checkout')
{
    /*------------------------------------------------------ */
    //-- 订单确认
    /*------------------------------------------------------ */
	update_favourable_activity_price();
    //updata_cart_vo();
   
    if(isset($_SESSION['flow_consignee']['areaname'])&&$_SESSION['flow_consignee']['areaname']!=null)
    {
		$arelist = $db->getAll("SELECT areaid,areaname FROM ".$ecs->table('area')." WHERE areaid='".$_SESSION['flow_consignee']['areaname']."'   AND state=1  ");
		$smarty->assign('arealist', $arelist); //选择哪个店发货
    }
    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
  

    /* 团购标志 */
    if ($flow_type == CART_GROUP_BUY_GOODS)
    {
        $smarty->assign('is_group_buy', 1);
    }
    /* 积分兑换商品 */
    elseif ($flow_type == CART_EXCHANGE_GOODS)
    {
        $smarty->assign('is_exchange_goods', 1);
    }
    else
    {
        //正常购物流程  清空其他购物流程情况
        $_SESSION['flow_order']['extension_code'] = '';
    }

    /* 检查购物车中是否有商品 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
        " WHERE session_id = '" . SESS_ID . "' " .
        "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";

    if ($db->getOne($sql) == 0)
    {
        show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
    }

    /*
     * 检查用户是否已经登录
     * 如果用户已经登录了则检查是否有默认的收货地址
     * 如果没有登录则跳转到登录和注册页面
     */
    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
    {
        /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
        ecs_header("Location: flow.php?step=login\n");
        exit;
    }

     $consignee_list = get_consignee_lists($_SESSION['user_id']);
     
    $consignee = get_consignee($_SESSION['user_id']);
    
    foreach ($consignee_list as $key=>$value)
    {
    	if (!check_consignee_info($value, $flow_type))
    	{
    		unset($consignee_list[$key]);
    	}else 
    	{
    		$consignee_list[$key]['country'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['country']."'");
    		
    		$consignee_list[$key]['province'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['province']."'");
    		
    		$consignee_list[$key]['city'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['city']."'");
    		
    		$consignee_list[$key]['district'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['district']."'");
    		
    	}
    	if($consignee['address_id'] == $value['address_id'])
    	{
    		$_SESSION['flow_consignee'] = $value;
    		$consignee_list[$key]['css'] = 1;
    	}else 
    	{
    		//unset($consignee_list[$key]);
    	}
    	
    	
    }
   
    if(count($consignee_list)<=0)
    {
    	/* 如果不完整则转向到收货人信息填写界面 */
    	//ecs_header("Location: flow.php?step=consignee\n");
    	//exit;
    	$consignee_list[0] = $consignee;
    	foreach ($consignee_list as $key=>$value)
    	{
    		if (!check_consignee_info($value, $flow_type))
    		{
    			unset($consignee_list[$key]);
    		}else
    		{
    			$consignee_list[$key]['country'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['country']."'");
    	
    			$consignee_list[$key]['province'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['province']."'");
    	
    			$consignee_list[$key]['city'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['city']."'");
    	
    			$consignee_list[$key]['district'] = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='".$value['district']."'");
    	
    		}
    		if($consignee['address_id'] == $value['address_id'])
    		{
    			$_SESSION['flow_consignee'] = $value;
    			$consignee_list[$key]['css'] = 1;
    		}else
    		{
    			unset($consignee_list[$key]);
    		}    		     		 
    	}
    }
   
    /* 检查收货人信息是否完整
    if (!check_consignee_info($consignee, $flow_type))
    {
       
    } */

    //$_SESSION['flow_consignee'] = $consignee;

    $smarty->assign('consignee', $consignee_list);

    //检查赠品是否够数

    //checkout_song();
   
	
    /* 对是否允许修改购物车赋值 */
    if ($flow_type != CART_GENERAL_GOODS || $_CFG['one_step_buy'] == '1')
    {
        $smarty->assign('allow_edit_cart', 0);
    }
    else
    {
        $smarty->assign('allow_edit_cart', 1);
    }
    
    /*
     * 取得购物流程设置
     */
    $smarty->assign('config', $_CFG);
    /*
     * 取得订单信息
     */
    $order = flow_order_info();
    $smarty->assign('order', $order);

    /* 计算折扣 */
    if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS)
    {
    	
        $discount = compute_discount(2);
        
        $smarty->assign('discount', $discount['discount']);
        $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
        $smarty->assign('your_discount', sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount'])));
    }

   
    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计
    
    $zong_list = array();
    foreach ($cart_goods as $key=>$value)
    {
    	if($value['is_shipping'] == 1)
    	{
    		$shipping_free  =1;
    	}
    
    	if($value['zengsong'] == 0)
    	{
    		/*$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type >2 and ".gmtime()." >= start_time and ".gmtime();
    		 $act_name = $GLOBALS['db']->getAll($sql);
    		$name_tring = '';
    		foreach ($act_name as $k=>$v)
    		{
    		$name_tring = $v['act_name'].' <br/>'.$name_tring;
    		}
    		if(!empty($act_name))
    		{
    		$cart_goods[$key]['act_name'] = '參與'.$name_tring;
    		}else
    		{
    		$cart_goods[$key]['act_name'] = '';
    		}*/
    	}elseif($value['zengsong'] == 3)
    	{
    		if(strstr($value['activity_id'],","))
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =4 and act_id in(".$value['activity_id'].") and ".gmtime()." >= start_time and ".gmtime();
    
    		}else
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =4 and act_id=".$value['activity_id']." and ".gmtime()." >= start_time and ".gmtime();
    
    		}
    		$act_name = $GLOBALS['db']->getAll($sql);
    		$name_tring = '';
    		foreach ($act_name as $k=>$v)
    		{
    			$name_tring = $v['act_name'].' <br/>'.$name_tring;
    		}
    		$cart_goods[$key]['act_name'] = $name_tring.'折扣商品';
    		$cart_goods[$key]['act_name_p'] = 4;
    
    	}
    	else if($value['zengsong'] == 5)
    	{
    		if(strstr($value['activity_id'],","))
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =5  and ".gmtime()." >= start_time and ".gmtime();
    
    		}else
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =5 and act_id=".$value['activity_id']." and ".gmtime()." >= start_time and ".gmtime();
    
    		}
    		$act_name = $GLOBALS['db']->getAll($sql);
    		$name_tring = '';
    		foreach ($act_name as $k=>$v)
    		{
    			$name_tring = $v['act_name'].$name_tring;
    		}
    		if(empty($act_name))
    		{
    			$cart_goods[$key]['act_name'] = '總價';
    
    			if($cart_goods[$key]['extension_code'] <>'package_buy_all')
    			{
    				$cart_goods[$key]['act_name_p'] = 2;
    				$cart_goods[$key]['yuan_shop_price'] = price_format($value['yuan_shop_price']);
    			}else
    			{
    				$cart_goods[$key]['act_name_p'] = 1;
    			}
    		}else
    		{
    
    			$cart_goods[$key]['act_name'] = $name_tring;
    			$cart_goods[$key]['act_name_p'] = 1;
    			if($cart_goods[$key]['extension_code'] <>'package_buy_all')
    			{
    				$cart_goods[$key]['act_name_p'] = 2;
    				$cart_goods[$key]['yuan_shop_price'] = price_format($value['yuan_shop_price']);
    			}else
    			{
    				$cart_goods[$key]['act_name_p'] = 1;
    			}
    		}
    		$zong_list[$key] = $cart_goods[$key];
    		unset($cart_goods[$key]);
    	}else
    	{
    		$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type >2 and ".gmtime()." >= start_time and ".gmtime();
    		$act_name = $GLOBALS['db']->getOne($sql);
    		$cart_goods[$key]['act_name'] = $act_name.'贈品';
    	}
    	
    	
    }
     
    $cart_goods = $cart_goods + $zong_list;
    
    $smarty->assign('goods_list', $cart_goods);
    
    
    /*
     * 计算订单的费用
     */
    $total = order_fee($order, $cart_goods, $consignee);
  
   

    $smarty->assign('total', $total);
    $smarty->assign('shopping_money', sprintf($_LANG['shopping_money'], $total['formated_goods_price']));
    $smarty->assign('market_price_desc', sprintf($_LANG['than_market_price'], $total['formated_market_price'], $total['formated_saving'], $total['save_rate']));


    /* 取得优惠赠品活动 */
    $favourable_list = favourable_list($_SESSION['user_rank']);

    usort($favourable_list, 'cmp_favourable');

    $smarty->assign('favourable_list', $favourable_list);

    $smarty->assign('fuhe',p_activity_son());
/*----------------------------------------------------------------------------------------------------------------------*/
    /* 取得配送列表 */
    $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
    $shipping_list     = available_shipping_list($region,$_SESSION['area_rate_id']);

   
    $cart_weight_price = cart_weight_price($flow_type);
    $insure_disabled   = true;
    $cod_disabled      = true;

    // 查看购物车中是否全为免运费商品，若是则把运费赋为零
    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
    $shipping_count = $db->getOne($sql);
    $moren = 0;
    foreach ($shipping_list AS $key => $val)
    {
        $shipping_cfg = unserialize_config($val['configure']);
        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
        $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
            price_format($val['insure'], false) : $val['insure'];

        
        /* 当前的配送方式是否支持保价 */
        if ($val['shipping_id'] == $order['shipping_id'])
        {
        	if($val['shipping_code'] == 'cac')
        	{
        		$moren =1;
        	}elseif($val['shipping_code'] == 'sto_express'){
                $moren = 2;
            }
            $insure_disabled = ($val['insure'] == 0);
            $cod_disabled    = ($val['support_cod'] == 0);
        }
    }
    $sf_list = array();
    
    /*順豐快遞自取點列表*///根據改版的傳ID進來修改
    if(!empty($region[2]))
    {
    if(isset($_SESSION['flow_consignee']['fsf']))//如果选择自取点。则去掉顺服到付。
    {
    $sql="select * from ".$ecs->table('shipping_sf')." where id = '".$_SESSION['flow_consignee']['fsf']."'";
    
    $sf_list['list'] = $db->getAll($sql);
    }
    if(isset($_SESSION['flow_consignee']['sf'])) 
    {
    	
        $sql="select * from ".$ecs->table('shipping_sf')." where id = '".$_SESSION['flow_consignee']['sf']."' ";
       
        $sf_list['list_sf'] = $db->getAll($sql);
    }
    }
   
    if(isset($_SESSION['flow_consignee']['areaname'])&&$_SESSION['flow_consignee']['areaname']!=null)//没有选门店的。去掉门店选项
    {
    	
    	
    	$smarty->assign('xs_dm',1);
    	//unset($shipping_list[0]);
    	//unset($shipping_list[2]);
    	//unset($shipping_list[3]);
    }else 
    {
    	
    	$smarty->assign('xs_dm',0);
    }
    
    $smarty->assign('sf_list',$sf_list);

	$smarty->assign('areaidp',$_SESSION['area_rate_id']);
    $smarty->assign('moren',$moren);
    $smarty->assign('shipping_list',   $shipping_list);
	
    $smarty->assign('insure_disabled', $insure_disabled);
    $smarty->assign('cod_disabled',    $cod_disabled);
    /*----------------------------------------------------------------------------------------------------------------------*/
    /* 取得支付列表 */
    if ($order['shipping_id'] == 0)
    {
        $cod        = true;
        $cod_fee    = 0;
    }
    else
    {
        $shipping = shipping_info($order['shipping_id']);
        $cod = $shipping['support_cod'];

        if ($cod)
        {
            /* 如果是团购，且保证金大于0，不能使用货到付款 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $group_buy_id = $_SESSION['extension_id'];
                if ($group_buy_id <= 0)
                {
                    show_message('error group_buy_id');
                }
                $group_buy = group_buy_info($group_buy_id);
                if (empty($group_buy))
                {
                    show_message('group buy not exists: ' . $group_buy_id);
                }

                if ($group_buy['deposit'] > 0)
                {
                    $cod = false;
                    $cod_fee = 0;

                    /* 赋值保证金 */
                    $smarty->assign('gb_deposit', $group_buy['deposit']);
                }
            }

            if ($cod)
            {
                $shipping_area_info = shipping_area_info($order['shipping_id'], $region);
                $cod_fee            = $shipping_area_info['pay_fee'];
            }
        }
        else
        {
            $cod_fee = 0;
        }
    }
   
    // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
    $payment_list = available_payment_list(1, $cod_fee);
    
    if(isset($payment_list))
    {
        foreach ($payment_list as $key => $payment)
        {
            if ($payment['is_cod'] == '1')
            {
                $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
            }
            /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
            if ($payment['pay_code'] == 'yeepayszx' && $total['amount'] > 300)
            {
                unset($payment_list[$key]);
            }
            /* 如果有余额支付 */
            if ($payment['pay_code'] == 'balance')
            {
                /* 如果未登录，不显示 */
                if ($_SESSION['user_id'] == 0)
                {
                    unset($payment_list[$key]);
                }
                else
                {
                    if ($_SESSION['flow_order']['pay_id'] == $payment['pay_id'])
                    {
                        $smarty->assign('disable_surplus', 1);
                    }
                }
            }
            if ($payment['pay_code'] == 'mpay'&&$_SESSION['area_rate_id'] <> 1)
            {
            	unset($payment_list[$key]);
            }
            if ($payment['pay_code'] == 'kzs'&&$_SESSION['area_rate_id'] <> 4)
            {
            	unset($payment_list[$key]);
            }
        }
    }
    
   //var_dump($_SESSION);die;
    if($_SESSION['flow_consignee']['fsf']!=-1&&$_SESSION['flow_consignee']['fsf']!=null&&$_SESSION['flow_consignee']['sf']!=null)//如果选择自取点。则去掉顺服到付。
    {
    	
    	unset($payment_list[3]);
    }
    if(isset($_SESSION['flow_consignee']['areaname'])&&$_SESSION['flow_consignee']['areaname']!=null)//没有选门店的。去掉门店选项
    {
    	unset($payment_list[3]);
    }else
    {
    	 
    	
    }
    $smarty->assign('payment_list', $payment_list);
    
    /* 取得包装与贺卡 */
    if ($total['real_goods_count'] > 0)
    {
        /* 只有有实体商品,才要判断包装和贺卡 */
        if (!isset($_CFG['use_package']) || $_CFG['use_package'] == '1')
        {
            /* 如果使用包装，取得包装列表及用户选择的包装 */
            $smarty->assign('pack_list', pack_list());
        }

        /* 如果使用贺卡，取得贺卡列表及用户选择的贺卡 */
        if (!isset($_CFG['use_card']) || $_CFG['use_card'] == '1')
        {
            $smarty->assign('card_list', card_list());
        }
    }


    $user_info = user_info($_SESSION['user_id']);
    /*推荐人 */
    if($_SESSION['user_id'] > 0){
        if(!empty($user_info['parent_id'])){
            $parent['name'] = $db->getOne("select user_name from ".$ecs->table('users')." where user_id = ".$user_info['parent_id']);
            $parent['id'] = $user_info['parent_id'];
        }
        $smarty->assign('parent', $parent);
    }
    $dl=0;//代理使用余额显示
    $su=1;//非代理使用余额显示
    if($_SESSION['user_id'] > 0) {
        $sql = "select r.* from " . $GLOBALS['ecs']->table('users') . " as u, " . $GLOBALS['ecs']->table('user_rank') . " as r where u.user_rank=r.rank_id and u.user_id = " . $_SESSION['user_id'];
        $dl_user = $GLOBALS['db']->getRow($sql);

        if($dl_user['dl_pd']){
            $sql = "select count(*) from " . $GLOBALS['ecs']->table('cart') . " as o," . $GLOBALS['ecs']->table('goods') . " as g where o.goods_id=g.goods_id and g.dl_goods>0 and " . "o.extension_code not like '%package_buy%' and o.session_id = '".SESS_ID."'";
            $dl_num = $GLOBALS['db']->getOne($sql);

            $sql = "select count(*) from ".$GLOBALS['ecs']->table('cart')." where session_id = '".SESS_ID."'";
            $cart_num = $GLOBALS['db']->getOne($sql);

            if($dl_num==$cart_num){
                $dl=1;
                $su=0;
            }elseif($dl_num >0){
                $dl=1;
            }
        }
    }



    /* 如果使用余额，取得用户余额 */
    if ((!isset($_CFG['use_surplus']) || $_CFG['use_surplus'] == '1')
        && $_SESSION['user_id'] > 0 && !empty($su)
        && $user_info['user_money'] > 0)
    {
        // 能使用余额
        $smarty->assign('allow_use_surplus', 1);
        $smarty->assign('your_surplus', price_format($user_info['user_money']));
    }
    /* 如果使用代理余额，取得用户余额 */
    if ((!isset($_CFG['use_surplus']) || $_CFG['use_surplus'] == '1')
        && $_SESSION['user_id'] > 0 && !empty($dl)
        && $user_info['dl_money'] > 0)
    {
        // 能使用余额
        $smarty->assign('allow_use_dl', 1);
        $smarty->assign('dl_surplus', 'HKD $'.$user_info['dl_money']);
    }
    /*如果使用奖金，取得用户奖金*/
    if ((!isset($_CFG['use_surplus']) || $_CFG['use_surplus'] == '1')
    && $_SESSION['user_id'] > 0 && !empty($dl)
    && $user_info['dlfcmoney'] > 0)
    {
    	// 能使用余额
    	$smarty->assign('allow_use_dlfc', 1);
    	
    	$smarty->assign('dlfc_surplus', 'HKD $'.$user_info['dlfcmoney']);
    }
    /* 如果使用积分，取得用户可用积分及本订单最多可以使用的积分 */
    if ((!isset($_CFG['use_integral']) || $_CFG['use_integral'] == '1')
        && $_SESSION['user_id'] > 0
        && $user_info['pay_points'] > 0
        && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
    {
    	
        // 能使用积分
        $smarty->assign('allow_use_integral', 1);
        $smarty->assign('order_max_integral', flow_available_points());  // 可用积分
        $smarty->assign('your_integral',      $user_info['pay_points']); // 用户积分
    }
   
    /* 如果使用现金卷，取得用户可以使用的现金卷及用户选择的现金卷 */    // 卡的原因。。。。。。。。。。。后期解决
    if ((!isset($_CFG['use_bonus']) || $_CFG['use_bonus'] == '1')
        && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
    {
    	
        // 取得用户可用现金卷
        $user_bonus = user_bonus($_SESSION['user_id'], $total['goods_price']);
        /*需要增加对VIP设置判断*/
       
        if (!empty($user_bonus))
        {
            foreach ($user_bonus AS $key => $val)
            {
                $user_bonus[$key]['bonus_money_formated'] = price_format($val['type_money'], false);
            }
           
            
            $smarty->assign('bonus_list', $user_bonus);
        }
        
        // 能使用现金卷
        $smarty->assign('allow_use_bonus', 1);
    }
    
    /* 如果使用缺货处理，取得缺货处理列表 */
    if (!isset($_CFG['use_how_oos']) || $_CFG['use_how_oos'] == '1')
    {
        if (is_array($GLOBALS['_LANG']['oos']) && !empty($GLOBALS['_LANG']['oos']))
        {
            $smarty->assign('how_oos_list', $GLOBALS['_LANG']['oos']);
        }
    }

    /* 如果能开发票，取得发票内容列表 */
    if ((!isset($_CFG['can_invoice']) || $_CFG['can_invoice'] == '1')
        && isset($_CFG['invoice_content'])
        && trim($_CFG['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS)
    {
        $inv_content_list = explode("\n", str_replace("\r", '', $_CFG['invoice_content']));
        $smarty->assign('inv_content_list', $inv_content_list);

        $inv_type_list = array();
        foreach ($_CFG['invoice_type']['type'] as $key => $type)
        {
            if (!empty($type))
            {
                $inv_type_list[$type] = $type . ' [' . floatval($_CFG['invoice_type']['rate'][$key]) . '%]';
            }
        }
        $smarty->assign('inv_type_list', $inv_type_list);
    }

    /* 保存 session */
    $_SESSION['flow_order'] = $order;
}
elseif ($_REQUEST['step'] == 'select_shipping')
{
    /*------------------------------------------------------ */
    //-- 改变配送方式
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods) )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['shipping_id'] = intval($_REQUEST['shipping']);
        $regions = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
        $shipping_info = shipping_area_info($order['shipping_id'], $regions);

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和现金卷 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['cod_fee']     = $shipping_info['pay_fee'];
        if (strpos($result['cod_fee'], '%') === false)
        {
            $result['cod_fee'] = price_format($result['cod_fee'], false);
        }
        $result['need_insure'] = ($shipping_info['insure'] > 0 && !empty($order['need_insure'])) ? 1 : 0;
        $result['content']     = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_insure')
{
    /*------------------------------------------------------ */
    //-- 选定/取消配送的保价
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods)  )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    	
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['need_insure'] = intval($_REQUEST['insure']);//应该把配送ID也传过来。然后再确认保价数

        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        
        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
       
        
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和现金卷 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_payment')
{
    /*------------------------------------------------------ */
    //-- 改变支付方式
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods)  )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    	
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['pay_id'] = intval($_REQUEST['payment']);
        $payment_info = payment_info($order['pay_id']);
        $result['pay_code'] = $payment_info['pay_code'];

        if($order['pay_id'] != 8){
            if($order['shipping_id'] == 2){
                $order['shipping_id'] = 1;
            }
        }

        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和现金卷 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_pack')
{
    /*------------------------------------------------------ */
    //-- 改变商品包装
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods)  )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    	
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['pack_id'] = intval($_REQUEST['pack']);

        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和现金卷 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_card')
{
    /*------------------------------------------------------ */
    //-- 改变贺卡
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods)  )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    	
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['card_id'] = intval($_REQUEST['card']);

        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和现金卷 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $order['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'change_surplus')
{
    /*------------------------------------------------------ */
    //-- 改变余额
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');

    $surplus   = floatval($_GET['surplus']);
    $dl_surplus   = floatval($_GET['dl_surplus']);
    $dlfc_surplus = floatval($_GET['dlfc_surplus']);
    $user_info = user_info($_SESSION['user_id']);

    if ($user_info['user_money'] + $user_info['credit_line'] < $surplus)
    {
        $result['error'] = $_LANG['surplus_not_enough'];
    }
    elseif($user_info['dl_money']<$dl_surplus){
        $result['error1'] = $_LANG['surplus_not_enough'];
    }
    elseif($user_info['dlfcmoney']<$dlfc_surplus){
    	$result['error2'] = $_LANG['surplus_not_enough'];
    	
    }
    else
    {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 获得收货人信息 */
        $consignee = get_consignee($_SESSION['user_id']);

        /* 对商品信息赋值 */
        $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

        if (empty($cart_goods)  )
        {
            $result['error'] = $_LANG['no_goods_in_cart'];

        }elseif (!check_consignee_info($consignee, $flow_type))
        {
            $result['error'] = '沒有填寫收貨地址';

        }
        else
        {
            /* 取得订单信息 */
            $order = flow_order_info();
            $order['surplus'] = $surplus;
            $order['dl_surplus'] = $dl_surplus;
            $order['dlfc_surplus'] = $dlfc_surplus;
            /* 计算订单的费用 */
            $total = order_fee($order, $cart_goods, $consignee);
           
            $smarty->assign('total', $total);
            $result['surplus'] = $total['surplus'];
            $result['dl_surplus'] = $total['dl_surplus'];
            $result['dlfc_surplus'] = $total['dlfc_surplus'];
            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $smarty->assign('is_group_buy', 1);
            }

            $result['content'] = $smarty->fetch('library/order_total.lbi');
        }
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_integral')
{
    /*------------------------------------------------------ */
    //-- 改变积分
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');

    $points    = floatval($_GET['points']);
    $user_info = user_info($_SESSION['user_id']);

    /* 取得订单信息 */
    $order = flow_order_info();

    $flow_points = flow_available_points();  // 该订单允许使用的积分
    $user_points = $user_info['pay_points']; // 用户的积分总数

    if ($points > $user_points)
    {
        $result['error'] = $_LANG['integral_not_enough'];
    }
    elseif ($points > $flow_points)
    {
        $result['error'] = sprintf($_LANG['integral_too_much'], $flow_points);
    }
    else
    {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        $order['integral'] = $points;

        /* 获得收货人信息 */
        $consignee = get_consignee($_SESSION['user_id']);

        /* 对商品信息赋值 */
        $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

        if (empty($cart_goods)  )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    	
    }
        else
        {
            /* 计算订单的费用 */
            $total = order_fee($order, $cart_goods, $consignee);
            $smarty->assign('total',  $total);
            $smarty->assign('config', $_CFG);

            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $smarty->assign('is_group_buy', 1);
            }

            $result['content'] = $smarty->fetch('library/order_total.lbi');
            $result['error'] = '';
        }
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_bonus')
{
    /*------------------------------------------------------ */
    //-- 改变现金卷
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods)  )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    	
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $bonus = bonus_info(intval($_GET['bonus']));

        if ((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || $_GET['bonus'] == 0)
        {
            $order['bonus_id'] = intval($_GET['bonus']);
        }
        else
        {
            $order['bonus_id'] = 0;
            $result['error'] = $_LANG['invalid_bonus'];
        }

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_needinv')
{
    /*------------------------------------------------------ */
    //-- 改变发票的设置
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');
    $json = new JSON();
    $_GET['inv_type'] = !empty($_GET['inv_type']) ? json_str_iconv(urldecode($_GET['inv_type'])) : '';
    $_GET['invPayee'] = !empty($_GET['invPayee']) ? json_str_iconv(urldecode($_GET['invPayee'])) : '';
    $_GET['inv_content'] = !empty($_GET['inv_content']) ? json_str_iconv(urldecode($_GET['inv_content'])) : '';

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods) )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        die($json->encode($result));
    }elseif(!check_consignee_info($consignee, $flow_type))
	{
		$result['error'] = '沒有填寫收貨地址';
        die($json->encode($result));
	}
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        if (isset($_GET['need_inv']) && intval($_GET['need_inv']) == 1)
        {
            $order['need_inv']    = 1;
            $order['inv_type']    = trim(stripslashes($_GET['inv_type']));
            $order['inv_payee']   = trim(stripslashes($_GET['inv_payee']));
            $order['inv_content'] = trim(stripslashes($_GET['inv_content']));
        }
        else
        {
            $order['need_inv']    = 0;
            $order['inv_type']    = '';
            $order['inv_payee']   = '';
            $order['inv_content'] = '';
        }

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        die($smarty->fetch('library/order_total.lbi'));
    }
}
elseif ($_REQUEST['step'] == 'change_oos')
{
    /*------------------------------------------------------ */
    //-- 改变缺货处理时的方式
    /*------------------------------------------------------ */

    /* 取得订单信息 */
    $order = flow_order_info();

    $order['how_oos'] = intval($_GET['oos']);

    /* 保存 session */
    $_SESSION['flow_order'] = $order;
}
elseif ($_REQUEST['step'] == 'check_surplus')
{
    /*------------------------------------------------------ */
    //-- 检查用户输入的余额
    /*------------------------------------------------------ */
    $surplus   = floatval($_GET['surplus']);
    $user_info = user_info($_SESSION['user_id']);

    if (($user_info['user_money'] + $user_info['credit_line'] < $surplus))
    {
        die($_LANG['surplus_not_enough']);
    }

    exit;
}
elseif ($_REQUEST['step'] == 'check_integral')
{
    /*------------------------------------------------------ */
    //-- 检查用户输入的余额
    /*------------------------------------------------------ */
    $points      = floatval($_GET['integral']);
    $user_info   = user_info($_SESSION['user_id']);
    $flow_points = flow_available_points();  // 该订单允许使用的积分
    $user_points = $user_info['pay_points']; // 用户的积分总数

    if ($points > $user_points)
    {
        die($_LANG['integral_not_enough']);
    }

    if ($points > $flow_points)
    {
        die(sprintf($_LANG['integral_too_much'], $flow_points));
    }

    exit;
}

/*------------------------------------------------------ */
//-- 完成所有订单操作，提交到数据库
/*------------------------------------------------------ */
elseif ($_REQUEST['step'] == 'done')
{
    include_once('includes/lib_clips.php');
    include_once('includes/lib_payment.php');

    //添加赠品
    $gift_goods = $_REQUEST['gift'];
    $gift_attr = $_REQUEST['goods_attr'];

    $gift_lsit = array();
    foreach($gift_goods as $k=>$vg){
        foreach($vg as $g){
            $gift_lsit[$g]['goods_id'] = $g;
            $gift_lsit[$g]['act_id'] = $k;
            $gift_lsit[$g]['goods_attr'] = $gift_attr[$k][$g];
        }
    }

    foreach($gift_lsit as $v){
        add_favourable_cart($v);
    }
    
    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 检查购物车中是否有商品 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
        " WHERE session_id = '" . SESS_ID . "' " .
        "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
    if ($db->getOne($sql) == 0)
    {
        show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
    }
    
    //更新购物车参与活动ID
    $sql = "SELECT * FROM ". $ecs->table('cart') .
        " WHERE session_id = '" . SESS_ID . "' " .
        "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'  AND  carta_id<>0";
    $updatecart = $db->getAll($sql);
   
    foreach ($updatecart as $uk=>$uv)
    {
    	$sql = "SELECT act_id FROM ".$ecs->table('cart_activity')." where recs_id in(" . $uv['carta_id'] . ") ";
    	$act_id_list = $db->getAll($sql);
    	$act_id_lists =  array();
    	foreach ($act_id_list as $kk=>$kv)
    	{
    		$act_id_lists[$kk] = $kv['act_id'];
    	}
    	$act_id_lists = array_unique($act_id_lists);
    	$act_id_string = implode(',', $act_id_lists);
    	$carta_id_list = explode(',',$uv['carta_id']);
    	
    	$carta_id_list = array_unique($carta_id_list);
    	$carta_id_string = implode(',', $carta_id_list);
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  activity_id = '$act_id_string' ,carta_id='$carta_id_string' "  .
    	" WHERE rec_id=".$uv['rec_id'];
    	$GLOBALS['db']->query($sql);
    }
    
    //更新购物车参与活动ID
    
    /* 检查商品库存 */
    /* 如果使用库存，且下订单时减库存，则减少库存 
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
    {
        $cart_goods_stock = get_cart_goods();
        $_cart_goods_stock = array();
        foreach ($cart_goods_stock['goods_list'] as $value)
        {
            $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
        }
        flow_cart_stock($_cart_goods_stock);
        unset($cart_goods_stock, $_cart_goods_stock);
    }*/

    /*
     * 检查用户是否已经登录
     * 如果用户已经登录了则检查是否有默认的收货地址
     * 如果没有登录则跳转到登录和注册页面
     */
    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
    {
        /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
        ecs_header("Location: flow.php?step=login\n");
        exit;
    }

    $consignee = get_consignee($_SESSION['user_id']);

    /* 检查收货人信息是否完整 */
    if (!check_consignee_info($consignee, $flow_type))
    {
        /* 如果不完整则转向到收货人信息填写界面 */
        ecs_header("Location: flow.php?step=consignee\n");
        exit;
    }
    if(empty($_POST['payment']))
    {
    	 show_message('請選擇支付方式');
    	 exit;
    }
    $sf_id=0;
    if(isset($_SESSION['flow_consignee']['sf'])&&$_SESSION['flow_consignee']['sf']!=-1)
    {
    	$sf_id=$_SESSION['flow_consignee']['sf'];
    }
    if(isset($_SESSION['flow_consignee']['fsf'])&&$_SESSION['flow_consignee']['fsf']!=-1)
    {
    	$sf_id=$_SESSION['flow_consignee']['fsf'];
    }
    if(!empty($_POST['shipping']))
    {
    	$sql = "SELECT shipping_code FROM ".$ecs->table('shipping')." WHERE shipping_id=".$_POST['shipping'];
    	
    	$code_shipping = $db->getOne($sql);
    	
    	if($code_shipping=='cac'){
            if(empty($_POST['areaname']))
            {
                show_message('請選擇取貨店鋪');
                exit;
            }
        }elseif($code_shipping=='sto_express'){
            //$sf_id=$_POST['sf'];
        }
    }
   
   
    
    $_POST['how_oos'] = isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
    $_POST['card_message'] = isset($_POST['card_message']) ? htmlspecialchars($_POST['card_message']) : '';
    $_POST['inv_type'] = !empty($_POST['inv_type']) ? htmlspecialchars($_POST['inv_type']) : '';
    $_POST['inv_payee'] = isset($_POST['inv_payee']) ? htmlspecialchars($_POST['inv_payee']) : '';
    $_POST['inv_content'] = isset($_POST['inv_content']) ? htmlspecialchars($_POST['inv_content']) : '';
    $_POST['postscript'] = isset($_POST['postscript']) ? htmlspecialchars($_POST['postscript']) : '';

  
   
    $order = array(
        'shipping_id'     => intval($_POST['shipping']),
        'pay_id'          => intval($_POST['payment']),
        'pack_id'         => isset($_POST['pack']) ? intval($_POST['pack']) : 0,
        'card_id'         => isset($_POST['card']) ? intval($_POST['card']) : 0,
        'card_message'    => trim($_POST['card_message']),
        'surplus'         => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
    	'dlfc_surplus'         => isset($_POST['surplus_dlfc']) ? floatval($_POST['surplus_dlfc']) : 0.00,
        'dl_surplus'      => isset($_POST['dl_surplus']) ? floatval($_POST['dl_surplus']) : 0.00,
        'integral'        => isset($_POST['integral']) ? intval($_POST['integral']) : 0,
        'bonus_id'        => isset($_POST['bonus']) ? intval($_POST['bonus']) : 0,
        'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
        'inv_type'        => $_POST['inv_type'],
        'inv_payee'       => trim($_POST['inv_payee']),
        'inv_content'     => $_POST['inv_content'],
        'postscript'      => trim($_POST['postscript']),
        'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
        'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
        'user_id'         => $_SESSION['user_id'],
        'add_time'        => gmtime(),
    	'areaid'        => $_POST['areaname'],
        'order_status'    => OS_UNCONFIRMED,
        'shipping_status' => SS_UNSHIPPED,
        'pay_status'      => PS_UNPAYED,
    	'web_referer'     =>'web',	
    	'cart_rate'       =>$_SESSION['area_rate_id'],
        'agency_id'       => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'])),
        'shipping_sf'     =>$sf_id
        );

    /* 扩展信息 */
    if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS)
    {
        $order['extension_code'] = $_SESSION['extension_code'];
        $order['extension_id'] = $_SESSION['extension_id'];
    }
    else
    {
        $order['extension_code'] = '';
        $order['extension_id'] = 0;
    }

    /* 检查积分余额是否合法 */
    $user_id = $_SESSION['user_id'];
    if ($user_id > 0)
    {
        $user_info = user_info($user_id);

        $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
        if ($order['surplus'] < 0)
        {
            $order['surplus'] = 0;
        }

        // 查询用户有多少积分
        $flow_points = flow_available_points();  // 该订单允许使用的积分
        $user_points = $user_info['pay_points']; // 用户的积分总数

        $order['integral'] = min($order['integral'], $user_points, $flow_points);
        if ($order['integral'] < 0)
        {
            $order['integral'] = 0;
        }
    }
    else
    {
        $order['surplus']  = 0;
        $order['integral'] = 0;
    }

    /* 检查现金卷是否存在 */
    if ($order['bonus_id'] > 0)
    {
        $bonus = bonus_info($order['bonus_id']);

        if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type))
        {
            $order['bonus_id'] = 0;
        }
    }
    elseif (isset($_POST['bonus_sn']))
    {
        $bonus_sn = trim($_POST['bonus_sn']);
        $bonus = bonus_info(0, $bonus_sn);
        $now = gmtime();
        if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type) || $now > $bonus['use_end_date'])
        {
        }
        else
        {
            if ($user_id > 0)
            {
                $sql = "UPDATE " . $ecs->table('user_bonus') . " SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
                $db->query($sql);
            }
            $order['bonus_id'] = $bonus['bonus_id'];
            $order['bonus_sn'] = $bonus_sn;
        }
    }

    /* 订单中的商品 */
    $cart_goods = cart_goods($flow_type);

    if (empty($cart_goods))
    {
        show_message($_LANG['no_goods_in_cart'], $_LANG['back_home'], './', 'warning');
    }

    /* 检查商品总额是否达到最低限购金额 */
    if ($flow_type == CART_GENERAL_GOODS && cart_amount(true, CART_GENERAL_GOODS) < $_CFG['min_goods_amount'])
    {
        show_message(sprintf($_LANG['goods_amount_not_enough'], price_format($_CFG['min_goods_amount'], false)));
    }

    /* 收货人信息 */
    foreach ($consignee as $key => $value)
    {
        $order[$key] = addslashes($value);
    }
    $order['tel'] = $order['dqcontent'].' '.$order['tel'];
   /* 判断是不是实体商品 */
    foreach ($cart_goods AS $val)
    {
        /* 统计实体商品的个数 */
        if ($val['is_real'])
        {
            $is_real_good=1;
        }
    }
    if(isset($is_real_good))
    {
        $sql="SELECT shipping_id FROM " . $ecs->table('shipping') . " WHERE shipping_id=".$order['shipping_id'] ." AND enabled =1"; 
        if(!$db->getOne($sql))
        {
           show_message($_LANG['flow_no_shipping']);
        }
    }
    /* 订单中的总额 */
    $total = order_fee($order, $cart_goods, $consignee);
   
    $dl_pd = 0;
    if($_SESSION['user_id'])
    {
    	$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
    	$order['is_dl_order'] = $GLOBALS['db']->getOne($sql);
    }else 
    {
    	$order['is_dl_order'] = 0;
    }
    $dlfc_total = $total['dlfc_surplus_hk']+$total['dlfc_surplus_qt'];
   $order['dlfcmoney_hk'] = $total['dlfc_surplus_hk'];
    $order['dlfcmoney_area_hk'] = $total['dlfc_surplus_qt'];
    $order['dlfcmoney_area_qt'] = $total['dlfc_surplus_qt_hk'];
    $order['dl_yuantotal'] = $total['amount_dl_formated_cn'];
    $order['fdl_yuantotal'] = $total['amount_fdl_formated_cn'];
    
    $order['dl_surplus'] = $total['dl_surplus'];
    $order['dl_surplus_no'] = $total['amount_dl'];
    $order['fdl_surplus_no'] = $total['amount_fdl'];
    $order['bonus']        = $total['bonus'];
    $order['goods_amount'] = $total['goods_price'];
    $order['discount']     = $total['discount'];
    $order['surplus']      = $total['surplus'];
    $order['tax']          = $total['tax'];

    
    
    
    // 购物车中的商品能享受现金卷支付的总额
    $discount_amout = compute_discount_amount();
    // 现金卷和积分最多能支付的金额为商品总额
    $temp_amout = $order['goods_amount'] - $discount_amout;
    if ($temp_amout <= 0)
    {
        $order['bonus_id'] = 0;
    }

    /* 配送方式 */
    if ($order['shipping_id'] > 0)
    {
        $shipping = shipping_info($order['shipping_id']);
        $order['shipping_name'] = addslashes($shipping['shipping_name']);
    }
    $order['shipping_fee'] = $total['shipping_fee'];
    $order['insure_fee']   = $total['shipping_insure'];

    /* 支付方式 */
    if ($order['pay_id'] > 0)
    {
        $payment = payment_info($order['pay_id']);
        $pay_code = $db->getOne("SELECT pay_code FROM ".$ecs->table('payment')." WHERE pay_id=".intval($_POST['payment']));
        if($pay_code=='bank')
        {
        	$order['pay_name'] = addslashes($payment['pay_name']).'_'.$_SESSION['area_rate_id'];
        }else 
        {
        	$order['pay_name'] = addslashes($payment['pay_name']);
        }
        if($pay_code == 'zfbsm')
        {
        	$order['zfbsm'] = 'https://www.icmarts.com/images/HKAlipay.jpg';
        }
        if($pay_code == 'mpay')
        {
        	$order['zfbsm'] = 'https://www.icmarts.com/images/mpayicmarts.jpg';
        }
        $order['pay_desc'] = addslashes($payment['pay_desc']);
    }
    $order['pay_fee'] = $total['pay_fee'];
    $order['cod_fee'] = $total['cod_fee'];

    /* 商品包装 */
    if ($order['pack_id'] > 0)
    {
        $pack               = pack_info($order['pack_id']);
        $order['pack_name'] = addslashes($pack['pack_name']);
    }
    $order['pack_fee'] = $total['pack_fee'];

    /* 祝福贺卡 */
    if ($order['card_id'] > 0)
    {
        $card               = card_info($order['card_id']);
        $order['card_name'] = addslashes($card['card_name']);
    }
    $order['card_fee']      = $total['card_fee'];

    $order['order_amount']  = number_format($total['amount'], 2, '.', '');

    /* 如果全部使用余额支付，检查余额是否足够 */
    if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0)
    {
        if($order['surplus'] >0) //余额支付里如果输入了一个金额
        {
            $order['order_amount'] = $order['order_amount'] + $order['surplus'];
            $order['surplus'] = 0;
        }
        if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
        {
            show_message($_LANG['balance_not_enough']);
        }
        else
        {
            $order['surplus'] = $order['order_amount'];
            $order['order_amount'] = 0;
        }
    }

    /* 如果订单金额为0（使用余额或积分或现金卷支付），修改订单状态为已确认、已付款 */
    if ($order['order_amount'] <= 0)
    {
        $order['order_status'] = OS_CONFIRMED;
        $order['confirm_time'] = gmtime();
        $order['pay_status']   = PS_PAYED;
        $order['pay_time']     = gmtime();
        $order['order_amount'] = 0;
    }

    $order['integral_money']   = $total['integral_money'];
    $order['integral']         = $total['integral'];

    if ($order['extension_code'] == 'exchange_goods')
    {
        $order['integral_money']   = 0;
        $order['integral']         = $total['exchange_integral'];
    }

    $order['from_ad']          = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
    $order['referer']          = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '本站';

    /* 记录扩展信息 */
    if ($flow_type != CART_GENERAL_GOODS)
    {
        $order['extension_code'] = $_SESSION['extension_code'];
        $order['extension_id'] = $_SESSION['extension_id'];
    }

    //绑定推荐人
    /*if(!empty($_POST['parent_name'])){
        $parent = $db->getRow("select user_id,parent_id,user_rank  from ".$ecs->table('users')." where user_name = '".$_POST['parent_name']."' or email = '".$_POST['parent_name']."' or home_phone='".$_POST['parent_name']."' or mobile_phone='".$_POST['parent_name']."'");
        if(!empty($parent['user_id']) && !empty($user_id)) {
            if(!empty($parent['parent_id'])){
                if($parent['parent_id'] !=$user_id){
                	
                    $sql = "UPDATE " . $ecs->table('users') . " SET parent_id = ".$parent['user_id']." WHERE user_id = $user_id";
                    $db->query($sql);
                }
            }else{
                $sql = "UPDATE " . $ecs->table('users') . " SET parent_id = ".$parent['user_id']." WHERE user_id = $user_id";
                $db->query($sql);
            }
        }
    }*/

    $affiliate = unserialize($_CFG['affiliate']);
    if(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1)
    {
        //推荐订单分成
        if(!empty($_POST['parent_id']) || !empty($_POST['parent_name'])){
            if(!empty($_POST['parent_id'])){
                $parent_id = intval($_POST['parent_id']);
            }
            if(!empty($_POST['parent_name'])){
                $parent_id = $db->getOne("select user_id from ".$ecs->table('users')." where user_name = '".$_POST['parent_name']."' or email = '".$_POST['parent_name']."' or home_phone='".$_POST['parent_name']."' or mobile_phone='".$_POST['parent_name']."'");
            }
        }else{
            $parent_id = get_affiliate();
        }
        if($user_id == $parent_id)
        {
            $parent_id = 0;
        }
    }
    elseif(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0)
    {
        //推荐注册分成
        $parent_id = 0;
    }
    else
    {
        //分成功能关闭
        $parent_id = 0;
    }
    $order['parent_id'] = $parent_id;
    
   
    /* 插入订单表 */
    $error_no = 0;
    do
    {
        $order['order_sn'] = get_order_sn(); //获取新订单号
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'INSERT');

        $error_no = $GLOBALS['db']->errno();

        if ($error_no > 0 && $error_no != 1062)
        {
            die($GLOBALS['db']->errorMsg());
        }
    }
    while ($error_no == 1062); //如果是订单号重复则重新提交数据

    $new_order_id = $db->insert_id();
    $order['order_id'] = $new_order_id;

	$sql = "SELECT * FROM" .$ecs->table('cart'). " WHERE session_id = '".SESS_ID."' and carta_id>0 ";
	
    $list = $db->getAll($sql);
	if(!empty($list))
	{
	    foreach ($list as $key=>$value)
    {
    	
    	$sql = "SELECT * FROM ".$ecs->table('cart_activity')." WHERE recs_id in(".$value['carta_id'].")";
    	$cart_activitys = $db->getAll($sql);
    	$string_c = '';
    	if($value['zengsong']==5)
    	{
    		$sql = "SELECT goods_price FROM ".$ecs->table('cart'). " WHERE session_id = '".SESS_ID."' and goods_price>0 and carta_id=".$value['carta_id'];
    		$price_huo = $db->getOne($sql);
    	}
    	foreach ($cart_activitys as $k=>$v)
    	{
    		if($value['zengsong']==5)
    		{
    			$string_c = $string_c.$v['act_id'].":".$v['buy'].'-'.$price_huo.',';
    		}else 
    		{
    			$string_c = $string_c.$v['act_id'].":".$v['buy'].'-'.$v['song'].',';
    		}
    			
    	}
    	$string_c=substr($string_c,0,-1);
    	$sql = "UPDATE ". $ecs->table('cart') ." SET activity_id='$string_c' WHERE rec_id=".$value['rec_id'];
    	$db->query($sql);
    }
    }
    
    /* 插入订单商品 */
    $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id,suppliers_id,fen_cheng,areaid, extension_id,comfirm_goods_number,fuwu,zengsong,activity_id,activity_p,yi_song) ".
            " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id,suppliers_id,fen_cheng,areaid, extension_id,goods_number,fuwu,zengsong,activity_id, carta_id,package_num".
            " FROM " .$ecs->table('cart') .
            " WHERE session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
    $db->query($sql);
	
    
    $sql = "SELECT * FROM ". $ecs->table('order_goods') . " WHERE order_id =".$new_order_id." AND yi_song>0 ";
    $bc_goods_list = $db->getAll($sql);
    
    foreach ($bc_goods_list as $key=>$value)//借用yi_song存package_num 现在还原保存数组用来更新
    {
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET  yi_song = '' " .
    	" WHERE rec_id=" . $value['rec_id'];//更新划出数量
    	$GLOBALS['db']->query($sql);
    }
    
	 $sql = "SELECT * FROM ". $ecs->table('order_goods') . " WHERE order_id =".$new_order_id." AND zengsong=0 ";
    $goods_list_c = $db->getAll($sql);
    foreach ($goods_list_c as $gk=>$gv)
    {
        if(!empty($gv['activity_p'])) {
            $sql = "SELECT rec_id FROM " . $ecs->table('cart') . " WHERE session_id = '" . SESS_ID . "' AND zengsong=0 AND goods_id=" . $gv['goods_id'] . " AND goods_attr_id='" . $gv['goods_attr_id'] . "' ";
            $cart_id = $db->getOne($sql);
            $sql = "SELECT * FROM " . $ecs->table('cart_activity') . " WHERE session_id = '" . SESS_ID . "'  AND recs_id in(" . $gv['activity_p'] . ") ";
            //查询活动表是否有商品参与活动，要更新到订单货品表里
            $cart_activity_list = $db->getAll($sql);
            $cart_string = '';
            foreach ($cart_activity_list as $cak => $cav) {
                $song_string = unserialize($cav['buy_goods_id']);

                foreach ($song_string as $sk => $sv) {
                    if ($sv['rec_id'] == $cart_id) {
                        $cart_string = $cart_string . $sv['ysong'] . ',';//链接划出赠送数量字符串
                    }
                }
            }

            $cart_string = substr($cart_string, 0, -1);

            $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET  yi_song = '$cart_string' " .
                " WHERE rec_id=" . $gv['rec_id'];//更新划出数量

            $GLOBALS['db']->query($sql);
			if((!$cart_string||$cart_string==0)&&(!$gv['zengsong']||$gv['zengsong']==0))
            {
            	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET  activity_id = 0,activity_p=0,yi_song=0 " .
            	" WHERE rec_id=" . $gv['rec_id'];//更新划出数量
            	
            	$GLOBALS['db']->query($sql);
            }
        }
    }
    $sql = "SELECT * FROM ". $ecs->table('order_goods') . " WHERE order_id =".$new_order_id." AND zengsong = 3 ";
    $goods_zs = $db->getAll($sql);
    foreach ($goods_zs as $zk=>$zv)
    {
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET  zengsong=2"  . 
    	" WHERE rec_id=".$zv['rec_id'];//更新划出数量
    	 
    	$GLOBALS['db']->query($sql);
    }
    $sql = "SELECT * FROM ". $ecs->table('order_goods') . " WHERE order_id =".$new_order_id." AND zengsong != 0 ";
    $goods_list_c = $db->getAll($sql);
    foreach ($goods_list_c as $gk=>$gv)
    {
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET  yi_song =".$gv['goods_number']  .
    	" WHERE rec_id=".$gv['rec_id'];//更新划出数量    	
    	$GLOBALS['db']->query($sql);
    }
    foreach ($bc_goods_list as $key=>$value)
    {
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . " SET  activity_p=".$value['yi_song'].
    			" WHERE rec_id=" . $value['rec_id'];//
    	$GLOBALS['db']->query($sql);
    }
    $sql = "SELECT * FROM ". $ecs->table('order_goods') . " WHERE order_id =".$new_order_id." AND product_id != 0 ";
    $goods_lis_c = $db->getAll($sql);
    foreach ($goods_lis_c as $key=>$value)
    {
        online_goods($value['goods_id']);
    }

    
    /* 插入订单商品 */
    /*$sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id,suppliers_id,fen_cheng,areaid, extension_id,comfirm_goods_number,fuwu,zengsong,activity_id) ".
            " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id,suppliers_id,fen_cheng,areaid, extension_id,goods_number,fuwu,zengsong,activity_id ".
            " FROM " .$ecs->table('cart') .
            " WHERE session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
    $db->query($sql);*/
    
    
    
    
    /*打印小票*/
    $receipt['receipt_sn'] = $order['order_sn'];
    $receipt['order_id'] = $new_order_id;
    $receipt['return_order_id'] = 0;
	$receipt['add_time'] = gmtime();
    $receipt['discount'] = $discount;
    $receipt['money_paid'] = 0; //价格在付款修改
    $receipt['pay_detail'] = "网站";
    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('receipt'), $receipt, 'INSERT');
    /*打印小票*/
    /* 修改拍卖活动状态 */
    if ($order['extension_code']=='auction')
    {
        $sql = "UPDATE ". $ecs->table('goods_activity') ." SET is_finished='2' WHERE act_id=".$order['extension_id'];
        $db->query($sql);
    }

    /* 处理余额、积分、现金卷 */
    if ($order['user_id'] > 0 && $order['surplus'] > 0)
    {
        log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf($_LANG['pay_order'], $order['order_sn']));
    }
  
    if ($order['user_id'] > 0 && $order['dl_surplus'] > 0)
    {
        $user_moneys = $order['dl_surplus'] * (-1);
        /* 插入帐户变动记录 */
        $account_log = array(
            'user_id'       => $order['user_id'],
            'user_money'    => $user_moneys,
            'frozen_money'  => 0,
            'rank_points'   => 0,
            'pay_points'    => 0,
            'change_time'   => gmtime(),
            'change_desc'   => sprintf($_LANG['pay_order'].',使用加盟金', $order['order_sn']),
            'change_type'   => ACT_OTHER,
        	'dl_use'        =>1
        );
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('account_log'), $account_log, 'INSERT');

        /* 更新用户信息 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET dl_money = dl_money + ('$user_moneys') ". " WHERE user_id = '".$order['user_id']."' LIMIT 1";

        $GLOBALS['db']->query($sql);
    }
    
    if ($order['user_id'] > 0 && $dlfc_total > 0)
    {
    	$user_moneys = $dlfc_total * (-1);
    	/* 插入帐户变动记录 */
    	$account_log = array(
    			'user_id'       => $order['user_id'],
    			'user_money'    => $user_moneys,
    			'frozen_money'  => 0,
    			'rank_points'   => 0,
    			'pay_points'    => 0,
    			'change_time'   => gmtime(),
    			'change_desc'   => sprintf($_LANG['pay_order'].',使用獎金', $order['order_sn']),
    			'change_type'   => ACT_OTHER,
    			'dl_use'        =>1
    	);
    	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('account_log'), $account_log, 'INSERT');
    
    	/* 更新用户信息 */
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET dlfcmoney = dlfcmoney + ('$user_moneys') ". " WHERE user_id = '".$order['user_id']."' LIMIT 1";
    
    	$GLOBALS['db']->query($sql);
    }
            
    if ($order['user_id'] > 0 && $order['integral'] > 0)
    {
        log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * (-1), sprintf($_LANG['pay_order'], $order['order_sn']));
    }


    if ($order['bonus_id'] > 0 && $temp_amout > 0)
    {
        use_bonus($order['bonus_id'], $new_order_id);
    }
    
    
    
   
    
    /* 如果使用库存，且下订单时减库存，则减少库存 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
    {
       // change_order_goods_storage($order['order_id'], true, SDT_PLACE);
    }

    /* 给商家发邮件 */
    /* 增加是否给客服发送邮件选项 */
    if ($_CFG['send_service_email'] && $_CFG['service_email'] != '')//邮件后台设置有问题，发邮件时间太多。暂时不进发邮件功能
    {
        $order['user_area'] = $GLOBALS['db']->getOne("select a.areaname from ".$GLOBALS['ecs']->table('area')." as a, ".$GLOBALS['ecs']->table('users')." as u where a.areaid = u.areaid and u.user_id = ".$order['user_id']);
       
		if($order['shipping_name'] == '到當地門市取貨')
        {
        	$sql = "SELECT areaname FROM ".$ecs->table('area')." WHERE areaid=".$order['areaid'];
        	$areaname = $GLOBALS['db']->getOne($sql);
        	$order['areaname'] = $areaname;
        }
        $smarty->assign('order', $order);
        $smarty->assign('goods_list', $cart_goods);
        $smarty->assign('shop_name', $_CFG['shop_name']);
        $smarty->assign('send_date', date($_CFG['time_format']));
       // $content = $smarty->fetch('str:' . $tpl['template_content']);       
        //send_mail($_CFG['shop_name'], $_CFG['service_email'], $tpl['template_subject'], $content, $tpl['is_html']);
        $tpl = get_mail_template('remind_of_new_order');
        $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
        $content1 = $smarty->fetch('str:' .'親愛的'.$order['consignee'].'，妳好！ 我們已經收到您於 '.$order['formated_add_time'].' 提交的訂單，該訂單編號為：'.$order['order_sn'].' 請記住這個編號以便日後的查詢。'.$_CFG['shop_name'].date($_CFG['time_format']));
        send_mail($order['consignee'], trim($order['email']), $content1, $content1);
       
    }
   
    /* 如果需要，发短信 */
    if ($_CFG['sms_order_placed'] == '1' && $_CFG['sms_shop_mobile'] != '')
    {
        include_once('includes/cls_sms.php');
        $sms = new sms();
        $msg = $order['pay_status'] == PS_UNPAYED ?
            $_LANG['order_placed_sms'] : $_LANG['order_placed_sms'] . '[' . $_LANG['sms_paid'] . ']';
        $sms->send($_CFG['sms_shop_mobile'], sprintf($msg, $order['consignee'], $order['tel']),'', 13,1);

    }
    
    /* 如果订单金额为0 处理虚拟卡 */
    if ($order['order_amount'] <= 0)
    {
        $sql = "SELECT goods_id, goods_name, goods_number AS num FROM ".
               $GLOBALS['ecs']->table('cart') .
                " WHERE is_real = 0 AND extension_code = 'virtual_card'".
                " AND session_id = '".SESS_ID."' AND rec_type = '$flow_type'";

        $res = $GLOBALS['db']->getAll($sql);

        $virtual_goods = array();
        foreach ($res AS $row)
        {
            $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
        }

        if ($virtual_goods AND $flow_type != CART_GROUP_BUY_GOODS)
        {
            /* 虚拟卡发货 */
            if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
            {
                /* 如果没有实体商品，修改发货状态，送积分和现金卷 */
                $sql = "SELECT COUNT(*)" .
                        " FROM " . $ecs->table('order_goods') .
                        " WHERE order_id = '$order[order_id]' " .
                        " AND is_real = 1";
                if ($db->getOne($sql) <= 0)
                {
                    /* 修改订单状态 */
                    update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => gmtime()));

                    /* 如果订单用户不为空，计算积分，并发给用户；发现金卷 */
                    if ($order['user_id'] > 0)
                    {
                        /* 取得用户信息 */
                        $user = user_info($order['user_id']);

                        /* 计算并发放积分 */
                        $integral = integral_to_give($order);
                        log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($_LANG['order_gift_integral'], $order['order_sn']));

                        /* 发放现金卷 */
                        send_order_bonus($order['order_id']);
                    }
                }
            }
        }

    }

    /* 清空购物车 */
    clear_cart($flow_type);
    /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
    clear_all_files();

    /* 插入支付日志 */
    $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);

    /* 取得支付信息，生成支付代码 */
    if ($order['order_amount'] > 0)
    {
        $payment = payment_info($order['pay_id'],$_SESSION['area_rate_id']);

        include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');

        $pay_obj    = new $payment['pay_code'];

        $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
        $pay_name= explode('_', $order['pay_name']);
        $order['pay_name'] = $pay_name[0];
        $order['pay_desc'] = $payment['pay_desc'];

        $smarty->assign('pay_online', $pay_online);
    }
    if(!empty($order['shipping_name']))
    {
        $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
    }

    /* 订单信息 */
    $smarty->assign('order',      $order);
    $smarty->assign('total',      $total);
    $smarty->assign('goods_list', $cart_goods);
	
	
	
	$sql = " SELECT * from ".$ecs->table('order_goods')." WHERE order_id=".$order['order_id'];
    $order_goodslist = $db->getAll($sql);
    header('Content-Type: text/html;charset=utf-8');
    $con = mysql_connect("localhost:3306","root","dk288771");
    mysql_select_db("webposnewtest", $con);
    if (! mysql_query("INSERT INTO ecs_order_info (order_sn, user_id, order_status,shipping_status,pay_status,consignee,country,province,city,district,address,zipcode,tel,mobile,
    		email,best_time,sign_building,postscript,shipping_id,shipping_name,pay_id,pay_name,how_oos,how_surplus,pack_name,card_name,card_message,inv_payee,inv_content,
    		goods_amount,shipping_fee,insure_fee,pay_fee,pack_fee,card_fee,surplus,integral,integral_money,bonus,order_amount,from_ad,referer,add_time,confirm_time,pay_time,
    		shipping_time,pack_id,card_id,bonus_id,invoice_no,extension_code,extension_id,to_buyer,pay_note,agency_id,inv_type,tax,is_separate,parent_id,areaid,xianjin,
    		xinyongka,ktol,xianhuo,have_return_amount,use_return_amount,admin_user_id,discount,money_paid,pay_detail,shop_pay_yun,user_pay_account,user_pay_account_ture,
    		bonus_all,cart_rate,inform_num,shipping_sf,order_form,sign_time)
    		VALUES ('$order[order_sn]', '$order[user_id]', '$order[order_status]','$order[shipping_status]','$order[pay_status]','$order[consignee]','$order[country]','$order[province]','$order[city]','$order[district]','$order[address]','$order[zipcode]','$order[tel]','$order[mobile]',
    		'$order[email]','$order[best_time]','$order[sign_building]','$order[postscript]','$order[shipping_id]','$order[shipping_name]','$order[pay_id]','$order[pay_name]','$order[how_oos]','','','','$order[card_message]','$order[inv_payee]','$order[inv_content]',
    		'$order[goods_amount]','$order[shipping_fee]','$order[insure_fee]','$order[pay_fee]','$order[pack_fee]','$order[card_fee]','$order[surplus]','$order[integral]','$order[integral_money]','$order[bonus]','$order[order_amount]','$order[from_ad]','$order[referer]','$order[add_time]','','',
    		'','$order[pack_id]','$order[card_id]','$order[bonus_id]','','$order[extension_code]','$order[extension_id]','','','$order[agency_id]','$order[inv_type]','$order[tax]','','$order[parent_id]','$order[areaid]','',
    		'','','','','','','$order[discount]','','','','','',
    		'','$order[cart_rate]','','$order[shipping_sf]','','')", $con))
    {
    	die('Error: ' . mysql_error());
    }
    $result = mysql_query("SELECT * FROM ecs_order_info
    		WHERE order_sn='$order[order_sn]'");
    $order_idnew = '';
    while($row = mysql_fetch_array($result))
    {
    	$order_idnew =  $row['order_id'];
    	 
    }
    foreach ($order_goodslist as $key=>$value)
    {
    	 
    	if (! mysql_query("INSERT INTO ecs_order_goods (order_id,goods_id,goods_name,goods_sn,goods_number,market_price,goods_price,goods_attr,send_number,is_real,extension_code,parent_id,
    			is_gift,goods_attr_id,product_id,suppliers_id,fen_cheng,areaid,extension_id,comfirm_goods_number,comfirm_send_number,refund_reason,refund_desc,refund_pic1,
    			refund_pic2,refund_cat,refund_add_time,refund_confirm_time,refund_num,refund_status,refund_goods,is_online,cost_price,tui_num,fuwu,use_number,zengsong,activity_id)
    			VALUES ('$order_idnew','$value[goods_id]','$value[goods_name]','$value[goods_sn]','$value[goods_number]','$value[market_price]','$value[goods_price]','$value[goods_attr]','$value[send_number]','$value[is_real]','$value[extension_code]','$value[parent_id]',
    			'$value[is_gift]','$value[goods_attr_id]','$value[product_id]','$value[suppliers_id]','$value[fen_cheng]','$value[areaid]','$value[extension_id]','$value[comfirm_goods_number]','$value[comfirm_send_number]','$value[refund_reason]','$value[refund_desc]','$value[refund_pic1]',
    			'$value[refund_pic2]','$value[refund_cat]','$value[refund_add_time]','$value[refund_confirm_time]','$value[refund_num]','$value[refund_status]','$value[refund_goods]','$value[is_online]','$value[cost_price]','$value[tui_num]','$value[fuwu]','$value[use_number]','$value[zengsong]','$value[activity_id]')", $con))
    	{
    		echo "INSERT INTO ecs_order_goods (order_id,goods_id,goods_name,goods_sn,goods_number,market_price,goods_price,goods_attr,send_number,is_real,extension_code,parent_id,
    		is_gift,goods_attr_id,product_id,suppliers_id,fen_cheng,areaid,extension_id,comfirm_goods_number,comfirm_send_number,refund_reason,refund_desc,refund_pic1,
    		refund_pic2,refund_cat,refund_add_time,refund_confirm_time,refund_num,refund_status,refund_goods,is_online,cost_price,tui_num,fuwu,use_number,zengsong,activity_id)
    		VALUES ('$value[order_id]','$value[goods_id]','$value[goods_name]','$value[goods_sn]','$value[goods_number]','$value[market_price]','$value[goods_price]','$value[goods_attr]','$value[send_number]','$value[is_real]','$value[extension_code]','$value[parent_id]',
    		'$value[is_gift]','$value[goods_attr_id]','$value[product_id]','$value[suppliers_id]','$value[fen_cheng]','$value[areaid]','$value[extension_id]','$value[comfirm_goods_number]','$value[comfirm_send_number]','$value[refund_reason]','$value[refund_desc]','$value[refund_pic1]',
    		'$value[refund_pic2]','$value[refund_cat]','$value[refund_add_time]','$value[refund_confirm_time]','$value[refund_num]','$value[refund_status]','$value[refund_goods]','$value[is_online]','$value[cost_price]','$value[tui_num]','$value[fuwu]','$value[use_number]','$value[zengsong]','$value[activity_id]')";
    
    		die('Error: ' . mysql_error());
    	}
    	}
    
    	mysql_close($con);
    $smarty->assign('order_submit_back', sprintf($_LANG['order_submit_back'], $_LANG['back_home'], $_LANG['goto_user_center'])); // 返回提示

    user_uc_call('add_feed', array($order['order_id'], BUY_GOODS)); //推送feed到uc
    unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
    unset($_SESSION['flow_order']);
    unset($_SESSION['direct_shopping']);
}

/*------------------------------------------------------ */
//-- 更新购物车
/*------------------------------------------------------ */

elseif ($_REQUEST['step'] == 'update_cart')
{
    if (isset($_POST['goods_number']) && is_array($_POST['goods_number']))
    {
        //flow_update_cart($_POST['goods_number']);
        $song = array();
        $unsong = array();
        foreach($_POST['goods_number'] as $k=>$v){
            $sql = "select carta_id from ".$GLOBALS['ecs']->table('cart')." where rec_id = $k";
            $rec = $GLOBALS['db']->getOne($sql);
            if(!empty($rec)){ //有赠送商品
                $song[$k] = $v;
            }else{
                $unsong[$k] = $v;
            }
        }
        flow_update_cart_song($song);
        flow_update_cart($unsong);
    }

    if (isset($_POST['goods_attr']) && is_array($_POST['goods_attr']))
    {
        $attr = $_POST['goods_attr'];

        foreach($attr as $k=>$v){
            if(!empty($v)) {
                $sql = "select * from " . $GLOBALS['ecs']->table('cart') . " where rec_id = $k";
                $cart_info = $GLOBALS['db']->getRow($sql);

                $goods_attr = get_goods_attr_info($cart_info['goods_id'], $v);

                $attr_value = str_replace(",", "|", $v);

                $sql = "select * from " . $GLOBALS['ecs']->table('products') . " where goods_id=" . $cart_info['goods_id'] . " and goods_attr='" . $attr_value . "' and areaid= 0 " ;
                $pro_attr = $GLOBALS['db']->getRow($sql);
                $goods_price = get_final_price_new1($cart_info['goods_id'], $cart_info['goods_number'], true, $v, $_SESSION['area_rate_id'], $pro_attr['product_sn']);

                $sql = "update " . $GLOBALS['ecs']->table('cart') . " set goods_price=" . $goods_price . ",goods_attr='" . $goods_attr . "',goods_attr_id='" . $v . "',product_id=" . $pro_attr['product_id'] .
                    " where rec_id = " . $k;
                $GLOBALS['db']->query($sql);
            }
        }

    }

    ecs_header("Location:flow.php\n");
    exit;
}

/*------------------------------------------------------ */
//-- 删除购物车中的商品
/*------------------------------------------------------ */

elseif ($_REQUEST['step'] == 'drop_goods')
{
    $rec_id = intval($_GET['id']);
    $sql = "select goods_id from ".$ecs->table('cart')." WHERE rec_id = '$rec_id' ";
    //flow_hy_cart($rec_id);
    $goods_id = $db->getOne($sql);
    flow_drop_cart_goods($rec_id);
    $sql = "select goods_number,rec_id from ".$GLOBALS['ecs']->table('cart').
				" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' ".
				" AND parent_id = 0 " .
				" AND extension_code not like '%package_buy%' " .
				" AND rec_type = 'CART_GENERAL_GOODS'";
    $list = $db->getAll($sql);
    foreach ($list as $key=>$value)
    {
    	$arr = array();
    	$arr[$value['rec_id']] = $value['goods_number'];
    	flow_update_cart($arr);
    }
    
    
    $sql = "SELECT * FROM ".$ecs->table('cart')." WHERE session_id = '" .SESS_ID. "'  AND parent_id = 0 " .
    		" AND extension_code not like '%package_buy%' AND zengsong = 0 " .
    		" AND rec_type = 'CART_GENERAL_GOODS'";
    
    $ucart_list = $db->getAll($sql);
    
    if(!empty($ucart_list))
    foreach ($ucart_list as $key=>$value);
    {
    	if(!empty($value['goods_id']))
    	{
    		$attr_id    = empty($value['goods_attr_id']) ? array() : explode(',', $value['goods_attr_id']);
    		if($value['product_id']!=0)
    		{
    			$sql_sn = "SELECT product_sn,areaid FROM ".$GLOBALS['ecs']->table('products')." WHERE product_id=".$value['product_id'];
    			$product_list_p = $GLOBALS['db']->getRow($sql_sn);
    			$product_sn = $product_list_p['product_sn'];
    			$areaid_p = $product_list_p['areaid'];
    		}
    		update_cart_volume_price2($value['goods_id'],$areaid_p,$product_sn,$attr_id);
    	}
    }
    
    ecs_header("Location: flow.php\n");
    exit;
}

/* 把优惠活动加入购物车 */
elseif ($_REQUEST['step'] == 'add_favourable')
{
    /* 取得优惠活动信息 */
    $act_id = intval($_POST['act_id']);
    $favourable = favourable_info($act_id);
    if (empty($favourable))
    {
        show_message($_LANG['favourable_not_exist']);
    }

    /* 判断用户能否享受该优惠 */
    if (!favourable_available($favourable))
    {
        show_message($_LANG['favourable_not_available']);
    }

    /* 检查购物车中是否已有该优惠 */
   /* $cart_favourable = cart_favourable();
    if (favourable_used($favourable, $cart_favourable))
    {
        show_message($_LANG['favourable_used']);
    }*/

    /* 赠品（特惠品）优惠 */
    if ($favourable['act_type'] == FAT_GOODS)
    {
        /* 检查是否选择了赠品 */
        if (empty($_POST['gift']))
        {
            show_message($_LANG['pls_select_gift']);
        }

        /* 检查是否已在购物车 
        $sql = "SELECT goods_name" .
                " FROM " . $ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "'" .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
                " AND is_gift = '$act_id'" .
                " AND goods_id " . db_create_in($_POST['gift']);
        $gift_name = $db->getCol($sql);
        if (!empty($gift_name))
        {
            show_message(sprintf($_LANG['gift_in_cart'], join(',', $gift_name)));
        }*/

        /* 检查数量是否超过上限 
        $count = isset($cart_favourable[$act_id]) ? $cart_favourable[$act_id] : 0;
        if ($favourable['act_type_ext'] > 0 && $count + count($_POST['gift']) > $favourable['act_type_ext'])
        {
            show_message($_LANG['gift_count_exceed']);
        }*/
       
        $amount = cart_favourable_amount($favourable);
        $newarray['gift'] = $favourable['gift'] ;
        foreach ($favourable['gift'] as $key => $value)
        {
        	if($amount>=$favourable['gift'][$key]['gift_minprice'])
        	{
        		$num = $value['gift_num'];
        		foreach ($newarray['gift'] as $k=>$v)
        		{
        			if($value['id']==$v['id']&&$value['gift_minprice']!=$v['gift_minprice']&&$value['gift_pan']!=1&&$v['gift_pan']!=1)
        			{
        
        				$num = $num+ $v['gift_num'];
        
        				$favourable['gift'][$key]['pan'] =1;
        				$newarray['gift'][$k] =1;
        				unset($favourable['gift'][$k]);
        			}
        			unset($newarray['gift'][$key]);
        		}
        		if(isset($favourable['gift'][$key]))
        		{
        			$favourable['gift'][$key]['gift_num'] = $num;
        		}
        		//
        	}else
        	{
        		unset($favourable['gift'][$key]);
        	}
        	 
        }
        
       
        /* 添加赠品到购物车 */
        foreach ($favourable['gift'] as $gift)
        {
            if (in_array($gift['id'], $_POST['gift']))
            {
                add_gift_to_cart($act_id, $gift['id'], $gift['price']);
            }
        }
    }
    elseif ($favourable['act_type'] == FAT_DISCOUNT)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], cart_favourable_amount($favourable) * (100 - $favourable['act_type_ext']) / 100);
    }
    elseif ($favourable['act_type'] == FAT_PRICE)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], $favourable['act_type_ext']);
    }
   
    /* 刷新购物车 */
    ecs_header("Location: flow.php\n");
    exit;
}
elseif ($_REQUEST['step'] == 'clear')
{
    $sql = "DELETE FROM " . $ecs->table('cart') . " WHERE session_id='" . SESS_ID . "'";
    $db->query($sql);

    ecs_header("Location:./\n");
}
elseif ($_REQUEST['step'] == 'drop_to_collect')
{
    if ($_SESSION['user_id'] > 0)
    {
        $rec_id = intval($_GET['id']);
        $goods_id = $db->getOne("SELECT  goods_id FROM " .$ecs->table('cart'). " WHERE rec_id = '$rec_id' AND session_id = '" . SESS_ID . "' ");
        $count = $db->getOne("SELECT goods_id FROM " . $ecs->table('collect_goods') . " WHERE user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id'");
        if (empty($count))
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";
            $db->query($sql);
        }
        flow_drop_cart_goods($rec_id);
    }
    ecs_header("Location: flow.php\n");
    exit;
}

/* 验证现金卷序列号 */
elseif ($_REQUEST['step'] == 'validate_bonus')
{
    $bonus_sn = trim($_REQUEST['bonus_sn']);
    if (is_numeric($bonus_sn))
    {
        $bonus = bonus_info(0, $bonus_sn);
    }
    else
    {
        $bonus = array();
    }

//    if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0)
//    {
//        die($_LANG['bonus_sn_error']);
//    }
//    if ($bonus['min_goods_amount'] > cart_amount())
//    {
//        die(sprintf($_LANG['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false)));
//    }
//    die(sprintf($_LANG['bonus_is_ok'], price_format($bonus['type_money'], false)));
    $bonus_kill = price_format($bonus['type_money'], false);

    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods)  )
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        
    }elseif (!check_consignee_info($consignee, $flow_type))
    {
    	$result['error'] = '沒有填寫收貨地址';
    	
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();


        if (((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || ($bonus['type_money'] > 0 && empty($bonus['user_id']))) && $bonus['order_id'] <= 0)
        {
            //$order['bonus_kill'] = $bonus['type_money'];
            $now = gmtime();
            if ($now > $bonus['use_end_date'])
            {
                $order['bonus_id'] = '';
                $result['error']=$_LANG['bonus_use_expire'];
            }
            else
            {
                $order['bonus_id'] = $bonus['bonus_id'];
                $order['bonus_sn'] = $bonus_sn;
            }
        }
        else
        {
            //$order['bonus_kill'] = 0;
            $order['bonus_id'] = '';
            $result['error'] = $_LANG['invalid_bonus'];
        }

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);

        if($total['goods_price']<$bonus['min_goods_amount'])
        {
         $order['bonus_id'] = '';
         /* 重新计算订单 */
         $total = order_fee($order, $cart_goods, $consignee);
         $result['error'] = sprintf($_LANG['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false));
        }

        $smarty->assign('total', $total);

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }
    $json = new JSON();
    die($json->encode($result));
}
/*------------------------------------------------------ */
//-- 添加礼包到购物车
/*------------------------------------------------------ */
elseif ($_REQUEST['step'] == 'add_package_to_cart')
{
    include_once('includes/cls_json.php');
    $_POST['package_info'] = json_str_iconv($_POST['package_info']);
    
    $result = array('error' => 0, 'message' => '', 'content' => '', 'package_id' => '');
    $json  = new JSON;

    if (empty($_POST['package_info']))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }

    $package = $json->decode($_POST['package_info']);

    /* 如果是一步购物，先清空购物车 */
    if ($_CFG['one_step_buy'] == '1')
    {
        clear_cart();
    }

    /* 商品数量是否合法 */
    if (!is_numeric($package->number) || intval($package->number) <= 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['invalid_number'];
    }
    else
    {
    	
        /* 添加到购物车 */
        if (add_package_to_cart($package->package_id, $package->number,$_REQUEST['goods_idlist'],$_REQUEST['attr_list']))
        {
            if ($_CFG['cart_confirm'] > 2)
            {
                $result['message'] = '';
            }
            else
            {
                $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            }

            $result['content'] = insert_cart_info();
            $result['one_step_buy'] = $_CFG['one_step_buy'];
        }
        else
        {
            $result['message']    = $err->last_message();
            $result['error']      = $err->error_no;
            $result['package_id'] = stripslashes($package->package_id);
        }
    }
    $result['confirm_type'] = !empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    die($json->encode($result));
}
//获取购物车信息
elseif ($_REQUEST['step'] == 'get_cart_info')
{
    include_once('includes/cls_json.php');

    $result = array('error' => 0, 'content' => '', 'number' => '', 'amount' => '');
    $json  = new JSON;

    $sql = 'SELECT SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount , SUM(extension_code = "package_buy" ) AS  package_buy_sum , SUM(extension_code = "package_buy_all" ) AS  package_buy_all_sum' .
           ' FROM ' . $GLOBALS['ecs']->table('cart') .
           " WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "' and areaid = ".$_SESSION['area_rate_id'];
    $row = $GLOBALS['db']->GetRow($sql);

    $list = array();
    if ($row)
    {
        $number = intval($row['number']) - intval($row['package_buy_sum']) - intval($row['package_buy_all_sum']);
        $amount = floatval($row['amount']);

        $sql = 'SELECT c.*,g.goods_thumb  FROM ' . $GLOBALS['ecs']->table('cart') .' as c, '.$GLOBALS['ecs']->table('goods')." as g ".
            " WHERE c.goods_id = g.goods_id and c.session_id = '" . SESS_ID . "' AND c.rec_type = '" . CART_GENERAL_GOODS . "' and c.areaid = ".$_SESSION['area_rate_id'];
        $list = $GLOBALS['db']->GetAll($sql);
        foreach($list as $k=>$v){
            $list[$k]['url'] = build_uri('goods', array('gid' => $v['goods_id']), $v['goods_name']);
            $list[$k]['thumb'] = get_image_path($v['goods_id'], $v['goods_thumb'], true);
            $list[$k]['price'] = price_format($v['goods_price']);
        }
    }
    else
    {
        $number = 0;
        $amount = 0;
    }

    $result['number'] = $number;
    $result['amount'] = price_format($amount);

    $goods_list_html = '';

    if($list)
    {
        foreach ($list as $k => $v) {
            if($k<3)
            {
                $goods_list_html .= '<a href="'.$v["url"].'" class="shopcart-box">';
                $goods_list_html .= '<div class="cover" style="background-image:url('.$v["thumb"].')"></div>';
                $goods_list_html .= '<div class="content">';
                $goods_list_html .= '<div class="title">'.$v["goods_name"].'</div>';
                $goods_list_html .= '<div class="info">';
                $goods_list_html .= '<span>數量：'.$v["goods_number"].'</span>';
                $goods_list_html .= '<span>價格：'.$v["price"].'</span>';
                $goods_list_html .= '</div>';
                $goods_list_html .= '</div>';
                $goods_list_html .= '</a>';
            }
        }
    }
    else
    {   
        $result['error'] = 1;

        $goods_list_html .= ' <a href="" class="shopcart-box">';
        $goods_list_html .= ' <div class="content">';
        $goods_list_html .= ' <div class="title">購物車還沒有添加商品</div>';
        $goods_list_html .= '</div>';
        $goods_list_html .= '</a>';
    }
    $result['content'] = $goods_list_html;

    die($json->encode($result));
}

else
{

    /* 标记购物流程为普通商品 */
    $_SESSION['flow_type'] = CART_GENERAL_GOODS;

    /* 如果是一步购物，跳到结算中心 */
    if ($_CFG['one_step_buy'] == '1')
    {
        ecs_header("Location: flow.php?step=checkout\n");
        exit;
    }
  update_favourable_activity_price();
    //updata_cart_vo();
   
  //update_cart_tonew();
    
  /* 计算折扣 */
  $discount = compute_discount(2);
 
  $smarty->assign('discount', $discount['discount']);
    //检查买几送几活动
    /*$up_cart = update_cart_song();

    $smarty->assign('up_cart', $up_cart);*/
    /* 取得商品列表，计算合计 */
    $cart_goods = get_cart_goods();

    $cart_goods_list = $cart_goods['goods_list'];
    //$smarty->assign('goods_list', $cart_goods['goods_list']);
    $smarty->assign('total', $cart_goods['total']);
    $smarty->assign('total_num', $cart_goods['total']['num_c']);
    //判断是否是代理登陆购物
    $smarty->assign('dl_pd',$cart_goods['total']['dl_pd']);
    $smarty->assign('di_total',$cart_goods['total']['di_total']);
    $smarty->assign('fdl_total',$cart_goods['total']['fdl_total']);
    //购物车的描述的格式化
    $smarty->assign('shopping_money',         sprintf( $cart_goods['total']['goods_price']));
    $smarty->assign('market_price_desc',      sprintf($_LANG['than_market_price'],
        $cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate']));
    $smarty->assign('market_price_t',$cart_goods['total']['market_price']);
    $smarty->assign('yh_price_t',$cart_goods['total']['saving']);
    // 显示收藏夹内的商品
    if ($_SESSION['user_id'] > 0)
    {
        require_once(ROOT_PATH . 'includes/lib_clips.php');
        $collection_goods = get_collection_goods($_SESSION['user_id']);
        $smarty->assign('collection_goods', $collection_goods);

        foreach($cart_goods_list as $k=>$value){
            $cart_goods_list[$k]['is_collection']=0;
            foreach($collection_goods as $v){
                if($value['goods_id']==$v['goods_id']){
                    $cart_goods_list[$k]['is_collection']=1;
                }
            }
        }
    }
//new wuxurong
    $shipping_free = 0;

    $zong_list = array();
    foreach ($cart_goods_list as $key=>$value)
    {
        if($value['is_shipping'] == 1)
        {
            $shipping_free  =1;
        }

    	if($value['zengsong'] == 0)
    	{
    		/*$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type >2 and ".gmtime()." >= start_time and ".gmtime();
    		$act_name = $GLOBALS['db']->getAll($sql);
    		$name_tring = '';
    		foreach ($act_name as $k=>$v)
    		{
    			$name_tring = $v['act_name'].' <br/>'.$name_tring;
    		}
    		if(!empty($act_name))
    		{
    			$cart_goods_list[$key]['act_name'] = '參與'.$name_tring;
    		}else
    		{
    			$cart_goods_list[$key]['act_name'] = '';
    		}*/
    	}elseif($value['zengsong'] == 3)
    	{
    		if(strstr($value['activity_id'],","))
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =4 and act_id in(".$value['activity_id'].") and ".gmtime()." >= start_time and ".gmtime();
    			
    		}else 
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =4 and act_id=".$value['activity_id']." and ".gmtime()." >= start_time and ".gmtime();
    			
    		}
    		$act_name = $GLOBALS['db']->getAll($sql);
    		$name_tring = '';
    		foreach ($act_name as $k=>$v)
    		{
    			$name_tring = $v['act_name'].' <br/>'.$name_tring;
    		}
    		$cart_goods_list[$key]['act_name'] = $name_tring.'折扣商品';
    		$cart_goods_list[$key]['act_name_p'] = 4;
    		
    	}
    	else if($value['zengsong'] == 5)
    	{
    		if(strstr($value['activity_id'],","))
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =5  and ".gmtime()." >= start_time and ".gmtime();
    			
    		}else 
    		{
    			$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type =5 and act_id=".$value['activity_id']." and ".gmtime()." >= start_time and ".gmtime();
    			
    		}
    		$act_name = $GLOBALS['db']->getAll($sql);
    		$name_tring = '';
    		foreach ($act_name as $k=>$v)
    		{
    			$name_tring = $v['act_name'].$name_tring;
    		}
    		if(empty($act_name))
    		{
    			$cart_goods_list[$key]['act_name'] = '總價';
    			
    			if($cart_goods_list[$key]['extension_code'] <>'package_buy_all')
    			{
    				$cart_goods_list[$key]['act_name_p'] = 2;
    			}else 
    			{
    				$cart_goods_list[$key]['act_name_p'] = 1;
    			}
    		}else 
    		{
    			
    			$cart_goods_list[$key]['act_name'] = $name_tring;
    			$cart_goods_list[$key]['act_name_p'] = 1;
	    		if($cart_goods_list[$key]['extension_code'] <>'package_buy_all')
	    			{
	    				$cart_goods_list[$key]['act_name_p'] = 2;
	    			}else 
	    			{
	    				$cart_goods_list[$key]['act_name_p'] = 1;
	    			}
    		}
			$zong_list[$key] = $cart_goods_list[$key];
    		unset($cart_goods_list[$key]);
    	}else
    	{
    		$sql = "SELECT act_name FROM ". $GLOBALS['ecs']->table('favourable_activity') ." WHERE act_range_ext like '%".$value['goods_id']."%' and act_type >2 and ".gmtime()." >= start_time and ".gmtime();
    		$act_name = $GLOBALS['db']->getOne($sql);
    		$cart_goods_list[$key]['act_name'] = $act_name.'贈品';
    	}
    }
   
    $cart_goods_list = $cart_goods_list + $zong_list;
    $smarty->assign('fuhe',p_activity_son());

    $smarty->assign('shipping_free',$shipping_free);
  	
	//star 查询购物车是否有买几活动 
    $sql = "SELECT * FROM " . $ecs->table('favourable_activity') . 
    " where act_type >2 and act_range_ext <> '' and ".gmtime()." >= start_time and ".gmtime()." <= end_time ORDER BY sort_order ASC,end_time DESC";
    
    $res = $db->query($sql);

    $list = array();
    $favourable_lists = array();
    $j=1;
    
   
    $smarty->assign('favourable_lists',$favourable_lists);//参与活动列表
   // var_dump($favourable_lists[1]['gift']);
	//end 查询购物车是否有买几活动 
//new wuxurong

    $smarty->assign('goods_list', $cart_goods_list);

    /* 如果使用现金卷，取得用户可以使用的现金卷及用户选择的现金卷 */    // 卡的原因。。。。。。。。。。。后期解决
    if (((!isset($_CFG['use_bonus']) || $_CFG['use_bonus'] == '1') && !empty($_SESSION['user_id']))
        && ( $_SESSION['flow_type'] != CART_GROUP_BUY_GOODS &&  $_SESSION['flow_type'] != CART_EXCHANGE_GOODS))
    {

        // 取得用户可用现金卷
        $user_bonus = user_bonus($_SESSION['user_id'], $cart_goods['total']['goods_amount']);
        /*需要增加对VIP设置判断*/

        if (!empty($user_bonus))
        {
            foreach ($user_bonus AS $key => $val)
            {
                $user_bonus[$key]['bonus_money_formated'] = price_format($val['type_money'], false);
            }

            $smarty->assign('bonus_list', $user_bonus);
        }
    }
    if(!empty($_SESSION['order_bonus'])){
        $bonus_in = bonus_info($_SESSION['order_bonus']);
        $smarty->assign('bonus_value', '使用優惠券：'.$bonus_in['type_name'].'--'.price_format($bonus_in['type_money'], false));
    }
   
   
    /* 取得优惠活动 */
    $favourable_list = favourable_list($_SESSION['user_rank']);

    usort($favourable_list, 'cmp_favourable');
    
    $smarty->assign('favourable_list', $favourable_list);

    
    $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
    $smarty->assign('your_discount', sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount'])));

    /* 增加是否在购物车里显示商品图 */
    $smarty->assign('show_goods_thumb', $GLOBALS['_CFG']['show_goods_in_cart']);

    /* 增加是否在购物车里显示商品属性 */
    $smarty->assign('show_goods_attribute', $GLOBALS['_CFG']['show_attr_in_cart']);

    /* 购物车中商品配件列表 */
    //取得购物车中基本件ID
    $sql = "SELECT goods_id " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            "AND rec_type = '" . CART_GENERAL_GOODS . "' " .
            "AND is_gift = 0 " .
            "AND extension_code <> 'package_buy' " .
            "AND parent_id = 0 ";
    $parent_list = $GLOBALS['db']->getCol($sql);

    $fittings_list = get_goods_fittings($parent_list);

    $smarty->assign('fittings_list', $fittings_list);
}


$smarty->assign('currency_format', $_CFG['currency_format']);
$smarty->assign('integral_scale',  $_CFG['integral_scale']);
$smarty->assign('step',            $_REQUEST['step']);
assign_dynamic('shopping_flow');

$smarty->display('flow.dwt');

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */


function update_cart_tonew()
{
	
	/*删除活动列表购物车关联的数据*/
	$sql = "DELETE FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '".SESS_ID."' and extension_code='package_buy_all' ";
	$GLOBALS['db']->query($sql);
	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET m_pd='',zengsong =0,activity_id=0,song_buy=0,prec_id=0,song_num=0,carta_id=0 ".
			" WHERE session_id = '".SESS_ID."'";
	
	$GLOBALS['db']->query($sql);
	
	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  extension_code ='' ".
			" WHERE session_id = '".SESS_ID."'  and extension_code like 'package_buy_all%'";
	$GLOBALS['db']->query($sql);
	$sql = "DELETE FROM ".$GLOBALS['ecs']->table('cart_activity')." WHERE session_id = '".SESS_ID."'";
	$GLOBALS['db']->query($sql);
	
	$sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " .
			" FROM " . $GLOBALS['ecs']->table('cart') . " " .
			" WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'" .
			" ORDER BY carta_id, extension_code,pid, parent_id";
	$res = $GLOBALS['db']->query($sql);
	
	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		$sql = "select count(*) from ". $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" . SESS_ID . "' AND goods_id=".$row['goods_id']." and goods_attr_id='".$row['goods_attr_id']."' ";
		$pcount = $GLOBALS['db']->getOne($sql);
		if($pcount>1)
		{
			$sql = "select sum(goods_number) from ".$GLOBALS['ecs']->table('cart'). " WHERE session_id = '" . SESS_ID . "' AND goods_id=".$row['goods_id']." and goods_attr_id='".$row['goods_attr_id']."' ";
			$numbercount = $GLOBALS['db']->getOne($sql);
			$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number =".$numbercount.
					" WHERE rec_id =".$row['rec_id'];
			$GLOBALS['db']->query($sql);
			$sql = "DELETE FROM ".$GLOBALS['ecs']->table('cart'). " WHERE session_id = '" . SESS_ID . "' AND goods_id=".$row['goods_id']." and goods_attr_id='".$row['goods_attr_id']."' and rec_id <>".$row['rec_id'];
			$GLOBALS['db']->query($sql);
		}
	}
}

/* 把优惠活动加入购物车 */
function add_favourable_cart($list)
{
    /* 取得优惠活动信息 */
    $act_id = intval($list['act_id']);
    $favourable = favourable_info($act_id);
    if (empty($favourable))
    {
        exit;
    }

    $goods_id = $list['goods_id'];
    $goods_attr_id = $list['goods_attr'];

    $attr_value = str_replace(",", "|", $list['goods_attr']);

    $goods_attr = get_goods_attr_info($list['goods_id'], $list['goods_attr']);

    $sql = "select * from " . $GLOBALS['ecs']->table('products') .  " where goods_id=$goods_id and areaid = 0 and product_status = 1 and goods_attr = '" . $attr_value . "'";
    $pro_attr = $GLOBALS['db']->getRow($sql);

    $sql = "select * from ".$GLOBALS['ecs']->table('goods')." where goods_id=".$list['goods_id'];
    $goods = $GLOBALS['db']->getRow($sql);

    /* 赠品（特惠品）优惠 */
    if ($favourable['act_type'] == FAT_GOODS)
    {
        $sql = "INSERT INTO ".$GLOBALS['ecs']->table('cart')." (user_id,session_id,goods_id,goods_sn,goods_name,market_price,goods_price,goods_number,goods_attr,is_real,extension_code,is_gift,goods_attr_id,product_id,suppliers_id,areaid)".
            " VALUES ('$_SESSION[user_id]','".SESS_ID."',$goods_id,'".$goods['goods_sn']."','".$goods['goods_name']."','".$goods['market_price']."',0,1,'".$goods_attr."',".$goods['is_real'].",'".$goods['extension_code']."',
            $act_id,'".$goods_attr_id."',".$pro_attr['product_id'].",".$goods['suppliers_id'].",".$_SESSION['area_rate_id'].")";

        $GLOBALS['db']->query($sql);
    }
    elseif ($favourable['act_type'] == 3) //买几送几活动，有设赠品
    {
        $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('cart_activity')." where act_id=$act_id  and is_finish=0 and session_id = '".SESS_ID."'  limit 1 ";
        $cart_activity = $GLOBALS['db']->getRow($sql);

        if(empty($cart_activity)) //判斷贈品是否存在
        {

            exit;
        }else{

            $ysong = $cart_activity['ysong']+1;

            $is_finish = 0;
            if($ysong == $cart_activity['song'])
            {
                $is_finish = 1;
            }

            //更新记录表确认这个活动是否已经赠送完赠品
            $sql = "UPDATE " . $GLOBALS['ecs']->table('cart_activity') . " SET  ysong ='".$ysong ."' , is_finish =".$is_finish.
                " WHERE recs_id = ".$cart_activity['recs_id'] ;
            //var_dump($sql);die();
            $GLOBALS['db']->query($sql);
            $sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  cf_huodong =1".
                " WHERE prec_id = ".$cart_activity['recs_id'] ;
            $GLOBALS['db']->query($sql);

            if (!empty($pro_attr) && !empty($pro_attr['product_status'])) {
                //插入赠送商品，减少购物车正常商品的数量
                $zengsong = array(
                    'user_id' => $_SESSION['user_id'],
                    'session_id' => SESS_ID,
                    'goods_id' => $goods_id,
                    'goods_sn' => $goods['goods_sn'],
                    'product_id' => $pro_attr['product_id'],
                    'goods_name' => $goods['goods_name'],
                    'market_price' => $goods['shop_price'],
                    'goods_number' => 1,
                    'goods_price' => 0,
                    'goods_attr' => $goods_attr,
                    'parent_id' => 0,
                    'goods_attr_id' => $list['goods_attr'],
                    'is_real' => $goods['is_real'],
                    'extension_code' => '',
                    'is_gift' => 0,
                    'is_shipping' => $goods['is_shipping'],
                    'rec_type' => CART_GENERAL_GOODS,
                    'areaid' => $_SESSION['area_rate_id'],
                    'suppliers_id' => $goods['suppliers_id'],
                    'fuwu' => $goods['fuwu'],
                    'fen_cheng' => 0,
                    'zengsong' => 2,
                    'activity_id' => $act_id,
                    'song_buy'=>0,
                    'prec_id'=>0,
                    'carta_id'=>$cart_activity['recs_id'],
                    'cf_huodong'=>1
                );

                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');

            }
        }
    }
}

/**
 * 判斷購物車是否有買几送几活動未選擇
 */
function p_activity_son()
{
	$sql = "SELECT *,(song-ysong) as zum FROM " . $GLOBALS['ecs']->table('cart_activity') . " where session_id='".SESS_ID."'  and is_finish=0 and song>ysong group by act_id ";
	$res = $GLOBALS['db']->getAll($sql);
	$fuhe = array();
	$i = 1;
	foreach ($res as $key=>$value)
	{
		$fuhe[$i] = $value;
		$sql = "SELECT act_name,gift FROM " . $GLOBALS['ecs']->table('favourable_activity') . " where act_id=".$value['act_id'];
		$favourable = $GLOBALS['db']->getRow($sql);
		$gift = unserialize($favourable['gift']);
		$fuhe[$i]['act_name'] = $favourable['act_name'];
		$t = array();
		foreach ($gift as $k => $v) {
			$attr = get_goods_properties_two($v['id']);
            $de_attr = array();
            foreach($attr['spe'] as $vc){
                foreach($vc['values'] as $vaa){
                    if($vaa['css'] == 1){
                        $de_attr[]=$vaa['id'];
                    }
                }
            }
            $gift[$k]['goods_attr_id'] = implode(',',$de_attr);
			$gift[$k]['attr'] = $attr['spe'];
			$gift[$k]['thumb'] = get_image_path($v['id'], $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '" . $v['id'] . "'"), true);
			/* 获得商品列表 */
			$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND "."g.is_delete = 0"." AND  g.goods_id in(".$v['id'].") ";
			$sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.is_shipping, g.shop_price AS org_price, ' .
					"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
					'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
					'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
					'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
					"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
					"WHERE ".$where;
			$resst = $GLOBALS['db']->query($sql);
			$arrt = array();
			$spe = array();
			while ($rowst = $GLOBALS['db']->fetchRow($resst))
			{
		
				$promote_price = bargain_price($rowst['promote_price'], $rowst['promote_start_date'], $rowst['promote_end_date']);
				if($promote_price > 0)
				{
					//$_SESSION['area_rate_id']
		
					if($_SESSION['area_rate_id'] > 0)
					{
						$promote_price = get_price_area($rowst['goods_id'],0,'promote_price',0,0,$_SESSION['area_rate_id']);//取地区促销价格
					}
				}
		
				/* 处理商品水印图片 */
				$watermark_img = '';
		
				if ($promote_price != 0)
				{
					$watermark_img = "watermark_promote_small";
				}
				elseif ($rowst['is_new'] != 0)
				{
					$watermark_img = "watermark_new_small";
				}
				elseif ($rowst['is_best'] != 0)
				{
					$watermark_img = "watermark_best_small";
				}
				elseif ($rowst['is_hot'] != 0)
				{
					$watermark_img = 'watermark_hot_small';
				}
		
				if ($watermark_img != '')
				{
					$arrt[$rowst['goods_id']]['watermark_img'] =  $watermark_img;
				}
		
				$arrt[$rowst['goods_id']]['goods_id']         = $rowst['goods_id'];
				if($display == 'grid')
				{
					$arrt[$rowst['goods_id']]['goods_name']       = $rowst['goods_name'];
				}
				else
				{
					$arrt[$rowst['goods_id']]['goods_name']       = $rowst['goods_name'];
				}
				$properties = get_goods_properties($rowst['goods_id']);  // 获得商品的规格和属性
				foreach ($properties['spe'] as $key=>$value)
				{
					foreach ($value['values'] as $v)
					{
						if($v['css'] == 1)
						{
							$properties['spe'][$key]['va'] = $v['id'];
						}
					}
				}
		
				$arrt[$rowst['goods_id']]['name']             = $rowst['goods_name'];
				$arrt[$rowst['goods_id']]['goods_brief']      = $rowst['goods_brief'];
				$arrt[$rowst['goods_id']]['goods_style_name'] = add_style($rowst['goods_name'],$rowst['goods_name_style']);
		
				if($_SESSION['area_rate_id'] > 0)
				{
					$sql = "SELECT price FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$rowst['goods_id']." and areaid=0 and areaid_rate=".$_SESSION['area_rate_id']." order by price " ;
		
					$goods_list_num = $GLOBALS['db']->getOne($sql);
		
					$shop_price_rate = get_price_area($rowst['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
					if ($shop_price_rate>$goods_list_num&&$goods_list_num!=0) {
						$shop_price_rate = $goods_list_num;
					}
					$arrt[$rowst['goods_id']]['shop_price_c'] = $shop_price_rate;
					$arrt[$rowst['goods_id']]['shop_price']   = price_format($shop_price_rate);
					$market_price_rate = get_price_area($rowst['goods_id'],0,'market_price',0,0,$_SESSION['area_rate_id']);
					$arrt[$rowst['goods_id']]['market_price'] = price_format($market_price_rate);
					if($promote_price > 0){
						$promote_price_rate = get_price_area($rowst['goods_id'],0,'promote_price',0,0,$_SESSION['area_rate_id']);
						$arrt[$rowst['goods_id']]['promote_price']    = price_format($promote_price_rate);
						$arrt[$rowst['goods_id']]['promote_price_c'] = $promote_price_rate;
					}else{
						$arrt[$rowst['goods_id']]['promote_price']    = '';
					}
				}else
				{
					$arrt[$rowst['goods_id']]['market_price']     = price_format($rowst['market_price']);
					$arrt[$rowst['goods_id']]['shop_price']       = price_format($rowst['shop_price']);
					$arrt[$rowst['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
				}
				$now = gmtime();
				$sql_v="select sum(price) from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$rowst['goods_id'].
				" and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price')";
		
				$volume_type = $GLOBALS['db']->getOne($sql_v);
				if($volume_type > 0){
					$arrt[$rowst['goods_id']]['volume_type'] = 1;
				}
		
		
				$sql_g="select sum(p.price) from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods_activity')." as f where p.goods_id = ".$rowst['goods_id'].
				" and p.price_type = 'group_price' and p.hd_id = f.act_id and f.act_type = 1 and f.start_time >= ".$now." and f.end_time <= ".$now." and p.areaid_rate =".$_SESSION['area_rate_id'].
				" and p.areaid = 0 and f.areaid like '%".$_SESSION['area_rate_id']."%'";
		
				$group_type = $GLOBALS['db']->getOne($sql_g);
		
				if($group_type > 0){
					$arrt[$rowst['goods_id']]['group_type'] = 1;
				}
				if($volume_type >0 || $group_type >0 || $promote_price > 0 || $rowst['is_shipping']>0){
					$arrt[$rowst['goods_id']]['actp_type'] = 1;
				}
				$arrt[$rowst['goods_id']]['is_shipping']      = $rowst['is_shipping'];
				$arrt[$rowst['goods_id']]['type']             = $rowst['goods_type'];
				$arrt[$rowst['goods_id']]['goods_thumb']      = get_image_path($rowst['goods_id'], $rowst['goods_thumb'], true);
				$arrt[$rowst['goods_id']]['goods_img']        = get_image_path($rowst['goods_id'], $rowst['goods_img']);
				$arrt[$rowst['goods_id']]['url']              = build_uri('goods', array('gid'=>$rowst['goods_id']), $rowst['goods_name']);
				$gallery_list = get_goods_gallery($rowst['goods_id']); // 商品相册
				$gallery_list_one = array();
				$gallery_list_tow = array();
				foreach ($gallery_list as $key=>$value)
				{
					if($key<6)
					{
						$gallery_list_one[$key] = $value;
					}else
					{
						$gallery_list_tow[$key] = $value;
					}
				}
				$arrt[$rowst['goods_id']]['pictures']         = $gallery_list_one; // 商品相册
				$arrt[$rowst['goods_id']]['pictures_t']         = $gallery_list_tow; // 商品相册
				$arrt[$rowst['goods_id']]['properties']       = $properties['pro']; // 商品属性
				$arrt[$rowst['goods_id']]['specification']    = $properties['spe']; // 商品规格
				$spe = $properties['spe'];
				//商品地区数量
				$sql = "select areaid from ".$GLOBALS['ecs']->table('area')." where AreaExchangeRateId = ".$_SESSION['area_rate_id'];
				$area_list = $GLOBALS['db']->getAll($sql);
		
				$area_list_t = array();
				if(count($area_list) > 0){
					if(count($area_list) == 1){
						$area_value = "(". $area_list[0]['areaid'].")";
					}else{
						foreach ($area_list as $key=>$value)
						{
							$area_list_t[$key] = $value['areaid'];
						}
						$area_value = "(". implode(",",$area_list_t).")";
		
					}
				}else{
					$area_value = "(0)";
				}
		
				$sql = "select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where areaid in ".$area_value." and goods_id=".$rowst['goods_id'];
		
				$arrt[$rowst['goods_id']]['number']    = $GLOBALS['db']->getOne($sql);
			}
			$gift[$k]['specification'] =   $spe;
		}
		$t = $gift;
		$fuhe[$i]['gift'] = $t;
		$i = $i+1;
	}
	

	return $fuhe;
}
/**
 * 获得用户的可用积分
 *
 * @access  private
 * @return  integral
 */
function flow_available_points($rate=0,$areaid=0)
{
	if(!empty($_SESSION['area_rate_id']))
	{
		$rate = $_SESSION['area_rate_id'];
	}
    $sql = "SELECT SUM(g.price * c.goods_number) ".
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('price_area') . " AS g " .
            "WHERE c.session_id = '" . SESS_ID . "' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.price > 0 " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "' and g.price_type='integral' AND g.areaid=$areaid AND areaid_rate=$rate";

    $val = intval($GLOBALS['db']->getOne($sql));
   
   // return integral_of_value($val); 修改地区前 
    return $val;//修改地区后
}

/**
 * 更新购物车中的商品数量
 *
 * @access  public
 * @param   array   $arr
 * @return  void
 */
function flow_update_cart($arr)  //处理赠送商品未开发
{
	
    /* 处理 */
    foreach ($arr AS $key => $val) //循环处理购物车里的每条商品记录
    {
    	
        $val = intval(make_semiangle($val));
        if ($val <= 0 && !is_numeric($key))
        {
            continue;
        }
        //查询：
        $sql = "SELECT `goods_id`, `goods_attr_id`, `zengsong`, `product_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
               " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
        $goods = $GLOBALS['db']->getRow($sql);
        
        //需要过滤掉赠送商品，在循环结束后统计计算回赠送商品
        if($goods['zengsong'] > 1)
        {
        	continue;
        }
        
        $sql = "SELECT g.goods_name, g.goods_number ".
                "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
                    $GLOBALS['ecs']->table('cart'). " AS c ".
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
        $row = $GLOBALS['db']->getRow($sql);
        
        //查询：系统启用了库存，检查输入的商品数量是否有效
        if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
        {
        	
            if ($row['goods_number'] < $val)
            {
                show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                $row['goods_number'], $row['goods_number']));
              
                exit;
            }
            
            /* 是货品 */
            $goods['product_id'] = trim($goods['product_id']);
            if (!empty($goods['product_id']))
            {
                $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";

                $product_number = $GLOBALS['db']->getOne($sql);
                if ($product_number < $val)
                {
                    show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                    $product_number, $product_number));
                    exit;
                }
            }
        }
        elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
        {
            if (judge_package_stock($goods['goods_id'], $val))
            {
                show_message($GLOBALS['_LANG']['package_stock_insufficiency']);
                exit;
            }
        }
        
        /* 查询：检查该项是否为基本件 以及是否存在配件 */
        /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */
        $sql = "SELECT b.goods_number, b.rec_id
                FROM " .$GLOBALS['ecs']->table('cart') . " a, " .$GLOBALS['ecs']->table('cart') . " b
                WHERE a.rec_id = '$key'
                AND a.session_id = '" . SESS_ID . "'
                AND a.extension_code <> 'package_buy'
                AND b.parent_id = a.goods_id
                AND b.session_id = '" . SESS_ID . "'";

        $offers_accessories_res = $GLOBALS['db']->query($sql);

        //订货数量大于0
        if ($val > 0)
        {
            /* 判断是否为超出数量的优惠价格的配件 删除*/
            $row_num = 1;
            while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
            {
                if ($row_num > $val)
                {
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                            " WHERE session_id = '" . SESS_ID . "' " .
                            "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
                    $GLOBALS['db']->query($sql);
                }

                $row_num ++;
            }

            /* 处理超值礼包 */
            if ($goods['extension_code'] == 'package_buy')
            {
                //更新购物车中的商品数量
                $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
                        " SET goods_number = '$val' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
                /*更新购物车里的礼包商品数量*/
                $sqlgoodslist = "SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE extension_code='package_buy".$goods['goods_id']."' AND session_id='" . SESS_ID . "'";
                $goods_list = $GLOBALS['db']->getAll($sqlgoodslist);
                foreach ($goods_list as $key=>$value)
                {
                	$goods_num = $GLOBALS['db']->getOne("SELECT goods_number FROM ".$GLOBALS['ecs']->table('package_goods')." WHERE package_id=".$goods['goods_id']." AND goods_id=".$value['goods_id']);   	 
                	$goods_num = $goods_num*$val;
                	$sqlgoodsnum = "UPDATE " .$GLOBALS['ecs']->table('cart').
                        " SET goods_number = '$goods_num' WHERE extension_code='package_buy".$goods['goods_id']."' AND goods_id='$value[goods_id]' AND session_id='" . SESS_ID . "'";
                	$GLOBALS['db']->query($sqlgoodsnum);
                }
                /*更新购物车里的礼包商品数量*/
            }
            /* 处理普通商品或非优惠的配件 */
            else
            {
                $attr_id    = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
                $product_sn = 0;
                $areaid_p = 0;
                if($goods['product_id']!=0)
                {
                	$sql_sn = "SELECT product_sn,areaid FROM ".$GLOBALS['ecs']->table('products')." WHERE product_id=".$goods['product_id'];
                	$product_list_p = $GLOBALS['db']->getRow($sql_sn);
                	$product_sn = $product_list_p['product_sn'];
                	$areaid_p = $product_list_p['areaid'];
                }
                $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
                " SET goods_number = '$val' WHERE rec_id='$key' AND session_id='" . SESS_ID ."'";
                
                $GLOBALS['db']->query($sql);
				
                $goods_price = get_final_price_new1($goods['goods_id'], $val, true, $attr_id,$areaid_p,$product_sn,1);
                
                //更新购物车中的商品数量
                $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
                        " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND session_id='" . SESS_ID ."'";
				update_cart_volume_price2($goods['goods_id'],$areaid_p,$product_sn,$attr_id);
                $GLOBALS['db']->query($sql);
            }
        }
        //订货数量等于0
        else
        {
            /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */
            while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
            {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                        " WHERE session_id = '" . SESS_ID . "' " .
                        "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
                $GLOBALS['db']->query($sql);
            }

            $sql = "DELETE FROM " .$GLOBALS['ecs']->table('cart').
                " WHERE rec_id='$key' AND session_id='" .SESS_ID. "'";
        }

        $GLOBALS['db']->query($sql);
    }
    
    /*
     * 计算更新购物车赠送商品数量重新归档。
     */
    /* 删除所有买几送几赠品 
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" .SESS_ID. "' AND zengsong =1";
    $GLOBALS['db']->query($sql);
    //在添加回赠送商品
    $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('favourable_activity')." WHERE act_type = 3 and ".gmtime()." >= start_time and ".gmtime()." <= end_time  ";
    
    $fa_list = $GLOBALS['db']->getAll($sql);
    
    foreach ($fa_list as $key=>$value) //循环查询购物车里商品看是否符合优惠活动列表里的活动。循环增加赠送商品
    {
    	$sql = "SELECT sum(goods_number) FROM " .$GLOBALS['ecs']->table('cart').
    	" WHERE session_id = '" .SESS_ID. "' AND goods_id in(".$value['act_range_ext'].") ".
    	" AND parent_id = 0 " .
    	" AND extension_code not like '%package_buy%' " .
    	" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 ";
    	$pgoods_num1 = $GLOBALS['db']->getOne($sql);
    if($pgoods_num1 >= $value['buy'])
    	{
    		
    		$gift_list = unserialize($value['gift']);
    		
    		if(empty($gift_list))//没有设置赠送礼品，则选择购买这几样商品中最便宜的几件赠送
    		{
    			$sql = " SELECT goods_id,goods_number,goods_price,goods_attr_id,product_id FROM ".$GLOBALS['ecs']->table('cart').
		    	" WHERE session_id = '" .SESS_ID. "' AND goods_id in(".$value['act_range_ext'].") ".
		    	" AND parent_id = 0 " .
		    	" AND extension_code not like '%package_buy%' " .
		    	" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 ";
    			$cart_list = $GLOBALS['db']->getAll($sql); //查询活动产品有哪些加入购物车了。
    			if(count($cart_list)==1)//只有一种货品，设置配送的几件价格以及标识为赠送商品
    			{
    				
    				$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "' AND goods_id=".$cart_list[0]['goods_id'].
    				" AND parent_id = 0 " .
		    		" AND extension_code not like '%package_buy%' " .
		    		" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 ";
    				$goods_song = $GLOBALS['db']->getRow($sql);
    				
    				//插入赠送商品，减少购物车正常商品的数量
    				$zengsong = array(
    				 'user_id'       => $_SESSION['user_id'],
    						'session_id'    => SESS_ID,
    						'goods_id'      => $cart_list[0]['goods_id'],
    						'goods_sn'      => $goods_song['goods_sn'],
    						'product_id'    => $goods_song['product_id'],
    						'goods_name'    => $goods_song['goods_name'],
    						'market_price'  => $goods_song['market_price'],
    						'goods_number'   =>$value['song'],
    						'goods_price'   =>0,
    						'goods_attr'    => $goods_song['goods_attr'],
    						'parent_id'     =>$goods_song['parent_id'],
    						'goods_attr_id' => $goods_song['goods_attr_id'],
    						'is_real'       => $goods_song['is_real'],
    						'extension_code'=> $goods_song['extension_code'],
    						'is_gift'       => 0,
    						'is_shipping'   => $goods_song['is_shipping'],
    						'rec_type'      => $goods_song['rec_type'],
    						'areaid' 		=> $goods_song['areaid'],
    						'suppliers_id' 	=> $goods_song['suppliers_id'],
    						'fuwu' 	=> $goods_song['fuwu'],
    						'fen_cheng'		=> $goods_song['fen_cheng'],
    						'zengsong' => 1,
    						'activity_id'=>$value['act_id'] // 增加活动ID，未测试
    						
    				);
    				 $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');
    				 
    				 $num_update = $goods_song['goods_number'] - $value['song'];
    				 
    				 $sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num_update'" .
    				 " WHERE session_id = '" .SESS_ID. "' AND goods_id = '".$cart_list[0]['goods_id']."' ".
    				 " AND parent_id = 0 AND goods_attr = '" .$cart_list[0]['goods_attr']. "' " .
    				 " AND extension_code <> 'package_buy' " .
    				 "AND rec_type = 'CART_GENERAL_GOODS' AND  product_id=".$cart_list[0]['product_id']." AND zengsong=0";
    				 $GLOBALS['db']->query($sql);
    				
    			}else//多种货品，对比哪种商品便宜，就用哪种，不够类推，设置送够为止
    			{
    				
    				$goods_ids = '';
    				foreach ($cart_list as $ck=>$cv)
    				{
    					$goods_ids = $goods_ids.$cv['goods_id'].",";
    				}
    				$goods_ids = substr($goods_ids,0,strlen($goods_ids)-1);
    				$sql = " SELECT price,goods_id FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id in(".$goods_ids.") AND price_type='shop_price' and  areaid=".$_SESSION['area_rate_id']." GROUP BY goods_id ";
    				
    				
    				$goods_yprice = $GLOBALS['db']->getAll($sql); //取各个商品地区店售价
    				foreach ($cart_list as $ck=>$cv)  //查询购物车货品列表属性价格
    				{
    					$cart_list[$ck]['attr_price'] = 0;
    					if(!empty($cv['goods_attr_id']))
    					{
    						$sql = " SELECT sum(price) FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$cv['goods_id']." and areaid=".$_SESSION['area_rate_id']." and price_type='attr_price' and hd_id in(".$cv['goods_attr_id'].") ";
    						
    						$cart_list[$ck]['attr_price'] = $GLOBALS['db']->getOne($sql);
    					}
    				}
    				
    				foreach ($cart_list as $ck=>$cv)//合并属性价格 用于排序哪个货品价格更便宜
    				{
    					foreach ($goods_yprice as $gk=>$gv)
    					{
    						if($cv['goods_id'] == $gv['goods_id'])
    						{
    							$cart_list[$ck]['attr_price']  = $cart_list[$ck]['attr_price']+$gv['price'];
    						}
    					}
    				}
    				$cart_list = array_sort($cart_list, 'attr_price','asc');//按价格排序
    				//开始根据设置赠送数量赠送商品
    				$zengsong_s = $value['song'];
    				foreach ($cart_list as $ck=>$cv)
    				{
    					if($zengsong_s == 0)//送完跳出循环
    					{
    						break;
    					}
    					$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "' AND goods_id=".$cv['goods_id'].
    					" AND parent_id = 0 " .
    					" AND extension_code not like '%package_buy%' " .
    					" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 ";
    					$goods_song = $GLOBALS['db']->getRow($sql);
    					$song = 0;
    					if($zengsong_s>=$goods_song['goods_number'])//一件货品数不够赠送数大，全设置为赠送商品
    					{
    						$song = $goods_song['goods_number'];
    					}else //如果余下的赠送数小于这件货品，则就送余下的数即可。
    					{
    						$song = $zengsong_s;
    					}
    					$zengsong_s = $zengsong_s - $song; //减掉已赠送的数量。
    					//插入赠送商品，减少购物车正常商品的数量
    					$zengsong = array(
    							'user_id'       => $_SESSION['user_id'],
    							'session_id'    => SESS_ID,
    							'goods_id'      => $cv['goods_id'],
    							'goods_sn'      => $goods_song['goods_sn'],
    							'product_id'    => $goods_song['product_id'],
    							'goods_name'    => $goods_song['goods_name'],
    							'market_price'  => $goods_song['market_price'],
    							'goods_number'   =>$song,
    							'goods_price'   =>0,
    							'goods_attr'    => $goods_song['goods_attr'],
    							'parent_id'     =>$goods_song['parent_id'],
    							'goods_attr_id' => $goods_song['goods_attr_id'],
    							'is_real'       => $goods_song['is_real'],
    							'extension_code'=> $goods_song['extension_code'],
    							'is_gift'       => 0,
    							'is_shipping'   => $goods_song['is_shipping'],
    							'rec_type'      => $goods_song['rec_type'],
    							'areaid' 		=> $goods_song['areaid'],
    							'suppliers_id' 	=> $goods_song['suppliers_id'],
    							'fuwu' 	=> $goods_song['fuwu'],
    							'fen_cheng'		=> $goods_song['fen_cheng'],
    							'zengsong' => 1,
    							'activity_id'=>$value['act_id']// 增加活动ID，未测试
    					
    					);
    					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');
    						
    					$num_update = $goods_song['goods_number'] - $song;
    						
    					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num_update'" .
    					" WHERE session_id = '" .SESS_ID. "' AND goods_id = '".$cv['goods_id']."' ".
    					" AND parent_id = 0 AND goods_attr = '" .$cv['goods_attr']. "' " .
    					" AND extension_code <> 'package_buy' " .
    					"AND rec_type = 'CART_GENERAL_GOODS' AND  product_id=".$cv['product_id']." AND zengsong=0";
    					$GLOBALS['db']->query($sql);
    					
    				}
    				
    			}
    			
    		}//要做个页面让客户选择赠送商品属性
    		elseif ($value['song']> count($gift_list)) //如果设置赠送商品少于赠送数，则在购买商品中补齐    赠送商品每样一件
    		{
    			
    			
    		}else //选最便宜的赠送商品送，直到够数为止
    		{
    			
    		}
    		
    		//重新加入赠送商品
    	}
    	
    }*/
    /*
     * 计算更新购物车赠送商品数量重新归档。
    */

    
    
    /* 删除所有赠品 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" .SESS_ID. "' AND is_gift <> 0 and zengsong =0 ";
    $GLOBALS['db']->query($sql);
    //update_cart_volume_price($goods['goods_id']);
}

/**
 * 更新购物车中的赠送商品数量 long
 *
 * @access  public
 * @param   array   $arr
 * @return  void
 */
function flow_update_cart_song($arr){

    foreach ($arr AS $key => $val){
        $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where rec_id = $key";
        $cart_info = $GLOBALS['db']->getRow($sql);
        if(!empty($cart_info)){
            if($cart_info['goods_number'] != $val){ //更新数量不相等的商品
                if($val < $cart_info['song_num']){ //小于划出去的数量，删第一个赠送活动
                    $cat = explode(',',$cart_info['carta_id']);

                    foreach($cat as $k=>$value){

                        //删除正常商品关联的活动ID
                        $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$value."%'";

                        $cart_ze = $GLOBALS['db']->getAll($sql);

                        foreach($cart_ze as $v){
                            $catid = explode(',',$v['carta_id']);
                            if(count($catid) >1){
                                $rc = array();
                                foreach($catid as $vc){
                                    if($vc != $value){
                                        $rc[]=$vc;
                                    }
                                }
                                $cat = implode(',',$rc);

                                $sql = "select buy_goods_id from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$value;
                                $by_goods = $GLOBALS['db']->getOne($sql);
                                $by_list = unserialize($by_goods);
                                $snum = 0;
                                foreach($by_list as $vb){
                                    if($vb['rec_id'] == $v['rec_id']){
                                        $snum = $vb['ysong'];
                                    }
                                }
                                $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=song_num - $snum,carta_id='".$cat."'".
                                    " where rec_id = ".$v['rec_id'];
                            }else{
                                $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=0,carta_id=0".
                                    " where rec_id = ".$v['rec_id'];
                            }

                            $GLOBALS['db']->query($sql);
                        }

                        //删除赠品
                        $sql = "select zengsong from ".$GLOBALS['ecs']->table('cart')." where zengsong >0 and carta_id = '".$value."'";
                        $zeng = $GLOBALS['db']->getOne($sql);
                        if($zeng == 1 || $zeng == 3){ //没设赠品

                            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong >0 and carta_id = ".$value;

                            $cart_song = $GLOBALS['db']->getAll($sql);

                            foreach($cart_song as $values){
                                $sql = "select count(*) from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$values['prec_id'];
                                if($GLOBALS['db']->getOne($sql)){
                                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = goods_number + ".$values['goods_number']." where rec_id = ".$values['prec_id'];
                                    $GLOBALS['db']->query($sql);

                                    $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$values['rec_id'];
                                    $GLOBALS['db']->query($sql);
                                }else{
                                    $sql = "select product_sn from ".$GLOBALS['ecs']->table('products')." where product_id = ".$values['product_id'];
                                    $product_sn = $GLOBALS['db']->getOne($sql);
                                    $goods_price = get_final_price_new($values['goods_id'],$values['goods_number'],true,$values['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);

                                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set rec_id = ".$values['prec_id'].", goods_price = ".$goods_price.", zengsong = 0, activity_id = 0, song_buy = 0, prec_id=0, song_num=0, carta_id=0 ".
                                        " where rec_id = ".$values['rec_id'];

                                    $GLOBALS['db']->query($sql);
                                }
                            }
                            $sql = "delete from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id = ".$value;
                            $GLOBALS['db']->query($sql);

                            //更新数量
                            $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = $val where  rec_id = $key";

                            $GLOBALS['db']->query($sql);

                            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where rec_id = $key";
                            $info = $GLOBALS['db']->getRow($sql);

                            if($info['song_num'] < $info['goods_number']){
                                break;
                            }

                        }
                        elseif($zeng == 2){ //买几送几有赠品

                            $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where zengsong = 2 and  carta_id = ".$value;
                            $GLOBALS['db']->query($sql);

                            $sql = "delete from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id = ".$value;
                            $GLOBALS['db']->query($sql);

                            //更新数量
                            $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = $val where  rec_id = $key";

                            $GLOBALS['db']->query($sql);

                            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where rec_id = $key";
                            $info = $GLOBALS['db']->getRow($sql);

                            if($info['song_num'] < $info['goods_number']){
                                break;
                            }

                        }
                    }


                }else{ //更新数量
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = $val where  rec_id = $key";

                    $GLOBALS['db']->query($sql);
                }
            }
        }
    }

}

/**
 * 检查订单中商品库存
 *
 * @access  public
 * @param   array   $arr
 *
 * @return  void
 */
function flow_cart_stock($arr)
{
    foreach ($arr AS $key => $val)
    {
        $val = intval(make_semiangle($val));
        if ($val <= 0)
        {
            continue;
        }

        $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
               " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
        $goods = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
                "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
                    $GLOBALS['ecs']->table('cart'). " AS c ".
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
        $row = $GLOBALS['db']->getRow($sql);

        //系统启用了库存，检查输入的商品数量是否有效
        if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
        {
            if ($row['goods_number'] < $val)
            {
                show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                $row['goods_number'], $row['goods_number']));
                exit;
            }

            /* 是货品 */
            $row['product_id'] = trim($row['product_id']);
            if (!empty($row['product_id']))
            {
                $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                $product_number = $GLOBALS['db']->getOne($sql);
                if ($product_number < $val)
                {
                    show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                    $row['goods_number'], $row['goods_number']));
                    exit;
                }
            }
        }
        elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
        {
            if (judge_package_stock($goods['goods_id'], $val))
            {
                show_message($GLOBALS['_LANG']['package_stock_insufficiency']);
                exit;
            }
        }
    }

}
/**
 * 判断是不是买几送几活动，是则还原该活动
 * 
 * 
 * @param unknown $id
 */
function flow_hy_cart($id)
{
    /*取商品信息*/
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('cart'). " WHERE rec_id = '$id'";
    $row = $GLOBALS['db']->getRow($sql);
    if($row['zengsong']>0)//此商品为赠品，删除所有这个活动的赠品，正常商品还原
    {
        if($row['zengsong'] == 1 || $row['zengsong'] == 3)//无设置赠送商品
        {
            /*$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE rec_id=".$row['prec_id']; // 查询购物车是否存在正常商品，如果不存在。则需要去cart_activity找回，删除赠品后添加回购物车。
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE carta_id=".$row['carta_id'];
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE carta_id=".$row['carta_id'];
            echo $sql;
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('cart_activity')." WHERE recs_id=".$row['carta_id'];
            echo $sql;*/

            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$row['carta_id']."%'";

            $cart_ze = $GLOBALS['db']->getAll($sql);

            foreach($cart_ze as $v){
                $catid = explode(',',$v['carta_id']);
                if(count($catid) >1){
                    $rc = array();
                    foreach($catid as $vc){
                        if($vc != $row['carta_id']){
                            $rc[]=$vc;
                        }
                    }
                    $cat = implode(',',$rc);

                    $sql = "select buy_goods_id from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$row['carta_id'];
                    $by_goods = $GLOBALS['db']->getOne($sql);
                    $by_list = unserialize($by_goods);
                    $snum = 0;
                    foreach($by_list as $vb){
                        if($vb['rec_id'] == $v['rec_id']){
                            $snum = $vb['ysong'];
                        }
                    }
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=song_num - $snum,carta_id='".$cat."'".
                        " where rec_id = ".$v['rec_id'];
                }else{
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=0,carta_id=0,is_jianshu=0,extension_code=''".
                        " where rec_id = ".$v['rec_id'];
                }

                $GLOBALS['db']->query($sql);
            }

            //删除增品

            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = ".$row['zengsong']." and carta_id = ".$row['carta_id'];

            $cart_song = $GLOBALS['db']->getAll($sql);
            foreach($cart_song as $value){
                $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['prec_id'];
                $pre = $GLOBALS['db']->getRow($sql);
                $sql = "select count(*) from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['prec_id'];
                if($GLOBALS['db']->getOne($sql)){
                    $sql = "select product_sn from " . $GLOBALS['ecs']->table('products') . " where product_id = " . $value['product_id'];
                    $product_sn = $GLOBALS['db']->getOne($sql);
                    $goods_prices = get_final_price_new($value['goods_id'], $value['goods_number']+$pre['goods_number'], true, $value['goods_attr_id'], $_SESSION['area_rate_id'], $product_sn);

                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = goods_number + ".$value['goods_number'].",goods_price = " . $goods_prices . "  where rec_id = ".$value['prec_id'];
                    $GLOBALS['db']->query($sql);
                    $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where  rec_id = ".$value['rec_id'];
                    $GLOBALS['db']->query($sql);
                }else{
                    $sql = "select product_sn from ".$GLOBALS['ecs']->table('products')." where product_id = ".$value['product_id'];
                    $product_sn = $GLOBALS['db']->getOne($sql);
                    $goods_price = get_final_price_new($value['goods_id'],$value['goods_number'],true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);

                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set rec_id = ".$value['prec_id'].", goods_price = ".$goods_price.", zengsong = 0, activity_id = 0, song_buy = 0, prec_id=0, song_num=0, carta_id=0,extension_code='' ".
                        " where rec_id = ".$value['rec_id'];

                    $GLOBALS['db']->query($sql);
                }
            }


            $sql = "delete from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id = ".$row['carta_id'];
            $GLOBALS['db']->query($sql);

        }
        elseif ($row['zengsong']==2)//设置赠送商品
        {
            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$row['carta_id']."%'";

            $cart_ze = $GLOBALS['db']->getAll($sql);

            foreach($cart_ze as $v){
                $catid = explode(',',$v['carta_id']);
                if(count($catid) >1){
                    $rc = array();
                    foreach($catid as $vc){
                        if($vc != $row['carta_id']){
                            $rc[]=$vc;
                        }
                    }
                    $cat = implode(',',$rc);

                    $sql = "select buy from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$row['carta_id'];
                    $by_goods = $GLOBALS['db']->getOne($sql);

                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=song_num - $by_goods,carta_id='".$cat."'".
                        " where rec_id = ".$v['rec_id'];
                }else{
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=0,carta_id=0,extension_code=''".
                        " where rec_id = ".$v['rec_id'];
                }

                $GLOBALS['db']->query($sql);
            }

            $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where zengsong = 2 and  carta_id = ".$row['carta_id'];
            $GLOBALS['db']->query($sql);

            $sql = "delete from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id = ".$row['carta_id'];
            $GLOBALS['db']->query($sql);

        }
        elseif($row['zengsong']==5)//总价几商品
        {
            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$row['carta_id']."%'";

            $cart_ze = $GLOBALS['db']->getAll($sql);

            foreach($cart_ze as $v){
                $catid = explode(',',$v['carta_id']);
                if(count($catid) >1){
                    $rc = array();
                    foreach($catid as $vc){
                        if($vc != $row['carta_id']){
                            $rc[]=$vc;
                        }
                    }
                    $cat = implode(',',$rc);

                    $sql = "select buy_goods_id from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$row['carta_id'];
                    $by_goods = $GLOBALS['db']->getOne($sql);
                    $by_list = unserialize($by_goods);
                    $snum = 0;
                    foreach($by_list as $vb){
                        if($vb['rec_id'] == $v['rec_id']){
                            $snum = $vb['ysong'];
                        }
                    }
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=song_num - $snum,carta_id='".$cat."'".
                        " where rec_id = ".$v['rec_id'];
                }else{
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=0,carta_id=0,is_jianshu=0,extension_code=''".
                        " where rec_id = ".$v['rec_id'];
                }

                $GLOBALS['db']->query($sql);
            }

            //删除增品

            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = ".$row['zengsong']." and carta_id = ".$row['carta_id'];

            $cart_song = $GLOBALS['db']->getAll($sql);
            foreach($cart_song as $value){
                $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['prec_id'];
                $pre = $GLOBALS['db']->getRow($sql);

                $sql = "select count(*) from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['prec_id'];
                if($GLOBALS['db']->getOne($sql)){
                    $sql = "select product_sn from " . $GLOBALS['ecs']->table('products') . " where product_id = " . $value['product_id'];
                    $product_sn = $GLOBALS['db']->getOne($sql);
                    $goods_prices = get_final_price_new($value['goods_id'], $value['goods_number']+$pre['goods_number'], true, $value['goods_attr_id'], $_SESSION['area_rate_id'], $product_sn);

                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = goods_number + ".$value['goods_number'].",goods_price = " . $goods_prices . "  where rec_id = ".$value['prec_id'];
                    $GLOBALS['db']->query($sql);
                    $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where  rec_id = ".$value['rec_id'];
                    $GLOBALS['db']->query($sql);
                }else{
                    if(empty($value['prec_id']) && empty($value['goods_sn'])){
                        $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where  rec_id = ".$value['rec_id'];
                        $GLOBALS['db']->query($sql);
                    }else {
                        $sql = "select product_sn from " . $GLOBALS['ecs']->table('products') . " where product_id = " . $value['product_id'];
                        $product_sn = $GLOBALS['db']->getOne($sql);
                        $goods_price = get_final_price_new($value['goods_id'], $value['goods_number'], true, $value['goods_attr_id'], $_SESSION['area_rate_id'], $product_sn);

                        $sql = "update " . $GLOBALS['ecs']->table('cart') . " set rec_id = " . $value['prec_id'] . ", goods_price = " . $goods_price . ", zengsong = 0, activity_id = 0, song_buy = 0, prec_id=0, song_num=0, carta_id=0,extension_code='' " .
                            " where rec_id = " . $value['rec_id'];

                        $GLOBALS['db']->query($sql);
                    }
                }
            }


            $sql = "delete from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id = ".$row['carta_id'];
            $GLOBALS['db']->query($sql);
        }
        elseif($row['zengsong']==6)//递加折扣
        {
            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$row['carta_id']."%'";

            $cart_ze = $GLOBALS['db']->getAll($sql);

            foreach($cart_ze as $v){
                $catid = explode(',',$v['carta_id']);
                if(count($catid) >1){
                    $rc = array();
                    foreach($catid as $vc){
                        if($vc != $row['carta_id']){
                            $rc[]=$vc;
                        }
                    }
                    $cat = implode(',',$rc);

                    $sql = "select buy_goods_id from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$row['carta_id'];
                    $by_goods = $GLOBALS['db']->getOne($sql);
                    $by_list = unserialize($by_goods);
                    $snum = 0;
                    foreach($by_list as $vb){
                        if($vb['rec_id'] == $v['rec_id']){
                            $snum = $vb['ysong'];
                        }
                    }
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=song_num - $snum,carta_id='".$cat."'".
                        " where rec_id = ".$v['rec_id'];
                }else{
                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=0,carta_id=0,is_jianshu=0,extension_code=''".
                        " where rec_id = ".$v['rec_id'];
                }

                $GLOBALS['db']->query($sql);
            }

            //删除增品

            $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = ".$row['zengsong']." and carta_id = ".$row['carta_id'];

            $cart_song = $GLOBALS['db']->getAll($sql);

            foreach($cart_song as $value){
                $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['prec_id'];
                $pre = $GLOBALS['db']->getRow($sql);
                $sql = "select count(*) from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['prec_id'];
                if($GLOBALS['db']->getOne($sql)){
                    $sql = "select product_sn from " . $GLOBALS['ecs']->table('products') . " where product_id = " . $value['product_id'];
                    $product_sn = $GLOBALS['db']->getOne($sql);
                    $goods_prices = get_final_price_new($value['goods_id'], $value['goods_number']+$pre['goods_number'], true, $value['goods_attr_id'], $_SESSION['area_rate_id'], $product_sn);

                    $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = goods_number + ".$value['goods_number'].",goods_price = " . $goods_prices . "  where rec_id = ".$value['prec_id'];
                    $GLOBALS['db']->query($sql);
                    $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where  rec_id = ".$value['rec_id'];
                    $GLOBALS['db']->query($sql);
                }else{
                    if(empty($value['prec_id']) && empty($value['goods_sn'])){
                        $sql = "delete from ".$GLOBALS['ecs']->table('cart')." where  rec_id = ".$value['rec_id'];
                        $GLOBALS['db']->query($sql);
                    }else {
                        $sql = "select product_sn from " . $GLOBALS['ecs']->table('products') . " where product_id = " . $value['product_id'];
                        $product_sn = $GLOBALS['db']->getOne($sql);
                        $goods_price = get_final_price_new($value['goods_id'], $value['goods_number'], true, $value['goods_attr_id'], $_SESSION['area_rate_id'], $product_sn);

                        $sql = "update " . $GLOBALS['ecs']->table('cart') . " set rec_id = " . $value['prec_id'] . ", goods_price = " . $goods_price . ", zengsong = 0, activity_id = 0, song_buy = 0, prec_id=0, song_num=0, carta_id=0,extension_code='' " .
                            " where rec_id = " . $value['rec_id'];

                        $GLOBALS['db']->query($sql);
                    }
                }
            }

        }
    }
    elseif ($row['carta_id']!=0)//正常商品，不过赠送过赠品或折扣过。需要还原优惠或删除赠品
    {
        if(!empty($row['carta_id'])) {

            $catid = explode(',', $row['carta_id']);

            $rec_list = array();

            foreach ($catid as $v) {

                $sql = "select rec_id,carta_id from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$v."%'";
                $r = $GLOBALS['db']->getAll($sql);
                foreach($r as $vr){
                    if($vr['carta_id'] == $v){
                        $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=0,carta_id=0,extension_code=''". " where rec_id = ".$vr['rec_id'];
                        $GLOBALS['db']->query($sql);
                    }
                    else{
                        $catid = explode(',',$vr['carta_id']);
                        $rc = array();
                        foreach($catid as $vc){
                            if($vc != $v){
                                $rc[]=$vc;
                            }
                        }
                        $cat = implode(',',$rc);

                        $sql = "select buy_goods_id from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$v;
                        $by_goods = $GLOBALS['db']->getOne($sql);
                        $by_list = unserialize($by_goods);
                        $snum = 0;
                        foreach($by_list as $vb){
                            if($vb['rec_id'] == $vr['rec_id']){
                                $snum = $vb['ysong'];
                            }
                        }
                        $sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=song_num - $snum,carta_id='".$cat."'".
                            " where rec_id = ".$vr['rec_id'];
                        $GLOBALS['db']->query($sql);
                    }
                }


                $sql = "delete from " . $GLOBALS['ecs']->table('cart') . " where carta_id = " . $v." and zengsong > 0";
                $GLOBALS['db']->query($sql);

                $sql = "delete from " . $GLOBALS['ecs']->table('cart_activity') . " where recs_id = " . $v;
                $GLOBALS['db']->query($sql);
            }
        }
    }
}

/**
 * 删除购物车中的商品
 *
 * @access  public
 * @param   integer $id
 * @return  void
 */
function flow_drop_cart_goods($id)
{
    /* 取得商品id */
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('cart'). " WHERE rec_id = '$id'";
    $row = $GLOBALS['db']->getRow($sql);
    if ($row)
    {

        //如果是超值礼包
        if ($row['extension_code'] == 'package_buy')
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE session_id = '" . SESS_ID . "' " .
                    "AND rec_id = '$id' LIMIT 1";
            $sqlpackageid = $GLOBALS['db']->getOne('SELECT goods_id FROM'.$GLOBALS['ecs']->table('cart')." WHERE rec_id = '$id' ");
            $sqlall = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE session_id = '" . SESS_ID . "' " .
                    "  AND extension_code='package_buy".$sqlpackageid."' and package_num=".$row['package_num'];
           
            $GLOBALS['db']->query($sqlall);//级联删除购物车中礼包商品
        }
        elseif ($row['extension_code'] == 'package_buy_all')
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' " .
                "AND rec_id = '$id' LIMIT 1";
            $sqlpackageid = $GLOBALS['db']->getOne('SELECT carta_id FROM'.$GLOBALS['ecs']->table('cart')." WHERE rec_id = '$id' ");
            $sqlall = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' and activity_id>0 " .
                "  AND carta_id = '".$sqlpackageid."'";

            $GLOBALS['db']->query($sqlall);//级联删除购物车中礼包商品
            $sqlg = "UPDATE " .$GLOBALS['ecs']->table('cart').
                " SET song_num=0 WHERE carta_id=0 and session_id = '" . SESS_ID . "'";
            $GLOBALS['db']->query($sqlg);
        }

        //如果是普通商品，同时删除所有赠品及其配件
        elseif ($row['parent_id'] == 0 && $row['is_gift'] == 0)
        {
            /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
            $sql = "SELECT c.rec_id
                    FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('group_goods') . " AS gg, " . $GLOBALS['ecs']->table('goods'). " AS g
                    WHERE gg.parent_id = '" . $row['goods_id'] . "'
                    AND c.goods_id = gg.goods_id
                    AND c.parent_id = '" . $row['goods_id'] . "'
                    AND c.extension_code <> 'package_buy'
                    AND gg.goods_id = g.goods_id
                    AND g.is_alone_sale = 0";
            $res = $GLOBALS['db']->query($sql);
            $_del_str = $id . ',';
            while ($id_alone_sale_goods = $GLOBALS['db']->fetchRow($res))
            {
                $_del_str .= $id_alone_sale_goods['rec_id'] . ',';
            }
            $_del_str = trim($_del_str, ',');

            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE session_id = '" . SESS_ID . "' " .
                    "AND (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)";
        }

        //如果不是普通商品，只删除该商品即可
        else
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE session_id = '" . SESS_ID . "' " .
                    "AND rec_id = '$id' LIMIT 1";
        }
        preg_match('/package_buy(\d+)/', $row['extension_code'], $r);
        
        if(empty($r[1]))
        {
        
        	$GLOBALS['db']->query($sql);
            $sqlg = "UPDATE " .$GLOBALS['ecs']->table('cart').
                " SET song_num=0 WHERE carta_id=0 and session_id = '" . SESS_ID . "'";
            $GLOBALS['db']->query($sqlg);
        	
        }else 
        {
        	show_message("禮包禮品不能單獨刪除。想要刪除請刪除禮包");
        }
    }

    flow_clear_cart_alone();
}

/**
 * 删除购物车中不能单独销售的商品
 *
 * @access  public
 * @return  void
 */
function flow_clear_cart_alone()
{
    /* 查询：购物车中所有不可以单独销售的配件 */
    $sql = "SELECT c.rec_id, gg.parent_id
            FROM " . $GLOBALS['ecs']->table('cart') . " AS c
                LEFT JOIN " . $GLOBALS['ecs']->table('group_goods') . " AS gg ON c.goods_id = gg.goods_id
                LEFT JOIN" . $GLOBALS['ecs']->table('goods') . " AS g ON c.goods_id = g.goods_id
            WHERE c.session_id = '" . SESS_ID . "'
            AND c.extension_code <> 'package_buy'
            AND gg.parent_id > 0
            AND g.is_alone_sale = 0";
    $res = $GLOBALS['db']->query($sql);
    $rec_id = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $rec_id[$row['rec_id']][] = $row['parent_id'];
    }

    if (empty($rec_id))
    {
        return;
    }

    /* 查询：购物车中所有商品 */
    $sql = "SELECT DISTINCT goods_id
            FROM " . $GLOBALS['ecs']->table('cart') . "
            WHERE session_id = '" . SESS_ID . "'
            AND extension_code <> 'package_buy'";
    $res = $GLOBALS['db']->query($sql);
    $cart_good = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $cart_good[] = $row['goods_id'];
    }

    if (empty($cart_good))
    {
        return;
    }

    /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
    $del_rec_id = '';
    foreach ($rec_id as $key => $value)
    {
        foreach ($value as $v)
        {
            if (in_array($v, $cart_good))
            {
                continue 2;
            }
        }

        $del_rec_id = $key . ',';
    }
    $del_rec_id = trim($del_rec_id, ',');

    if ($del_rec_id == '')
    {
        return;
    }

    /* 删除 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ."
            WHERE session_id = '" . SESS_ID . "'
            AND rec_id IN ($del_rec_id)";
    $GLOBALS['db']->query($sql);
}

/**
 * 比较优惠活动的函数，用于排序（把可用的排在前面）
 * @param   array   $a      优惠活动a
 * @param   array   $b      优惠活动b
 * @return  int     相等返回0，小于返回-1，大于返回1
 */
function cmp_favourable($a, $b)
{
    if ($a['available'] == $b['available'])
    {
        if ($a['sort_order'] == $b['sort_order'])
        {
            return 0;
        }
        else
        {
            return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
        }
    }
    else
    {
        return $a['available'] ? -1 : 1;
    }
}

/**
 * 取得某用户等级当前时间可以享受的优惠活动
 * @param   int     $user_rank      用户等级id，0表示非会员
 * @return  array
 */
function favourable_list($user_rank)
{
    /* 购物车中已有的优惠活动及数量 */
    $used_list = cart_favourable();

    /* 当前用户可享受的优惠活动 */
    $favourable_list = array();
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();
    $sql = "SELECT * " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND start_time <= '$now' AND end_time >= '$now'" .
            " AND act_type = '" . FAT_GOODS . "'" .
            " ORDER BY sort_order";
    $res = $GLOBALS['db']->query($sql);
    while ($favourable = $GLOBALS['db']->fetchRow($res))
    {
        $favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
        $favourable['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
        $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
        $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
        $favourable['gift']       = unserialize($favourable['gift']);

        /* 优惠范围内的商品总额 */
        $amount = cart_favourable_amount($favourable);
        $newarray['gift'] = $favourable['gift'] ;
        foreach ($favourable['gift'] as $key => $value)
        {
        	if($amount>=$favourable['gift'][$key]['gift_minprice'])
        	{
        		$num = $value['gift_num'];
        		foreach ($newarray['gift'] as $k=>$v)
        		{
        			if($value['id']==$v['id']&&$value['gift_minprice']!=$v['gift_minprice']&&$value['gift_pan']!=1&&$v['gift_pan']!=1)
        			{
        				
        				$num = $num+ $v['gift_num'];
        				
        				$favourable['gift'][$key]['pan'] =1;
        				$newarray['gift'][$k] =1;
        				unset($favourable['gift'][$k]);
        			}
        			unset($newarray['gift'][$key]);
        		}
        		if(isset($favourable['gift'][$key]))
        		{
        			$favourable['gift'][$key]['gift_num'] = $num;
        		}
        		//
        	}else
        	{
        		unset($favourable['gift'][$key]);
        	}
        	
        }

        foreach ($favourable['gift'] as $key => $value)
        {
            $sql = "SELECT goods_thumb FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$value['id'];

            $favourable['gift'][$key]['goods_thumb'] = $GLOBALS['db']->getOne($sql);

            $properties = get_goods_properties_two($value['id']);  // 获得商品的规格和属性
            $de_attr = array();
            foreach($properties['spe'] as $vc){
                foreach($vc['values'] as $vaa){
                    if($vaa['css'] == 1){
                        $de_attr[]=$vaa['id'];
                    }
                }
            }
            $favourable['gift'][$key]['goods_attr_id'] = implode(',',$de_attr);
            $favourable['gift'][$key]['goods_spe'] = $properties['spe'];// 商品规格


            $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = ".$value['id'];
            $is_sale = $GLOBALS['db']->getOne($sql);
            if(!$is_sale)
            {
                unset($favourable['gift'][$key]);
            }
            
            $sql = "SELECT  sum(goods_number) AS num " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND goods_id = ".$value['id'].
            " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
            " AND is_gift =" .$favourable['act_id'].
            " GROUP BY is_gift";
            $total_song = $GLOBALS['db']->getOne($sql);
            if($total_song>=$favourable['gift'][$key]['gift_num'])
            {
            	
            	$favourable['gift'][$key]['gift_num']=0;
            }
           /* if($amount>=$favourable['gift'][$key]['gift_minprice'])
            {
            	 
            }else
            {
            	unset($favourable['gift'][$key]);
            }*/
        }

        $favourable['act_range_desc'] = act_range_desc($favourable);
       
        $favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);

        /* 是否能享受 */
        $favourable['available'] = favourable_available($favourable);
       
        if ($favourable['available'])
        {
            /* 是否尚未享受 */
            $favourable['available'] = !favourable_used($favourable, $used_list);
			$favourable_list[] = $favourable;
			
        }

        
    }

    return $favourable_list;
}

/**
 * 根据购物车判断是否可以享受某优惠活动
 * @param   array   $favourable     优惠活动信息
 * @return  bool
 */
function favourable_available($favourable)
{
    /* 会员等级是否符合 */
    $user_rank = $_SESSION['user_rank'];
    if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false)
    {
        return false;
    }

    /* 优惠范围内的商品总额 */
    $amount = cart_favourable_amount($favourable);

    /* 金额上限为0表示没有上限 */
    return $amount >= $favourable['min_amount'] &&
        ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
}

/**
 * 取得优惠范围描述
 * @param   array   $favourable     优惠活动
 * @return  string
 */
function act_range_desc($favourable)
{
    if ($favourable['act_range'] == FAR_BRAND)
    {
        $sql = "SELECT brand_name as goods_name ,brand_id as goods_id FROM " . $GLOBALS['ecs']->table('brand') .
                " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
        $goods_list = $GLOBALS['db']->getAll($sql);
    
        return $goods_list;
    }
    elseif ($favourable['act_range'] == FAR_CATEGORY)
    {
        $sql = "SELECT cat_name as goods_name,cat_id as goods_id FROM " . $GLOBALS['ecs']->table('category') .
                " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
        $goods_list = $GLOBALS['db']->getAll($sql);
    
        return $goods_list;
    }
    elseif ($favourable['act_range'] == FAR_GOODS)
    {
        $sql = "SELECT goods_name,goods_id FROM " . $GLOBALS['ecs']->table('goods') .
                " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
        $goods_list = $GLOBALS['db']->getAll($sql);
    
        return $goods_list;
    }
    else
    {
        return '';
    }
}

/**
 * 取得购物车中已有的优惠活动及数量
 * @return  array
 */
function cart_favourable()
{
    $list = array();
    $sql = "SELECT is_gift, COUNT(*) AS num " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "'" .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
            " AND is_gift > 0" .
            " GROUP BY is_gift";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $list[$row['is_gift']] = $row['num'];
    }

    return $list;
}

/**
 * 购物车中是否已经有某优惠
 * @param   array   $favourable     优惠活动
 * @param   array   $cart_favourable购物车中已有的优惠活动及数量
 */
function favourable_used($favourable, $cart_favourable)
{
	
    if ($favourable['act_type'] == FAT_GOODS)
    {
        return isset($cart_favourable[$favourable['act_id']]) &&
            $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
            $favourable['act_type_ext'] > 0;
    }
    else
    {
        return isset($cart_favourable[$favourable['act_id']]);
    }
}

/**
 * 添加优惠活动（赠品）到购物车
 * @param   int     $act_id     优惠活动id
 * @param   int     $id         赠品id
 * @param   float   $price      赠品价格
 */
function add_gift_to_cart($act_id, $id, $price)
{
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
                "goods_number, is_real, extension_code, parent_id, is_gift, rec_type,areaid ) ".
            "SELECT '$_SESSION[user_id]', '" . SESS_ID . "', goods_id, goods_sn, goods_name, market_price, ".
                "'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " .",$_SESSION[area_rate_id]   ".
            "  FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id = '$id'";
    $GLOBALS['db']->query($sql);
}

/**
 * 添加优惠活动（非赠品）到购物车
 * @param   int     $act_id     优惠活动id
 * @param   string  $act_name   优惠活动name
 * @param   float   $amount     优惠金额
 */
function add_favourable_to_cart($act_id, $act_name, $amount)
{
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . "(" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
                "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
            "VALUES('$_SESSION[user_id]', '" . SESS_ID . "', 0, '', '$act_name', 0, ".
                "'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
    $GLOBALS['db']->query($sql);
}

/**
 * 取得购物车中某优惠活动范围内的总金额
 * @param   array   $favourable     优惠活动
 * @return  float
 */
function cart_favourable_amount($favourable)
{
    /* 查询优惠范围内商品总额的sql */
    $sql = "SELECT SUM(c.goods_price * c.goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE c.goods_id = g.goods_id " .
            "AND c.session_id = '" . SESS_ID . "' " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
            "AND c.is_gift = 0 " .
           // "AND c.zengsong = 0 " .//过滤已参与买几活动的商品2016.9.23
           // "AND c.extension_code not like '%package_buy%' " .//过滤已参与买几活动的商品2016.9.23
            "AND c.goods_id > 0 ";

    /* 根据优惠范围修正sql */
    if ($favourable['act_range'] == FAR_ALL)
    {
        // sql do not change
    }
    elseif ($favourable['act_range'] == FAR_CATEGORY)
    {
        /* 取得优惠范围分类的所有下级分类 */
        $id_list = array();
        $cat_list = explode(',', $favourable['act_range_ext']);
        foreach ($cat_list as $id)
        {
            $id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
        }

        $sql .= "AND g.goods_id " . db_create_in($id_list);
    }
    elseif ($favourable['act_range'] == FAR_BRAND)
    {
        $id_list = explode(',', $favourable['act_range_ext']);

        $sql .= "AND g.goods_id " . db_create_in($id_list);
    }
    else
    {
        $id_list = explode(',', $favourable['act_range_ext']);

        $sql .= "AND g.goods_id " . db_create_in($id_list);
    }

    /* 优惠范围内的商品总额 */
    return $GLOBALS['db']->getOne($sql);
}
/*还原购物车里面的活动商品*/
function update_favourable_activity_price()
{
	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  parent_id = 0,m_pd='',zk_shop_price=0,zk_type=0 where   session_id='" . SESS_ID . "'";
	$GLOBALS['db']->query($sql);
	$sql = "select c.rec_id,c.goods_number, c.goods_id,g.parent_id from ".$GLOBALS['ecs']->table('cart')." as c,".$GLOBALS['ecs']->table('group_goods')." as g where  c.goods_id=g.goods_id and c.parent_id=0  and c.session_id='" . SESS_ID . "'";
	$p_goods_list = $GLOBALS['db']->getAll($sql);

	foreach($p_goods_list as $v){
		$sql = "select sum(goods_number) from ".$GLOBALS['ecs']->table('cart')." where session_id='" . SESS_ID
		. "' and goods_id=".$v['parent_id'];
		$rs = $GLOBALS['db']->getOne($sql);//基件數

		$sql = "select sum(goods_number) from ".$GLOBALS['ecs']->table('cart')." where session_id='" . SESS_ID
		. "' and parent_id=".$v['parent_id'];
		$rs_p = $GLOBALS['db']->getOne($sql);//原配件和
		if(!empty($rs) && $rs_p < $rs){
			$p_price = get_price_area($v['parent_id'],0,'pei_price',$v['goods_id'],0,$_SESSION['area_rate_id']);//取地区价格

			if($v['goods_number'] >= $rs-$rs_p){

				$p_cart = $GLOBALS['db']->getRow('select * from '.$GLOBALS['ecs']->table('cart')." where rec_id=".$v['rec_id']);

				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = ".($rs-$rs_p).",goods_price=".$p_price.",parent_id=".$v['parent_id']." WHERE rec_id=".$v['rec_id'];
				$GLOBALS['db']->query($sql);

				if($v['goods_number'] - ($rs-$rs_p) > 0){
					$p_cart['goods_number'] = $v['goods_number'] - ($rs-$rs_p);
					unset($p_cart['rec_id']);
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $p_cart, 'INSERT');
				}
			}else{
				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=".$p_price.",parent_id=".$v['parent_id']." WHERE rec_id=".$v['rec_id'];
				$GLOBALS['db']->query($sql);
			}
		}
	}

	$sql = "DELETE FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '".SESS_ID."' and extension_code='package_buy_all' and cf_huodong=0 and parent_id=0";
	$GLOBALS['db']->query($sql);
	$sql = "select * from ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '".SESS_ID."' and activity_id>0  and cf_huodong=0 and parent_id=0";
	$cart_list = $GLOBALS['db']->getAll($sql);

	/*还原购物车里的活动商品*/
	foreach ($cart_list as $key=>$value)
	{
		$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$value['product_id']);
		$goods_price = get_final_price_new($value['goods_id'],$value['goods_number'],true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);

		$sql = "update ".$GLOBALS['ecs']->table('cart')." set zengsong=0,order_c=0,activity_id=0,song_buy=0,prec_id=0,extension_code='',carta_id=0,is_jianshu=0,song_num=0,goods_price=".$goods_price.
		" where rec_id = ".$value['rec_id'];
		$GLOBALS['db']->query($sql);
	}
	$sql = "update ".$GLOBALS['ecs']->table('cart')." set order_c=0 ";

	$GLOBALS['db']->query($sql);
	$sql = "select * from ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '".SESS_ID."' and activity_id=0 and extension_code='' and goods_price>0  and cf_huodong=0 and parent_id=0";
	$cart_listss = $GLOBALS['db']->getAll($sql);
	foreach ($cart_listss as $key=>$value)
	{
		$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$value['product_id']);
		$goods_price = get_final_price_new1($value['goods_id'],$value['goods_number'],true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);

		$sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_price=".$goods_price.
		" where rec_id = ".$value['rec_id'];
		$GLOBALS['db']->query($sql);
	}


	$sql = "select rec_id, sum(goods_number) as goods_num,count(*) as num,goods_id,goods_attr_id from ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '".SESS_ID."' and activity_id=0 and extension_code='' and cf_huodong=0 and parent_id=0 group by goods_id,goods_attr_id";
	$cgoods = $GLOBALS['db']->getAll($sql);
	foreach($cgoods as $vg){
		if($vg['num'] > 1){
			$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = ".$vg['goods_num']." WHERE rec_id=".$vg['rec_id'];
			$GLOBALS['db']->query($sql);

			$sql = "DELETE FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '".SESS_ID."' and goods_id=".$vg['goods_id']." and goods_attr_id='".$vg['goods_attr_id']."' and rec_id<>".$vg['rec_id'];
			$GLOBALS['db']->query($sql);
		}
	}

	/*还原购物车里的活动商品*/
	/*删除活动列表购物车关联的数据*/
	$sql = "DELETE FROM ".$GLOBALS['ecs']->table('cart_activity')." WHERE session_id = '".SESS_ID."'";
	$GLOBALS['db']->query($sql);

	/*重新计算商品参加单次活动*/
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('favourable_activity') .
	" where act_type >2 and user_rank in(".$_SESSION['user_rank'].") and areaid like '%".$_SESSION['area_rate_id']."%'"." and  act_range_ext <> '' and ".gmtime()." >= start_time and ".gmtime()." <= end_time ORDER BY sort_order ASC,end_time DESC";

	$res = $GLOBALS['db']->query($sql);
	$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id ='".SESS_ID."' and cf_huodong = 0 and parent_id=0";//查看是否有参与重复活动的商品
	$new_cartlist = $GLOBALS['db']->getAll($sql);//清楚活动后的购物车商品列表
	$list = array();
	$favourable_lists = array();
	$j=1;
	$act_range_ext_list = '';
	$cun_hd = array();
	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$act_range_ext_list = $act_range_ext_list.','.$row['act_range_ext'];

		$cun_hd[$row['act_id']] = $row['act_range_ext'];
	}
	$act_range_ext_list = explode(',',$act_range_ext_list);

	$cf_goods = FetchRepeatMemberInArray($act_range_ext_list);//取重复参加活动的商品集合
	$hy_cf = 0;//购物车是否含有参与重复活动的商品
	/*
	 foreach ($new_cartlist as $key=>$value)
	 {
	foreach ($cf_goods as $k=>$v)
	{
	if($value['goods_id']==$v)
	{
	foreach ($cun_hd as $kk=>$vv) //过滤掉重复的商品集合
	{
	$gl_list = explode(',', $vv);
	foreach ($gl_list as $kkk=>$vvv)
	{
	if($vvv==$value['goods_id'])
	{
	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  cf_huodong = 0 WHERE goods_id in(".$vv.") and session_id ='".SESS_ID."'";
	$GLOBALS['db']->query($sql);
	}
	}
	}
	}
	}
	}
	if(empty($new_cartlist))//没有了参与多个活动的商品把划出去的商品，分回进正常商品
	{
	foreach ($new_cartlist as $key=>$value)
	{
	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  cf_huodong = 0 WHERE rec_id =(".$value['rec_id'].") and session_id ='".SESS_ID."'";
	$GLOBALS['db']->query($sql);
	}
	}*/


	if($hy_cf==0)//购物车不含有参加重复活动的商品
	{

		$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('favourable_activity') .
		" where act_type >2 and act_range_ext <> '' and areaid like '%".$_SESSION['area_rate_id']."%'"." and ".gmtime()." >= start_time and ".gmtime()." <= end_time ORDER BY sort_order ASC,end_time DESC";

		$res = $GLOBALS['db']->query($sql);
		$huodonglist = array();
		$kkvalue = 1;
		$sql = "SELECT goods_id,order_c,rec_id  FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "'  and dl_dlsp <>1 ".
				" AND parent_id = 0 " .
				" AND extension_code not like '%package_buy%' " .
				" AND rec_type = 'CART_GENERAL_GOODS' AND zengsong=0 and cf_huodong=0 and parent_id=0  AND goods_number>=song_num  order by market_price desc ";
			
		$cart_order = $GLOBALS['db']->getAll($sql);//重新排序，按活動少的排先

		

		while ($row = $GLOBALS['db']->fetchRow($res)) { //循环所有活动，把商品加入合适的活动里
			$user_ranklist = explode(',', $row['user_rank']);
			$puser = 0;
			foreach ($user_ranklist as $kk=>$vv)
			{
				if($vv == $_SESSION['user_rank'])
				{
					$puser = 1;
				}
			}
			if($puser == 0)
			{
				continue;
			}

			$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "'  and dl_dlsp <>1 AND goods_id in(".$row['act_range_ext'].")".
					" AND parent_id = 0 " .
					" AND extension_code not like '%package_buy%' " .
					" AND rec_type = 'CART_GENERAL_GOODS' AND zengsong=0 and cf_huodong=0 and parent_id=0  AND goods_number>=song_num  order by order_c asc ";

			$number_buy = $GLOBALS['db']->getAll($sql);

			if($number_buy)
			{//
				$row['buy'] = unserialize($row['buy']);
				$cart_number = 0;
				foreach ($number_buy as $key=>$value)
				{
					$cart_number = $cart_number+ ($value['goods_number']-$value['song_num']);//能参与活动的正常商品的数量
				}
				if($row['act_type'] == 5) //新加入的活動：買夠几總價多少
				{
					$now_buy = 0 ;
					$now_song = 0;
					foreach ($row['buy'] as $keys=>$values)
					{
						foreach ($values as $zk=>$zv)
						{
							if($zk == 'buy')
							{
								if($zv<=$cart_number && $zv >=$now_buy)
								{
									$now_buy = $zv;
									$now_song = $values[$_SESSION['area_rate_id']];
								}
							}
						}
					}

					if(!empty($now_buy) && !empty($now_song)){
						$zengsong = array(
								'session_id'    => SESS_ID,
								'act_id'       => $row['act_id'],
								'buy_goods_id'      => 0,
								'buy'      => $now_buy,
								'song'    => 0,
								'ysong'    => $now_buy,
								'is_finish'  => 1

						);
						$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
						$cart_id = $GLOBALS['db']->insert_id();
						//插入一条总价的记录
						$zongjia = array(
								'user_id' => $_SESSION['user_id'],
								'session_id' => SESS_ID,
								'goods_id' => 0,
								'goods_sn' => 0,
								'goods_name' => $row['act_name'],
								'market_price' =>0,
								'goods_price' =>$now_song,
								'goods_number' => 1,
								'goods_attr' => '',
								'is_real' => 1,
								'extension_code'=>'package_buy_all',
								'parent_id'=>0,
								'rec_type' =>0,
								'is_gift' =>0,
								'is_shipping' => 0,
								'can_handsel'=> 0,
								'goods_attr_id'=>'',
								'product_id'=>0,
								'suppliers_id'=>0,
								'fen_cheng'=>0,
								'areaid'=>$_SESSION['area_rate_id'],
								'extension_id'=>0,
								'fuwu'=> 0,
								'zengsong'=>5,
								'activity_id' => $row['act_id'],
								'song_buy'=>$now_buy,
								'prec_id' => 0,
								'song_num' => 0,
								'carta_id' =>$cart_id,
								'is_jianshu' =>0,
						);
						$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zongjia, 'INSERT');

						$ni = $now_buy;
						foreach($number_buy as $vgoods){
							if(!empty($ni)) {
								if ($ni - $vgoods['goods_number'] < 0) {

									$goods_cart = $vgoods;
									$goods_cart['goods_price'] = 0;
									$goods_cart['goods_number'] = $ni;
									$goods_cart['zengsong'] = 5;
									$goods_cart['song_buy'] = $now_buy;
									$goods_cart['activity_id'] = $row['act_id'];
									$goods_cart['extension_code'] = 'package_buy_all'.$row['act_id'];
									$goods_cart['carta_id'] = $cart_id;
									$goods_cart['prec_id'] = $goods_cart['rec_id'];
									unset($goods_cart['rec_id']);
									$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');

									$nu = $vgoods['goods_number']-$ni;

									$goods_price = $now_song/$now_buy;
									$sql = "select goods_name from ".$GLOBALS['ecs']->table('goods').' WHERE goods_id='.$vgoods['goods_id'];
									$name = $GLOBALS['db']->getOne($sql);
									$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_name='".$name.' ['.$row['act_name']."]', goods_price=$goods_price,song_num=$nu,goods_number=$nu,carta_id = '".$cart_id."' " .
											" WHERE rec_id=".$vgoods['rec_id'];
									$GLOBALS['db']->query($sql);

									$ni = 0;
								}
								else {
									$goods_cart = $vgoods;
									$goods_cart['goods_price'] = 0;
									$goods_cart['zengsong'] = 5;
									$goods_cart['song_buy'] = $now_buy;
									$goods_cart['activity_id'] = $row['act_id'];
									$goods_cart['extension_code'] = 'package_buy_all'.$row['act_id'];
									$goods_cart['carta_id'] = $cart_id;
									$goods_cart['prec_id'] = $goods_cart['rec_id'];
									unset($goods_cart['rec_id']);
									$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');

									$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$vgoods['rec_id'];
									$GLOBALS['db']->query($sql);

									$ni = $ni - $vgoods['goods_number'];
								}
							}
							else{
								$goods_price = ceil($now_song/$now_buy);

								$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=$goods_price,song_num=goods_number " .
								" WHERE rec_id=".$vgoods['rec_id'];
								$GLOBALS['db']->query($sql);
							}
						}
					}
					//var_dump($now_buy.'---->'.$now_song);//参加完一次活动还得判断商品数量是否够参加多一次活动。直到不能参加活动在跳出
				}
				elseif($row['act_type'] == 4)//新加入的活動：買几打折設置
				{
					$now_buy = 0 ;
					$now_song = 0;
					foreach ($row['buy'] as $keys=>$values)
					{
						if($keys<=$cart_number && $keys >=$now_buy)
						{
							$now_buy = $keys;
							$now_song = $values;
						}
					}

					if(!empty($now_buy) && !empty($now_song)){
						//插入赠送商品，减少购物车正常商品的数量
						$zengsong = array(
								'session_id'    => SESS_ID,
								'act_id'       => $row['act_id'],
								'buy_goods_id'      => 0,
								'buy'      => $now_buy,
								'song'    => $now_song,
								'ysong'    => $now_buy,
								'is_finish'  => 1
						);
						$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
						$cart_id = $GLOBALS['db']->insert_id();

						$ni = $now_buy;
						foreach($number_buy as $vgoods){
							$spec_price_attr = 0;
							if (!empty($vgoods['goods_attr_id'])) {
								$spec_price_attr = spec_price($vgoods['goods_attr_id'], $vgoods['goods_id'], $vgoods['areaid']);
							}
							$goods_price_g = get_price_area($vgoods['goods_id'],0,'shop_price',0,0,$vgoods['areaid']);
							if(!empty($ni)) {
								if ($ni - $vgoods['goods_number'] < 0) {

									$goods_cart = $vgoods;
									$goods_cart['goods_price'] =  ceil(($goods_price_g+$spec_price_attr)*$now_song/10);
									$goods_cart['goods_number'] = $ni;
									$goods_cart['zengsong'] = 3;
									$goods_cart['song_buy'] = $now_buy;
									$goods_cart['activity_id'] = $row['act_id'];
									$goods_cart['carta_id'] = $cart_id;
									$goods_cart['prec_id'] = $goods_cart['rec_id'];
									unset($goods_cart['rec_id']);
									$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
									$nu = $vgoods['goods_number']-$ni;
									$goods_prices = ceil(($goods_price_g+$spec_price_attr)*$now_song/10);
									$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=$goods_prices,song_num=$nu,goods_number=$nu,carta_id = '".$cart_id."' " .
											" WHERE rec_id=".$vgoods['rec_id'];
									$GLOBALS['db']->query($sql);
									$ni = 0;
								}
								else {
									$goods_cart = $vgoods;
									$goods_cart['goods_price'] = ceil(($goods_price_g+$spec_price_attr)*$now_song/10);
									$goods_cart['zengsong'] = 3;
									$goods_cart['song_buy'] = $now_buy;
									$goods_cart['activity_id'] = $row['act_id'];
									$goods_cart['carta_id'] = $cart_id;
									$goods_cart['prec_id'] = $goods_cart['rec_id'];
									unset($goods_cart['rec_id']);
									$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
									$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$vgoods['rec_id'];
									$GLOBALS['db']->query($sql);
									$ni = $ni - $vgoods['goods_number'];
								}
							}
							else{
								$goods_pricess = ceil(($goods_price_g+$spec_price_attr)*$now_song/10);

								$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=$goods_pricess,song_num=goods_number " .
								" WHERE rec_id=".$vgoods['rec_id'];
								$GLOBALS['db']->query($sql);
							}
						}
					}
				}
				elseif($row['act_type'] == 3)//買几送几設置
				{
					$aract = unserialize($row['gift']);
					if(empty($aract)) { //没设有赠品
						$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "'  and dl_dlsp <>1 AND goods_id in(".$row['act_range_ext'].")".
								" AND parent_id = 0 " .
								" AND extension_code not like '%package_buy%' " .
								" AND rec_type = 'CART_GENERAL_GOODS' AND zengsong=0 and cf_huodong=0 and parent_id=0  AND goods_number>=song_num  order by goods_price desc";
						$gif_list = $GLOBALS['db']->getAll($sql);
						$now_buy = array();
						$now_song = array();
						$j = 1;
						$n=$cart_number;
						while($j){
							$now_buys=0;
							$now_songs=0;
							foreach ($row['buy'] as $keys => $values) {
								if ($keys <= $n && $keys >= $now_buys) {
									$now_buys = $keys;
									$now_songs = $values;
								}
							}
							if(empty($now_buys)){
								$j=0;
							}else{
								$now_buy[]=$now_buys;
								$now_song[]=$now_songs;
								$n = $n-$now_buys-$now_songs;
							}
						}
						if (!empty($now_buy) && !empty($now_song)) {
							foreach($now_buy as $k=>$v){
								//插入赠送商品，减少购物车正常商品的数量
								$zengsongss = array(
										'session_id'    => SESS_ID,
										'act_id'       => $row['act_id'],
										'buy_goods_id'      => 0,
										'buy'      => $v,
										'song'    => $now_song[$k],
										'ysong'    => $now_song[$k],
										'is_finish'  => 1

								);
								$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsongss, 'INSERT');
								$cid = $GLOBALS['db']->insert_id();

								$n = $v-$now_song[$k];
								$m = $now_song[$k];


								foreach($gif_list as $ky=>$vy){

									if(!empty($n)&&$n!=null){

										if($n-$vy['goods_number']<0){
											$goods_cart = $gif_list[$ky];
											$goods_cart['goods_number'] = $n;
											$goods_cart['song_buy'] = $v;
											$goods_cart['song_num'] = $now_song[$k];
											$goods_cart['activity_id'] = $row['act_id'];
											$goods_cart['carta_id'] = $cid;
											unset($goods_cart['rec_id']);
											$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
											$nu = $vy['goods_number']-$n;

											if($nu >= $m){
												$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET zengsong=1,goods_price=0, goods_number=$m, song_buy = $v,song_num=$now_song[$k],activity_id= ".$row['act_id'] .",carta_id= '".$cid."'".
														" WHERE rec_id=".$gif_list[$ky]['rec_id'];
												$GLOBALS['db']->query($sql);
												if($nu-$m>0)
												{
													$goods_cart['goods_number'] = $nu-$m;
													$goods_cart['song_buy'] = 0;
													$goods_cart['song_num'] = 0;
													$goods_cart['activity_id'] = 0;
													$goods_cart['carta_id'] = 0;
													unset($goods_cart['rec_id']);
													$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
													$reid = $GLOBALS['db']->insert_id();
													$goods_cart['rec_id'] = $reid;

													$gif_list[$ky] = $goods_cart;
												}else{
													unset($gif_list[$ky]);
												}
												$m=0;

											}else{
												$m=$m-$nu;
												$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET zengsong=1,goods_price=0, goods_number=$nu, song_buy = $v,song_num=$now_song[$k],activity_id= ".$row['act_id'] .",carta_id= '".$cid."'".
														" WHERE rec_id=".$vy['rec_id'];
												$GLOBALS['db']->query($sql);
												unset($gif_list[$ky]);
											}
											$n=0;
										}else{

											$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET song_buy = $v,song_num=$now_song[$k],activity_id= ".$row['act_id'] .",carta_id= '".$cid."'".
													" WHERE rec_id=".$vy['rec_id'];

											$GLOBALS['db']->query($sql);
											$n=$n - $gif_list[$ky]['goods_number'];

											unset($gif_list[$ky]);

										}

									}
									else{

										if(!empty($m)){
											if($m <= $gif_list[$ky]['goods_number']){
												$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET zengsong=1,goods_price=0, goods_number=$m, song_buy = $v,song_num=$now_song[$k],activity_id= ".$row['act_id'] .",carta_id= '".$cid."'".
														" WHERE rec_id=".$vy['rec_id'];
												$GLOBALS['db']->query($sql);

												if($m!=$gif_list[$ky]['goods_number'])
												{
													$goods_cart = $gif_list[$ky];
													$goods_cart['goods_number'] = $gif_list[$ky]['goods_number']-$m;
													$goods_cart['song_buy'] = 0;
													$goods_cart['song_num'] = 0;
													$goods_cart['activity_id'] = 0;
													$goods_cart['carta_id'] = 0;
													unset($goods_cart['rec_id']);

													$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
													$reid = $GLOBALS['db']->insert_id();

													$goods_cart['rec_id'] = $reid;

													$gif_list[$ky] = $goods_cart;
												}else{
													unset($gif_list[$ky]);
												}

												$m=0;


											}else{
												$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET song_buy = $v,song_num=$now_song[$k],activity_id= ".$row['act_id'] .",carta_id= '".$cid."'".
														" WHERE rec_id=".$vy['rec_id'];
												$GLOBALS['db']->query($sql);
												$m=$m - $gif_list[$ky]['goods_number'];
												unset($gif_list[$ky]);
											}
										}
									}


								}
							}
						}
					}
					else{
						$now_buy = array();
						$now_song = array();
						$j = 1;
						$n=$cart_number;
						while($j){
							$now_buys=0;
							$now_songs=0;
							foreach ($row['buy'] as $keys => $values) {
								if ($keys <= $n && $keys >= $now_buys) {
									$now_buys = $keys;
									$now_songs = $values;
								}
							}
							if(empty($now_buys)){
								$j=0;
							}else{
								$now_buy[]=$now_buys;
								$now_song[]=$now_songs;
								$n = $n-$now_buys;
							}
						}

						if(!empty($now_buy)&&!empty($now_song)){
							foreach($now_buy as $k=>$v){
								//插入赠送商品，减少购物车正常商品的数量
								$zengsongss = array(
										'session_id'    => SESS_ID,
										'act_id'       => $row['act_id'],
										'buy_goods_id'      => 0,
										'buy'      => $v,
										'song'    => $now_song[$k],
										'ysong'    => 0,
										'is_finish'  => 0

								);
								$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsongss, 'INSERT');
								$cid = $GLOBALS['db']->insert_id();

								$n = $v;

								foreach($number_buy as $ky=>$vy){
									if(!empty($n)){
										if($n-$vy['goods_number']<0){
											$goods_cart = $vy;
											$goods_cart['goods_number'] = $n;
											$goods_cart['song_buy'] = $v;
											$goods_cart['song_num'] = $now_song[$k];
											$goods_cart['activity_id'] = $row['act_id'];
											$goods_cart['carta_id'] = $cid;
											unset($goods_cart['rec_id']);
											$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
											$nu = $vy['goods_number']-$n;

											$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number=$nu".
													" WHERE rec_id=".$vy['rec_id'];
											$GLOBALS['db']->query($sql);

											$number_buy[$ky]['goods_number'] = $nu;

											$n = 0;
										}else{
											$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET song_buy = $v,song_num=$now_song[$k],activity_id= ".$row['act_id'] .",carta_id= '".$cid."'".
													" WHERE rec_id=".$vy['rec_id'];
											$GLOBALS['db']->query($sql);
											unset($number_buy[$ky]);
											$n=$n - $vy['goods_number'];
										}

									}
								}
							}
						}

					}
				}
				else //其他活动
				{
					//$pan_hd = 0;
				}
			}
			else
			{
				//$pan_hd = 0;
			}
			//var_dump($cart_number);

			//$new_cartlist  参与一个活动后删除里面的商品直到完成
		}
	}else //购物车含有重复参加活动的商品
	{
		/*把重复商品参与的活动商品圈出来不做动作让客户选*/
		/*把重复商品参与的活动商品圈出来不做动作让客户选*/

		/*剩下的继续上面的购物车不含有参加重复活动的商品*/
		/*剩下的继续上面的购物车不含有参加重复活动的商品*/

	}


	/*重新计算商品参加单次活动*/

	$sql = "select * from ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '".SESS_ID."' and activity_id=0 and parent_id=0";
	$goods_ac=$GLOBALS['db']->getAll($sql);

	$sql = "select * from ".$GLOBALS['ecs']->table('cart')."WHERE session_id = '".SESS_ID."' and activity_id>0 and parent_id=0 group by activity_id";
	$actlist = $GLOBALS['db']->getAll($sql);

	foreach($actlist as $va){
		$sql = "select * from ".$GLOBALS['ecs']->table('favourable_activity')." where act_id=".$va['activity_id'];
		$actinfo = $GLOBALS['db']->getRow($sql);
		$ali = explode(',',$actinfo['act_range_ext']);
		$buy_act = unserialize($actinfo['buy']);

		foreach($goods_ac as $v){
			if(in_array($v['goods_id'],$ali)){
				$price_act = 0;
				if($actinfo['act_type'] == 5){
					foreach ($buy_act as $keys=>$values)
					{
						foreach ($values as $zk=>$zv)
						{
							if($zk == 'buy')
							{
								if($zv==$va['song_buy'])
								{
									$price_act = ceil($values[$_SESSION['area_rate_id']]/$zv);
									break;
								}
							}
						}
					}
				}
				elseif($actinfo['act_type'] == 4){
					$se = 0;
					foreach ($buy_act as $keys=>$values)
					{
						if($keys==$va['song_buy'])
						{
							$se = $values;
						}
					}

					if($se > 0){
						$spec_price_attr = 0;
						$goods_price_g = get_price_area($v['goods_id'],0,'shop_price',0,0,$v['areaid']);
						if (!empty($v['goods_attr_id'])) {
							$spec_price_attr = spec_price($v['goods_attr_id'], $v['goods_id'], $v['areaid']);
						}
						$price_act =ceil(($goods_price_g+$spec_price_attr)*$se/10);
					}
				}

				if($v['goods_price'] > $price_act && $price_act >0){
					$sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_price=$price_act,song_num=goods_number WHERE rec_id=".$v['rec_id'];
					$GLOBALS['db']->query($sql);
				}
			}
		}
	}





}

function FetchRepeatMemberInArray($array) {
	// 获取去掉重复数据的数组
	$unique_arr = array_unique ( $array );
	// 获取重复数据的数组
	$repeat_arr = array_diff_assoc ( $array, $unique_arr );
	return $repeat_arr;
}
/*
 * function updata_cart_vo()
{

    $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where is_jianshu=1 and carta_id=0 and session_id = '".SESS_ID."'";
    $list = $GLOBALS['db']->getAll($sql);
    //var_dump($list);die();
    foreach($list as $value){
        $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where goods_id = ".$value['goods_id']." and is_jianshu=0 and carta_id=0 and product_id = ".$value['product_id'].
            " and session_id = '".SESS_ID."'";
        $rec = $GLOBALS['db']->getRow($sql);
        //var_dump($rec);die();
        $product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$value['product_id']);
        if(!empty($rec['rec_id'])){
            $goods_price = get_final_price_new($rec['goods_id'],$rec['goods_number']+$value['goods_number'],true,$rec['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);

            $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number=goods_number+".$value['goods_number'].",goods_price=".$goods_price.
                " where rec_id = ".$rec['rec_id'];
            $GLOBALS['db']->query($sql);

            // 删除
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." where rec_id = ".$value['rec_id'];
            $GLOBALS['db']->query($sql);
        }else{
            $goods_price = get_final_price_new($value['goods_id'],-1,true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);
            $sql = "update ".$GLOBALS['ecs']->table('cart')." set is_jianshu=0,goods_price=".$goods_price.
                " where rec_id = ".$value['rec_id'];
            $GLOBALS['db']->query($sql);
        }
    }

    //赠送商品价格为0
    $sql="select c.rec_id from ".$GLOBALS['ecs']->table('cart')." as c, ".$GLOBALS['ecs']->table('favourable_activity')." as f where c.activity_id=f.act_id and f.act_type=3 and c.zengsong >0 and c.goods_price>0 and c.session_id = '".SESS_ID."'";
    $flist=$GLOBALS['db']->getAll($sql);

    foreach($flist as $v){
        $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_price=0 where rec_id=".$v['rec_id'];
        $GLOBALS['db']->query($sql);
    }

    $sql="select * from ".$GLOBALS['ecs']->table('cart')." where zengsong=0 and goods_price=0  and extension_code=''  and is_gift=0 and parent_id=0 and session_id = '".SESS_ID."' ";
    $glist=$GLOBALS['db']->getAll($sql);
    foreach($glist as $v){
        if(empty($v['carta_id'])){
            $product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$v['product_id']);
            $goods_price = get_final_price_new($v['goods_id'],-1,true,$v['goods_attr_id'],$v['areaid'],$product_sn);
        }else{
            $goods_price = get_price_area($v['goods_id'],0,'shop_price',0,0,$v['areaid']);
        }
        $sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_price=$goods_price where rec_id=".$v['rec_id'];
        $GLOBALS['db']->query($sql);
    }
    $sql="select * from ".$GLOBALS['ecs']->table('cart')." where zengsong=0 and extension_code='' and session_id = '".SESS_ID."' and is_gift = 0";
    $glist=$GLOBALS['db']->getAll($sql);

    foreach($glist as $value){
        $product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$value['product_id']);
        update_cart_volume_price2($value['goods_id'],$value['areaid'],$product_sn,$value['goods_attr_id']);
        $pr_g = 0;
        $gss=$value['goods_price'];
        $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where goods_id=".$value['goods_id']." and (carta_id is not null or carta_id !=0) and session_id = '".SESS_ID."' and goods_price > 0 and is_gift = 0";
        $pa_list = $GLOBALS['db']->getAll($sql);
        foreach($pa_list as $k=>$v){
            if($v['goods_price'] < $gss){
                $pr_g = $v['carta_id'];
                $gss = $v['goods_price'];
            }
        }
        if(!empty($pr_g)) {
            $sql = "select * from " . $GLOBALS['ecs']->table('cart_activity') . " where session_id = '".SESS_ID."' and recs_id=".$pr_g;
            $prow = $GLOBALS['db']->getRow($sql);
            $spec_price = 0;
            if (!empty($value['goods_attr_id'])) {
                $spec_price = spec_price($value['goods_attr_id'], $value['goods_id'], $value['areaid']);
            }
            $goo_price = get_price_area($value['goods_attr_id'], 0, 'shop_price', 0, 0, $value['areaid']);

            $price_fl = $goo_price * $prow['song'] + $spec_price;

            $sql = "update " . $GLOBALS['ecs']->table('cart') . " set goods_price=$price_fl where rec_id=" . $value['rec_id'];
            $GLOBALS['db']->query($sql);
        }
    }


    $sql = "select * from ".$GLOBALS['ecs']->table('cart')." where session_id = '".SESS_ID."' and extension_code='package_buy_all' and  activity_id>0";
    $actli = $GLOBALS['db']->getAll($sql);

    foreach($actli as $va){
        $sql="select * from ".$GLOBALS['ecs']->table('cart')." where zengsong=0 and extension_code='' and session_id = '".SESS_ID."' and is_gift = 0";
        $glists=$GLOBALS['db']->getAll($sql);
        $sql = "select * from ".$GLOBALS['ecs']->talbe('favourable_activity')." where act_id=".$va['activity_id'];
        $fact=$GLOBALS['db']->getRow($sql);
        $rang = explode(',',$fact['act_range_ext']);
        $l=unserialize($fact['buy']);
        var_dump($l);
        foreach($glists as $vg){
            if($fact['act_range'] == 3){
                if(in_array($vg['goods_id'],$rang)){
                    foreach($l as $vl){

                    }
                }
            }
        }
    }

}*/


function login_facebook()
{
	include_once('./phpsdk5/autoload.php');



$fb = new Facebook\Facebook([
  'app_id' => '1397762930546864', // Replace {app-id} with your app id
  'app_secret' => '01fddb435e8190d6ead0ba4939340ea2',
  'default_graph_version' => 'v2.9',
  ]);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['email']; // Optional permissions
$loginUrl = $helper->getLoginUrl('http://www.icmarts.com/user.php?act=fboath_login&type=facebook', $permissions);
return $loginUrl;
}
?>