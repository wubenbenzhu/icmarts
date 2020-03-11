<?php

/**
 * ECSHOP 文章内容
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: article.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

$_REQUEST['id'] = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$article_id     = $_REQUEST['id'];
if(isset($_REQUEST['cat_id']) && $_REQUEST['cat_id'] < 0)
{
    $article_id = $db->getOne("SELECT article_id FROM " . $ecs->table('article') . " WHERE cat_id = '".intval($_REQUEST['cat_id'])."' ");
}

if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'zan')
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	$article_id = $_REQUEST['id'];
	if(empty($_SESSION['article_ip_z']))
	{
		$sql = "update ".$ecs->table('article')." set total_uses = (select total_uses+1 from (select * from".$ecs->table('article').") as x where article_id = $article_id ) where article_id = $article_id";
		
		$db->query($sql);
	}else 
	{
		$res['qty'] = 0;
	}
	$sql = "select total_uses from ".$ecs->table('article')."where article_id = $article_id";
	$total = $db->getOne($sql);
	$res['total'] = $total;
	$_SESSION['article_ip_z'] = $_SERVER["REMOTE_ADDR"];
	die($json->encode($res));
}
else if($_REQUEST['act'] == 'huifu')
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	$article_id = $_REQUEST['id'];
	$neirong = $_REQUEST['nei'];
	$parentid = $_REQUEST['parent'];
	$three_id = $_REQUEST['three_id'];
	$user_id = $_SESSION['user_id'];
	if(empty($_SESSION['user_id'])||$_SESSION['user_id'] == 0)
	{
		$res['err_msg'] = 1;
	}else 
	{
		$add_time = gmtime();
		$sql = "INSERT INTO ".$ecs->table('comment_article')."(article_id, user_id, content, parent_id, three_id, ".
				"add_time) ".
				"VALUES ($article_id,$user_id, '$neirong', $parentid, $three_id, ".
				"'$add_time')";
		$db->query($sql);
	}
	
	
	die($json->encode($res));
}

// 首页文章内窜弹出 long 2018.3.20
elseif($_REQUEST['act'] == 'art_show'){
	include('includes/cls_json.php');

	$json   = new JSON;
	$article_id = $_REQUEST['id'];

	$sql = "select * from ".$GLOBALS['ecs']->table('article')." where article_id = ".$article_id;
	$info = $GLOBALS['db']->getRow($sql);

	preg_match_all("/src=\"?(.*?)\"/", $info['content'], $match);

	if(isset($match[1])){
		foreach($match[1] as $k=>$v) {
			if ($k < 3) {
				$info['img'][$k] = $match[1][$k];
			}else{
				break;
			}
		}
	}
	else{
		$info['img']         = array();
	}
	if(isset($match[1][0])) {
		$info['img_index'] = $match[1][0];
	}

	$GLOBALS['smarty']->assign('info',        $info);

	$val = $GLOBALS['smarty']->fetch('library/index_article.lbi');

	$res['result'] = $val;
	die($json->encode($res));
}

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

$cache_id = sprintf('%X', crc32($_REQUEST['id'] . '-' . $_CFG['lang']));

if (!$smarty->is_cached('article.dwt', $cache_id))
{
    /* 文章详情 */
    $article = get_article_info($article_id);

    if (empty($article))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    if (!empty($article['link']) && $article['link'] != 'http://' && $article['link'] != 'https://')
    {
        ecs_header("location:$article[link]\n");
        exit;
    }
    $sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";
    
    $ad_hd_ban = $db->getRow($sql);
    
    if($ad_hd_ban)
    {
    	$ad_hd_ban['ad_link'] = "affiche.php?ad_id=".$ad_hd_ban['ad_id']."&amp;uri=" .urlencode($ad_hd_ban["ad_link"]);
    	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
    }
    $smarty->assign('ad_hd_ban', $ad_hd_ban);
    $smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
    $cat_id = $db->getOne("SELECT cat_id FROM " . $ecs->table('article') . " WHERE article_id = '".$article_id."' ");
    $art_cat = article_categories_tree($cat_id);
    $smarty->assign('article_info',   $art_cat[$cat_id]);
    $art_pare=get_article_parent_cats($cat_id);
    $c_id = intval($art_pare[count($art_pare)-1]['cat_id']);
    $thmes_type = false;
    

   /* if($c_id == 16 || $c_id == 17){
    	$ar_cat = article_categories_tree($c_id);
        $smarty->assign('articles_list',   $ar_cat[$c_id]); //文章左分类
        $thmes_type = true;
    }else{}*/
        $smarty->assign('articles_list',  get_cat_articles(2));//同级文章列表
    
    $ar_cat = article_categories_tree(16);
    $smarty->assign('art_cat_left',   $ar_cat[16]); //文章左分类
    $smarty->assign('article_categories',   article_categories_tree($article_id)); //文章分类树
    
    $smarty->assign('categories',       get_categories_tree());  // 分类树
    $smarty->assign('helps',            get_shop_help()); // 网店帮助
   // $smarty->assign('top_goods',        get_top10());    // 销售排行
   // $smarty->assign('best_goods',       get_recommend_goods('best'));       // 推荐商品
   // $smarty->assign('new_goods',        get_recommend_goods('new'));        // 最新商品
   // $smarty->assign('hot_goods',        get_recommend_goods('hot'));        // 热点文章
   // $smarty->assign('promotion_goods',  get_promote_goods());    // 特价商品
  //$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
    //$smarty->assign('related_goods',    article_related_goods($_REQUEST['id']));  // 特价商品
    //$smarty->assign('id',               $article_id);
    //$smarty->assign('username',         $_SESSION['user_name']);
   // $smarty->assign('email',            $_SESSION['email']);
   // $smarty->assign('type',            '1');
   // $smarty->assign('promotion_info', get_promotion_info());
    
    /* 验证码相关设置 */
    if ((intval($_CFG['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand',            mt_rand());
    }
    if($_REQUEST['id'] == 5)//会员等级。VIP设置。
    {
    	$sql = "SELECT * FROM ".$ecs->table('user_rank')." WHERE show_price=1";
    	$use_rank = $db->getAll($sql);
    	foreach ($use_rank as $key=>$value)
    	{
    		$use_rank[$key]['discount'] = $use_rank[$key]['discount']/10;
    	}
    	$smarty->assign('article_use',      $use_rank);
    	
    }elseif($_REQUEST['id'] == 1)//该地区下的店铺信息列表
    {
    	$sql = "SELECT a.*,b.* FROM ".$ecs->table('area')." as a ,".$ecs->table('area_exchange_rate')." as b WHERE a.areaid!=0 and a.AreaExchangeRateId=".$_SESSION['area_rate_id']." AND a.AreaExchangeRateId=b.area_exchange_rate_id AND b.enabled=1 and a.state=1 ";
    	$area_list = $db->getAll($sql);
    	
    	$smarty->assign('article_area',      $area_list);
    }elseif($_REQUEST['id'] == 2)//所有店铺信息列表
    {
    	$sql = "SELECT a.*,b.* FROM ".$ecs->table('area')." as a ,".$ecs->table('area_exchange_rate')." as b WHERE a.areaid!=0  AND a.AreaExchangeRateId=b.area_exchange_rate_id AND b.enabled=1 and a.state=1 ";
    	$area_list = $db->getAll($sql);
    	$smarty->assign('article_area',      $area_list);
    }/*elseif ($_REQUEST['id'] == 16)//配送方式
	{
		$sql = 'SELECT a.shipping_desc_area as shipping_desc,s.shipping_id, s.shipping_code, s.shipping_name, ' .
				's.insure, s.support_cod, a.configure ' .
				'FROM ' . $GLOBALS['ecs']->table('shipping') . ' AS s, ' .
				$GLOBALS['ecs']->table('shipping_area') . ' AS a, ' .
				$GLOBALS['ecs']->table('area_region') . ' AS r ' .
				"WHERE   a.areaid=".$_SESSION['area_rate_id'].
				' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1  group by s.shipping_id  ORDER BY s.shipping_order';
		
		$paylist =  $GLOBALS['db']->getAll($sql);
		$article['content'] = '<div style="  font-size: 13px;">';
		foreach ($paylist as $key=>$value)
		{
			if($value['shipping_code'] == 'cac')
			{
				$sql = "SELECT areaname FROM ".$ecs->table('area')." WHERE AreaExchangeRateId=".$_SESSION['area_rate_id'];
				$area_list_name = $db->getAll($sql);
				$value['shipping_desc'] ='';
				foreach ($area_list_name as $k=>$v)
				{
					$value['shipping_desc'] = $value['shipping_desc'].$v['areaname'].' ';
				}
				
			}
			if($value['shipping_id'] == 5)
			{
				$article['content'] = $article['content'].'<p style="margin-top: 10px;">'.$value['shipping_name'].": ".$value['shipping_desc']."<a href='http://www.sf-express.com/hk/tc/deliver/delivery_tools/self_pickup_self_dropoff/' target='_blank' >順豐自寄自取服務詳情查看</a>"."</p>";
			}else 
			{
				$article['content'] = $article['content'].'<p style="margin-top: 10px;">'.$value['shipping_name'].": ".$value['shipping_desc']."</p>";
			}
		}
		$article['content'] = $article['content'].'<img src="themes/chaoliu/images/SSSnoc_TC.jpg"  style="width:100%">'. '</div>';
	}*//*elseif ($_REQUEST['id'] == 17)//支付方式 
	{
		$sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc, pay_config, is_cod' .
				' FROM ' . $GLOBALS['ecs']->table('payment') .
				' WHERE enabled = 1 AND is_online = 1 ';
		$pp = $db->getAll($sql);
		$article['content'] = '<div style="  font-size: 13px;">';
		foreach ($pp as $key=>$value)
		{
			
			$article['content'] = $article['content'].'<p style="margin-top: 10px;">'.$value['pay_name'].": ".$value['pay_desc']."</p>";
		}
		$article['content'] = $article['content']. '</div>';
	}*/
    $smarty->assign('article',      $article);
    $smarty->assign('keywords',     htmlspecialchars($article['keywords']));
    $smarty->assign('description', htmlspecialchars($article['description']));

    $catlist = array();
    foreach(get_article_parent_cats($article['cat_id']) as $k=>$v)
    {
        $catlist[] = $v['cat_id'];
    }

    assign_template('a', $catlist);

    $position = assign_ur_here($article['cat_id'], $article['title']);
    $smarty->assign('page_title',   $position['title']);    // 页面标题
    $smarty->assign('ur_here',      $position['ur_here']);  // 当前位置
    $smarty->assign('comment_type', 1);

    /* 相关商品 */
    $sql = "SELECT a.goods_id, g.goods_name " .
            "FROM " . $ecs->table('goods_article') . " AS a, " . $ecs->table('goods') . " AS g " .
            "WHERE a.goods_id = g.goods_id " .
            "AND a.article_id = '$_REQUEST[id]' ";
    $smarty->assign('goods_list', $db->getAll($sql));

    /* 上一篇下一篇文章 */
    $next_article = $db->getRow("SELECT article_id, title FROM " .$ecs->table('article'). " WHERE article_id > $article_id AND cat_id=$article[cat_id] AND is_open=1 LIMIT 1");
    if (!empty($next_article))
    {
        $next_article['url'] = build_uri('article', array('aid'=>$next_article['article_id']), $next_article['title']);
        $smarty->assign('next_article', $next_article);
    }

    $prev_aid = $db->getOne("SELECT max(article_id) FROM " . $ecs->table('article') . " WHERE article_id < $article_id AND cat_id=$article[cat_id] AND is_open=1");
    if (!empty($prev_aid))
    {
        $prev_article = $db->getRow("SELECT article_id, title FROM " .$ecs->table('article'). " WHERE article_id = $prev_aid");
        $prev_article['url'] = build_uri('article', array('aid'=>$prev_article['article_id']), $prev_article['title']);
        $smarty->assign('prev_article', $prev_article);
    }

    assign_dynamic('article');
}
if($_REQUEST['id'] == 57)//所有店铺信息列表
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
		$consignee_list[] = array('country' => $_CFG['shop_country'], 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
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
	if($_REQUEST['tj'] == 1)
	{
		
		//获取填写内容发送邮件
		$country = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id=".$_POST['country']);
		$province = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id=".$_POST['province']);
		$city = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id=".$_POST['city']);
		if($_POST['district'])
		{
			$district = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id=".$_POST['district']);
		}
		$qymc = $_POST['qymc'];
		$fzrm = $_POST['fzrm'];
		$email = $_POST['email'];
		$tel = $_POST['tel'];
		$content = $_POST['content'];
		$string = '国家：'.$country." ".$province." ".$city." ".$district." \r\n "."企業名稱:".$qymc." \r\n "."負責人姓名:".$fzrm." \r\n "."電郵:".$email." \r\n "."個人聯絡電話:".$tel." \r\n ".$content;
		send_mail('管理员','organicmacau@gmail.com','合作申请',$string,0,0);
		//获取填写内容发送邮件
		show_message('申請成功','申請成功', 'article.php?id=57');
	}
	$smarty->display('article_sq.dwt', $cache_id);
}
elseif($article['cat_id'] == 15)
{
	$sql = " SELECT a.*,g.goods_name,g.goods_img,p.price FROM ".$ecs->table('goods_article')." as a, ".$ecs->table('goods')." as g,".$ecs->table('price_area')." as p WHERE a.goods_id=p.goods_id and p.price_type='shop_price' and p.areaid=0 and g.goods_id=a.goods_id and  a.article_id=".$_REQUEST['id']." and p.areaid_rate=".$_SESSION['area_rate_id'];
	$goods = $db->getRow($sql);
	$goods['price'] = price_format($goods['price']);
	$smarty->assign('pictures',            get_goods_gallery($goods['goods_id']));   
	$smarty->assign('goods',   $goods);
	$sql = " SELECT goods_attr FROM ".$ecs->table('products')." WHERE product_sn='".$goods['product_sn']."' AND areaid=0 ";
	$attr = $db->getOne($sql);
	if(!empty($attr))
	{
		$attr = str_replace('|',',',$attr);
		$sql = " SELECT ga.*,a.attr_name FROM ".$ecs->table('goods_attr')." as ga, ".$ecs->table('attribute')." as a WHERE ga.goods_attr_id in(".$attr.") and ga.attr_id=a.attr_id ";
		$goods_attrlist = $db->getAll($sql);
		//var_dump($goods_attrlist);
		$smarty->assign('goods_list',   $goods_attrlist);
	}
	if(empty($_SESSION['article_ip']))
	{
		$sql = "update ".$ecs->table('article')." set total_browse = (select total_browse+1 from (select * from".$ecs->table('article').") as x where article_id = $article_id ) where article_id = $article_id";
		$db->query($sql);
	}
	$sql = " SELECT c.*,u.user_name FROM ".$ecs->table('comment_article')." as c,".$ecs->table('users')." as u WHERE c.user_id=u.user_id and   c.article_id=".$article_id;
	$comment_list = $db->getAll($sql);
	
	$comment_listall = array();
	foreach ($comment_list as $key=>$value)
	{
		$value['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
		if($value['parent_id'] == 0)//一级回复
		{
			$comment_listall[$value['id']] = $value;
		}elseif($value['parent_id']>0)//二级回复
		{
			
			$value['parent_name'] = $db->getOne("SELECT user_name FROM ".$ecs->table('users')." WHERE user_id=".$value['parent_id']);
			$comment_listall[$value['three_id']]['er_ji'][] = $value;
		}else //三级回复
		{
			
		}
	}
	
	$smarty->assign('comment_listall',$comment_listall);
	$smarty->assign('count_comment',count($comment_list));
	$_SESSION['article_ip'] = $_SERVER["REMOTE_ADDR"];
	
	$smarty->display('article_report.dwt', $cache_id);
}
else 
{
    if($thmes_type){
        $smarty->display('article.dwt', $cache_id);
    }else{
        $smarty->display('article.dwt', $cache_id);
    }
}
/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得指定的文章的详细信息
 *
 * @access  private
 * @param   integer     $article_id
 * @return  array
 */
function get_article_info($article_id)
{
    /* 获得文章的信息 */
    $sql = "SELECT a.*, IFNULL(AVG(r.comment_rank), 0) AS comment_rank ".
            "FROM " .$GLOBALS['ecs']->table('article'). " AS a ".
            "LEFT JOIN " .$GLOBALS['ecs']->table('comment'). " AS r ON r.id_value = a.article_id AND comment_type = 1 ".
            "WHERE a.is_open = 1 AND a.article_id = '$article_id' GROUP BY a.article_id";
    $row = $GLOBALS['db']->getRow($sql);

    if ($row !== false)
    {
        $row['comment_rank'] = ceil($row['comment_rank']);                              // 用户评论级别取整
        $row['add_time']     = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']); // 修正添加时间显示

        /* 作者信息如果为空，则用网站名称替换 */
        if (empty($row['author']) || $row['author'] == '_SHOPHELP')
        {
            $row['author'] = $GLOBALS['_CFG']['shop_name'];
        }
    }

    return $row;
}

/**
 * 获得文章关联的商品
 *
 * @access  public
 * @param   integer $id
 * @return  array
 */
function article_related_goods($id)
{
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            'FROM ' . $GLOBALS['ecs']->table('goods_article') . ' ga ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = ga.goods_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE ga.article_id = '$id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0";
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$row['goods_id']]['goods_id']      = $row['goods_id'];
        $arr[$row['goods_id']]['goods_name']    = $row['goods_name'];
        $arr[$row['goods_id']]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $arr[$row['goods_id']]['goods_thumb']   = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']     = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['market_price']  = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
        $arr[$row['goods_id']]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

        if ($row['promote_price'] > 0)
        {
            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
        }
        else
        {
            $arr[$row['goods_id']]['promote_price'] = 0;
        }
    }

    return $arr;
}

?>