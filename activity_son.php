<?php

/**
 * ECSHOP 活动列表
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: activity.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
include_once(ROOT_PATH . 'includes/lib_transaction.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

if (!isset($_REQUEST['step']))
{
    $_REQUEST['step'] = "list";
}
assign_template();
assign_dynamic('activity');
$position = assign_ur_here(0, $_LANG['shopping_activity']);
$smarty->assign('page_title', $position['title']);    // 页面标题
$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
$smarty->assign('ur_here', $position['ur_here']);  // 当前位置
if($_REQUEST['step'] == 'list') {

	/* 如果没有找到任何记录则跳回到首页 */
	ecs_header("Location: ./\n");
	exit;



//v
}elseif ($_REQUEST['step'] == 'category_price')
{
	include('includes/cls_json.php');

	$json   = new JSON;
	$goodsid = $_REQUEST['goodsid'];  //商品ID
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);

	
	
	die($json->encode($res));
}
elseif ($_REQUEST['step']=='show_goods')//显示某个活动的所有商品列表
{
	// 数据准备
	
	$time = gmtime();
	$activity_list = get_activity_show1();
	$act_l = array();
	foreach($activity_list as $k=>$v){
		if($v['act_id'] == $_REQUEST['id']){
			//$activity_list[$k]['is_active'] = 1;
			$act_l[0]=$v;
			$act_l[0]['is_active']=1;
		}else{
			//$activity_list[$k]['is_active'] = 0;
			$act_l[$k+1] = $v;
			$act_l[$k+1]['is_active']=0;
		}
	}
	ksort($act_l);
 	$smarty->assign('activity_show',$act_l);//買几送優惠
 
 	$sql = "select price_thumb from ".$GLOBALS['ecs']->table('price_area')." where  hd_id = ".$_REQUEST['id']." and price_type='favourable_price' and areaid=0 and areaid_rate=".$_SESSION['area_rate_id'];
 	$price_thumb = $GLOBALS['db']->getOne($sql);
 
 	$smarty->assign('price_thumb',$price_thumb);
    $promote_goods_list = get_promote_goods1(" limit 0,3");//促销活动
    $smarty->assign('promote_goods_list',$promote_goods_list);
    if($promote_goods_list)
    {
    	$smarty->assign('promote_goods_listone',$promote_goods_list[0]);
    }
    $sql = "SELECT g.goods_id,g.goods_name,g.shop_price,g.goods_thumb,g.volume_start_date,g.volume_end_date,p.id FROM ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  WHERE g.is_delete!=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and g.is_on_sale=1 and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=".$_SESSION['area_rate_id']." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' "." group by g.goods_id  order by p.id desc limit 0,3 ";
    $volume_goods_list = $GLOBALS['db']->getAll($sql);//件数优惠
    $smarty->assign('volume_goods_list',$volume_goods_list);
    if($volume_goods_list)
    {
    	$volume_goods_listone = $volume_goods_list[0];
    	$volume_goods_listone['volume_start_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_start_date'] );
    	$volume_goods_listone['volume_end_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_end_date'] );
    	
    	$smarty->assign('volume_goods_listone',$volume_goods_listone);
    }
    $package_goods_list = get_package_list(1,1); //组合活动  先取一个组合占位
    if($package_goods_list)
    {
    	$package_goods_lists = $package_goods_list[0];
    }
    $smarty->assign('package_goods_list',$package_goods_lists);
	/* 取得用户等级 */
	$user_rank_list = array();
	$user_rank_list[0] = $_LANG['not_user'];
	$sql = "SELECT rank_id, rank_name FROM " . $ecs->table('user_rank');
	$res = $db->query($sql);
	while ($row = $db->fetchRow($res)) {
		$user_rank_list[$row['rank_id']] = $row['rank_name'];
	}
	$act_idc = $_REQUEST['id'];
	$smarty->assign('act_idc',$act_idc);
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$size = isset($_REQUEST['size']) ? intval($_REQUEST['size']) : 50;
	// 开始工作
	
	$sql = "SELECT * FROM " . $ecs->table('favourable_activity'). " where is_online=0 and  act_id = ".$act_idc ." and ".gmtime()." >= start_time and ".gmtime()."<= end_time and areaid like '%".$_SESSION['area_rate_id']."%'"." limit 1 ";
	
	$res = $db->query($sql);
	
	$pan = $db->getOne($sql);
	
	if(empty($pan))
	{
		/* 如果没有找到任何记录则跳回到首页 */
		ecs_header("Location: ./\n");
		exit;
	}
	$list = array();
	
	while ($row = $db->fetchRow($res)) {
		
		//买几送几列表。
		$row['buy'] = unserialize($row['buy']);
		$row['gift'] = unserialize($row['gift']);
		foreach ($row['gift'] as $kk=>$vv)
		{
			
			$gallery_list = get_goods_gallery($vv['id']); // 商品相册
			
			
			$sql = " SELECT goods_thumb FROM ".$ecs->table('goods')." WHERE goods_id=".$vv['id'];
			$goods_thumb = $db->getOne($sql);
			$row['gift'][$kk]['goods_thumb']      = get_image_path($vv['id'], $goods_thumb, true);
			$row['gift'][$kk]['pictures']         = $gallery_list; // 商品相册
			
			$properties = get_goods_properties($vv['id']);  // 获得商品的规格和属性
			$row['gift'][$kk]['properties'] = $properties['spe'];
			$row['gift'][$kk]['url']              = build_uri('goods', array('gid'=>$vv['id']), $vv['name']);
		}
		$pan_num = 0 ;
		$i = 0;
		$zk = array();
		$total = array();
		$zjg_tow = 0;
		$zk_tow = 0;
		if($row['act_type'] == 2)
		{
			
			foreach ($row['buy'] as $keys=>$values)
			{
				$zjg_tow = $keys;
				$zk_tow = $values;
			}
			
		}
		foreach ($row['buy'] as $keys=>$values)
		{
		
			$row['buys'][$i]['buy'] = $keys;
			$row['buys'][$i]['song'] = $values;
			if($row['act_type'] == 4)
			{
				$zk[$keys] = $values;
			}
			if($row['act_type'] == 3)
			{
				$zk[$keys] = $values;
			}
			if($row['act_type'] == 5)
			{
				foreach ($values as $kt=>$vt)
				{
					if($kt == 'buy')
					{
						$total[$keys]['buy'] = $vt;
						
					}
					if($kt == $_SESSION['area_rate_id'])
					{
						
						$pj = $vt/$row['buy'][$keys]['buy'];
						
						$total[$keys]['pj_price'] = price_format($pj);
						$total[$keys]['price'] = price_format($vt);
					}
				}
				
			}
			
			
			$i++;
			if($keys<=$number_buy)
			{
				$pan_num = 1;
			}
		}
		if($row['act_type'] == 5)
		{
			
			$smarty->assign('total', $total);
		
		}
		
		$row['start_time'] = local_date('Y-m-d', $row['start_time']);
		$row['end_time'] = local_date('Y-m-d', $row['end_time']);
	
		//享受优惠会员等级
		$user_rank = explode(',', $row['user_rank']);
		$row['user_rank'] = array();
		foreach ($user_rank as $val) {
			if (isset($user_rank_list[$val])) {
				$row['user_rank'][] = $user_rank_list[$val];
			}
		}
		//优惠范围类型、内容
		if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext'])) {
			if ($row['act_range'] == FAR_CATEGORY) {
				$row['act_range'] = $_LANG['far_category'];
				$row['program'] = 'category.php?id=';
				/*$sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table('category') .
				" WHERE cat_id " . db_create_in($row['act_range_ext']);*/
				$sql = "select g.goods_id as id from ".$ecs->table('goods')." as g ,".$ecs->table('category')." as c where g.cat_id=c.cat_id and  c.cat_id " . db_create_in($row['act_range_ext_t']);
				$g_l = $db->getAll($sql);
				$l = array();
				foreach($g_l as $v){
					$l[]=$v['id'];
				}
				//$row['act_range_ext'] = implode(',',$l);
			} elseif ($row['act_range'] == FAR_BRAND) {
				$row['act_range'] = $_LANG['far_brand'];
				$row['program'] = 'brand.php?id=';
				/*$sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table('brand') .
				" WHERE brand_id " . db_create_in($row['act_range_ext']);*/
				$sql = "select g.goods_id as id from ".$ecs->table('goods')." as g ,".$ecs->table('brand')." as b where g.brand_id=b.brand_id and b.brand_id " . db_create_in($row['act_range_ext_t']);
				$g_l = $db->getAll($sql);
				$l = array();
				foreach($g_l as $v){
					$l[]=$v['id'];
				}
				//$row['act_range_ext'] = implode(',',$l);
				
			} else {
				$row['act_range'] = $_LANG['far_goods'];
				$row['program'] = 'goods.php?id=';
			
				$sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
				" WHERE goods_id " . db_create_in($row['act_range_ext']);
			}
		
			/* 获得商品列表 */
			if(empty($row['act_range_ext']))
			{
				$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND "."g.is_delete = 0 ";
			}else 
			{
				$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND "."g.is_delete = 0"." AND  g.goods_id in(".$row['act_range_ext'].") ";
			}
			
			
			$sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style,g.online_sale_z, g.market_price, g.is_new, g.is_best, g.is_hot, g.is_shipping, g.shop_price AS org_price, ' .
					"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
					'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
					'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
					'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
					"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
					"WHERE ".$where." ORDER BY field(g.goods_id,".$row['act_range_ext'].") ";
					//echo $sql;die();
			$sqlcount = 'SELECT count(g.goods_id) '.
			 'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
			'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
			"WHERE ".$where;
			$counts = $db->getOne($sqlcount);
			$pager  = get_pager('activity_son.php', array('step' => 'show_goods','id'=>$act_idc), $counts, $page,$size);
			$ress = $GLOBALS['db']->SelectLimit($sql, $pager['size'], $pager['start']);

			$arr = array();
			while ($rows = $GLOBALS['db']->fetchRow($ress))
			{
				$arr[$rows['goods_id']]['goods_id']         = $rows['goods_id'];
				$arr[$rows['goods_id']]['online_sale_z']         = $rows['online_sale_z'];
				$arr[$rows['goods_id']]['goods_name']       = $rows['goods_name'];
				$properties = get_goods_properties($rows['goods_id']);  // 获得商品的规格和属性
				
				$arr[$rows['goods_id']]['name']             = $rows['goods_name'];
				if($_SESSION['area_rate_id'] > 0)
				{
					$shop_price_rate = get_price_area($rows['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
					if($row['act_type'] == 4)
					{
						foreach ($zk as $zks=>$zv)
						{
							$arr[$rows['goods_id']]['shop_price_c_z'][$zv]['shop'] = price_format($shop_price_rate*($zv/10));
							$arr[$rows['goods_id']]['shop_price_c_z'][$zv]['zk'] = $zv;
							$arr[$rows['goods_id']]['shop_price_c_z'][$zv]['zk_s'] = $zks;
						}	
					}
					if($row['act_type'] == 3)
					{
						
					}
					if($row['act_type'] == 2)
					{
						
						$arr[$rows['goods_id']]['shop_price_c_zc'][$zv]['shop'] = price_format($shop_price_rate*($zk_tow/100));
						$arr[$rows['goods_id']]['shop_price_c_zc'][$zv]['zk'] = $zk_tow;
						$arr[$rows['goods_id']]['shop_price_c_zc'][$zv]['zk_s'] =$zjg_tow;
					}
					
					$arr[$rows['goods_id']]['shop_price_c'] = $shop_price_rate;
					$arr[$rows['goods_id']]['shop_price']   = price_format($shop_price_rate);
				}else
				{
					$arr[$rows['goods_id']]['shop_price']       = price_format($rows['shop_price']);
				}
				$now = gmtime();
				$arr[$rows['goods_id']]['is_shipping']      = $rows['is_shipping'];
				$arr[$rows['goods_id']]['type']             = $rows['goods_type'];
				$arr[$rows['goods_id']]['goods_thumb']      = get_image_path($rows['goods_id'], $rows['goods_thumb'], true);
				$arr[$rows['goods_id']]['goods_img']        = get_image_path($rows['goods_id'], $rows['goods_img']);
				$arr[$rows['goods_id']]['url']              = build_uri('goods', array('gid'=>$rows['goods_id']), $rows['goods_name']);
				$gallery_list = get_goods_gallery($rows['goods_id']); // 商品相册
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
				
				$arr[$rows['goods_id']]['pictures']         = $gallery_list_one; // 商品相册
				$arr[$rows['goods_id']]['pictures_t']         = $gallery_list_tow; // 商品相册
				$arr[$rows['goods_id']]['properties']       = $properties['pro']; // 商品属性
				$arr[$rows['goods_id']]['specification']    = $properties['spe']; // 商品规格
			}
			$act_range_ext = $arr;
	
			$row['act_range_ext'] = $act_range_ext;
		} else {
			$row['act_range'] = $_LANG['far_all'];
		}
		

		
		$list[] = $row;
	}
	
	
	$list =    array_reverse(multi_array_sort($list,'pan_num'));
	
	$list[0]['favourable_note'] = trim($list[0]['favourable_note']);
	$list[0]['favourable_note'] = explode("\r\n", $list[0]['favourable_note']);
	
	$smarty->assign('val', $list[0]);
	$smarty->assign('pager', $pager);
	$smarty->assign('page', $page);
	$smarty->assign('dqurl',   '&text=我想查詢活動：'.$list[0]['act_name']);
    $favourable_note = $list[0]['favourable_note'];

    $smarty->assign('favourable_note', $favourable_note);
	$smarty->assign('helps', get_shop_help());       // 网店帮助
	$smarty->assign('lang', $_LANG);
	
	$smarty->assign('feed_url', ($_CFG['rewrite'] == 1) ? "feed-typeactivity.xml" : 'feed.php?type=activity'); // RSS URL
	$smarty->display('activity_sonshow.dwt');
			
	
}

elseif ($_REQUEST['step'] == 'page_list')
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$jsonRes    = array();

	/* 取得用户等级 */
	$user_rank_list = array();
	$user_rank_list[0] = $_LANG['not_user'];
	$sql = "SELECT rank_id, rank_name FROM " . $ecs->table('user_rank');
	$res = $db->query($sql);
	while ($row = $db->fetchRow($res)) {
		$user_rank_list[$row['rank_id']] = $row['rank_name'];
	}
	$act_idc = $_REQUEST['act_idc'];
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) + 1 : 1;
	$size = isset($_REQUEST['size']) ? intval($_REQUEST['size']) : 50;
	// 开始工作
	
	$sql = "SELECT * FROM " . $ecs->table('favourable_activity'). " where is_online=0 and  act_id = ".$act_idc ." and ".gmtime()." >= start_time and ".gmtime()."<= end_time and areaid like '%".$_SESSION['area_rate_id']."%'"." limit 1 ";
	
	$res = $db->query($sql);
	
	$pan = $db->getOne($sql);
	
	if(empty($pan))
	{	
		$jsonRes['err_msg'] = 1;
		die($json->encode($res));
	}

	$list = array();
	
	while ($row = $db->fetchRow($res)) {
		
		//买几送几列表。
		$row['buy'] = unserialize($row['buy']);
		$row['gift'] = unserialize($row['gift']);
		foreach ($row['gift'] as $kk=>$vv)
		{
			
			$gallery_list = get_goods_gallery($vv['id']); // 商品相册
			
			
			$sql = " SELECT goods_thumb FROM ".$ecs->table('goods')." WHERE goods_id=".$vv['id'];
			$goods_thumb = $db->getOne($sql);
			$row['gift'][$kk]['goods_thumb']      = get_image_path($vv['id'], $goods_thumb, true);
			$row['gift'][$kk]['pictures']         = $gallery_list; // 商品相册
			
			$properties = get_goods_properties($vv['id']);  // 获得商品的规格和属性
			$row['gift'][$kk]['properties'] = $properties['spe'];
			$row['gift'][$kk]['url']              = build_uri('goods', array('gid'=>$vv['id']), $vv['name']);
		}
		$pan_num = 0 ;
		$i = 0;
		$zk = array();
		$total = array();
		$zjg_tow = 0;
		$zk_tow = 0;
		if($row['act_type'] == 2)
		{
			
			foreach ($row['buy'] as $keys=>$values)
			{
				$zjg_tow = $keys;
				$zk_tow = $values;
			}
			
		}
		foreach ($row['buy'] as $keys=>$values)
		{
		
			$row['buys'][$i]['buy'] = $keys;
			$row['buys'][$i]['song'] = $values;
			if($row['act_type'] == 4)
			{
				$zk[$keys] = $values;
			}
			if($row['act_type'] == 5)
			{
				foreach ($values as $kt=>$vt)
				{
					if($kt == 'buy')
					{
						$total[$keys]['buy'] = $vt;
						
					}
					if($kt == $_SESSION['area_rate_id'])
					{
						
						$pj = $vt/$row['buy'][$keys]['buy'];
						
						$total[$keys]['pj_price'] = price_format($pj);
						$total[$keys]['price'] = price_format($vt);
					}
				}
				
			}
			$i++;
			if($keys<=$number_buy)
			{
				$pan_num = 1;
			}
		}
		if($row['act_type'] == 5)
		{
			
			//$smarty->assign('total', $total);
			$row['total'] = $total;
		
		}
		
		$row['start_time'] = local_date('Y-m-d H:i', $row['start_time']);
		$row['end_time'] = local_date('Y-m-d H:i', $row['end_time']);
	
		//享受优惠会员等级
		$user_rank = explode(',', $row['user_rank']);
		$row['user_rank'] = array();
		foreach ($user_rank as $val) {
			if (isset($user_rank_list[$val])) {
				$row['user_rank'][] = $user_rank_list[$val];
			}
		}
		//优惠范围类型、内容
		if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext'])) {
			if ($row['act_range'] == FAR_CATEGORY) {
				$row['act_range'] = $_LANG['far_category'];
				$row['program'] = 'category.php?id=';
				/*$sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table('category') .
				" WHERE cat_id " . db_create_in($row['act_range_ext']);*/
				$sql = "select g.goods_id as id from ".$ecs->table('goods')." as g ,".$ecs->table('category')." as c where g.cat_id=c.cat_id and  c.cat_id " . db_create_in($row['act_range_ext_t']);
				$g_l = $db->getAll($sql);
				$l = array();
				foreach($g_l as $v){
					$l[]=$v['id'];
				}
				//$row['act_range_ext'] = implode(',',$l);
			} elseif ($row['act_range'] == FAR_BRAND) {
				$row['act_range'] = $_LANG['far_brand'];
				$row['program'] = 'brand.php?id=';
				/*$sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table('brand') .
				" WHERE brand_id " . db_create_in($row['act_range_ext']);*/
				$sql = "select g.goods_id as id from ".$ecs->table('goods')." as g ,".$ecs->table('brand')." as b where g.brand_id=b.brand_id and b.brand_id " . db_create_in($row['act_range_ext_t']);
				$g_l = $db->getAll($sql);
				$l = array();
				foreach($g_l as $v){
					$l[]=$v['id'];
				}
				//$row['act_range_ext'] = implode(',',$l);
				
			} else {
				$row['act_range'] = $_LANG['far_goods'];
				$row['program'] = 'goods.php?id=';
			
				$sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
				" WHERE goods_id " . db_create_in($row['act_range_ext']);
			}
		
			/* 获得商品列表 */
			if(empty($row['act_range_ext']))
			{
				$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND "."g.is_delete = 0 ";
			}else 
			{
				$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND "."g.is_delete = 0"." AND  g.goods_id in(".$row['act_range_ext'].") ";
			}
			
			
			$sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.is_shipping, g.shop_price AS org_price, ' .
					"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
					'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
					'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
					'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
					"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
					"WHERE ".$where." ORDER BY field(g.goods_id,".$row['act_range_ext'].") ";
			$sqlcount = 'SELECT count(g.goods_id) '.
			 'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
			'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
			"WHERE ".$where;
			$counts = $db->getOne($sqlcount);

			$max_page = ($counts> 0) ? ceil($counts / $size) : 1;
		    if ($page > $max_page)
		    {
		       $jsonRes['err_msg'] = 1;
			   die($json->encode($jsonRes));
		    }

			$pager  = get_pager('activity_son.php', array('step' => 'show_goods','id'=>$act_idc), $counts, $page,$size);
			$ress = $GLOBALS['db']->SelectLimit($sql, $pager['size'], $pager['start']);

			$arr = array();
			while ($rows = $GLOBALS['db']->fetchRow($ress))
			{
				$arr[$rows['goods_id']]['goods_id']         = $rows['goods_id'];
				$arr[$rows['goods_id']]['goods_name']       = $rows['goods_name'];
				$properties = get_goods_properties($rows['goods_id']);  // 获得商品的规格和属性
				$arr[$rows['goods_id']]['shop_price'] = get_price_area($rows['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
				$arr[$rows['goods_id']]['name']             = $rows['goods_name'];
				if($_SESSION['area_rate_id'] > 0)
				{
					$shop_price_rate = get_price_area($rows['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
					if($row['act_type'] == 4)
					{
						foreach ($zk as $zks=>$zv)
						{
							$arr[$rows['goods_id']]['shop_price_c_z'][$zv]['shop'] = price_format($shop_price_rate*($zv/10));
							$arr[$rows['goods_id']]['shop_price_c_z'][$zv]['zk'] = $zv;
							$arr[$rows['goods_id']]['shop_price_c_z'][$zv]['zk_s'] = $zks;
						}	
					}
					if($row['act_type'] == 2)
					{
						
						$arr[$rows['goods_id']]['shop_price_c_zc'][$zv]['shop'] = price_format($shop_price_rate*($zk_tow/100));
						$arr[$rows['goods_id']]['shop_price_c_zc'][$zv]['zk'] = $zk_tow;
						$arr[$rows['goods_id']]['shop_price_c_zc'][$zv]['zk_s'] =$zjg_tow;
					}
					
					$arr[$rows['goods_id']]['shop_price_c'] = $shop_price_rate;
					$arr[$rows['goods_id']]['shop_price']   = price_format($shop_price_rate);
				}else
				{
					$arr[$rows['goods_id']]['shop_price']       = price_format($rows['shop_price']);
				}
				$now = gmtime();
				$arr[$rows['goods_id']]['is_shipping']      = $rows['is_shipping'];
				$arr[$rows['goods_id']]['type']             = $rows['goods_type'];
				$arr[$rows['goods_id']]['goods_thumb']      = get_image_path($rows['goods_id'], $rows['goods_thumb'], true);
				$arr[$rows['goods_id']]['goods_img']        = get_image_path($rows['goods_id'], $rows['goods_img']);
				$arr[$rows['goods_id']]['url']              = build_uri('goods', array('gid'=>$rows['goods_id']), $rows['goods_name']);
				$gallery_list = get_goods_gallery($rows['goods_id']); // 商品相册
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
				$arr[$rows['goods_id']]['shop_price']       = price_format(get_price_area($rows['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']));
				$arr[$rows['goods_id']]['pictures']         = $gallery_list_one; // 商品相册
				$arr[$rows['goods_id']]['pictures_t']         = $gallery_list_tow; // 商品相册
				$arr[$rows['goods_id']]['properties']       = $properties['pro']; // 商品属性
				$arr[$rows['goods_id']]['specification']    = $properties['spe']; // 商品规格
			}
			$act_range_ext = $arr;
	
			$row['act_range_ext'] = $act_range_ext;
		} else {
			$row['act_range'] = $_LANG['far_all'];
		}
		
		$list[] = $row;
	}
	
	
	$list =    array_reverse(multi_array_sort($list,'pan_num'));

	if (!empty($list[0]))
    {
        $jsonRes['err_msg'] = 0;
        $jsonRes['result'] = appendPageAct($list[0]);
    } 
    else 
    { 
        $jsonRes['err_msg'] = 1;
    }

	die($json->encode($jsonRes));
}

elseif ($_REQUEST['step']=='ajax_show_goods'){

	$counts_ajax = $_REQUEST['counts_ajax'];
	$counts_ajaxp = $_REQUEST['counts_ajaxp'];
	$act_idc = $_REQUEST['act_id'];

	/* 取得用户等级 */
	$user_rank_list = array();
	$user_rank_list[0] = $_LANG['not_user'];
	$sql = "SELECT rank_id, rank_name FROM " . $ecs->table('user_rank');
	$res = $db->query($sql);
	while ($row = $db->fetchRow($res)) {
		$user_rank_list[$row['rank_id']] = $row['rank_name'];
	}

	// 开始工作

	$sql = "SELECT * FROM " . $ecs->table('favourable_activity'). " where act_id = ".$act_idc ." and ".gmtime()." >= start_time and ".gmtime()." <= end_time ";

	$row = $db->getRow($sql);
	$pan = $db->getAll($sql);
	if(empty($pan))
	{
		make_json_error($act_idc);
	}

	$list = array();
	//优惠范围类型、内容
	if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext'])) {
		if ($row['act_range'] == FAR_CATEGORY) {
			$row['act_range'] = $_LANG['far_category'];
			$row['program'] = 'category.php?id=';
			$sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table('category') .
				" WHERE cat_id " . db_create_in($row['act_range_ext']);
		} elseif ($row['act_range'] == FAR_BRAND) {
			$row['act_range'] = $_LANG['far_brand'];
			$row['program'] = 'brand.php?id=';
			$sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table('brand') .
				" WHERE brand_id " . db_create_in($row['act_range_ext']);
		} else {
			$row['act_range'] = $_LANG['far_goods'];
			$row['program'] = 'goods.php?id=';
			$sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
				" WHERE goods_id " . db_create_in($row['act_range_ext']);
		}

		$page_1 = $counts_ajax+1;
		$page_2 = $counts_ajax+16;
		/* 获得商品列表 */
		$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND "."g.is_delete = 0"." AND  g.goods_id in(".$row['act_range_ext'].") ";
		$sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.is_shipping, g.shop_price AS org_price, ' .
			"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
			'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
			'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
			'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
			"WHERE ".$where;
		$sql = $sql." limit $page_1, $page_2";
		$ress = $GLOBALS['db']->query($sql);

		$sqlcount = 'SELECT count(g.goods_id) '.
			'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
			'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
			"WHERE ".$where;
		$counts = $db->getOne($sqlcount);
		if($counts > $counts_ajax+16)
		{
			$counts_ajax =  $counts_ajax+16;
			$counts_ajaxp=1;
		}else
		{
			$counts_ajax = 0;
			$counts_ajaxp=0;
		}
		$arr = array();
		while ($rows = $GLOBALS['db']->fetchRow($ress))
		{

			$promote_price = bargain_price($rows['promote_price'], $rows['promote_start_date'], $rows['promote_end_date']);
			if($promote_price > 0)
			{
				//$_SESSION['area_rate_id']

				if($_SESSION['area_rate_id'] > 0)
				{
					$promote_price = get_price_area($rows['goods_id'],0,'promote_price',0,0,$_SESSION['area_rate_id']);//取地区促销价格
				}
			}

			$arr[$rows['goods_id']]['goods_id']         = $rows['goods_id'];
			if($display == 'grid')
			{
				$arr[$rows['goods_id']]['goods_name']       = $rows['goods_name'];
			}
			else
			{
				$arr[$rows['goods_id']]['goods_name']       = $rows['goods_name'];
			}
			$properties = get_goods_properties($rows['goods_id']);  // 获得商品的规格和属性
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

			$arr[$rows['goods_id']]['name']             = $rows['goods_name'];
			$arr[$rows['goods_id']]['goods_brief']      = $rows['goods_brief'];
			$arr[$rows['goods_id']]['goods_style_name'] = add_style($rows['goods_name'],$rows['goods_name_style']);

			if($_SESSION['area_rate_id'] > 0)
			{
				$sql = "SELECT price FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$rows['goods_id']." and areaid=0 and areaid_rate=".$_SESSION['area_rate_id']." order by price " ;

				$goods_list_num = $GLOBALS['db']->getOne($sql);

				$shop_price_rate = get_price_area($rows['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
				if ($shop_price_rate>$goods_list_num&&$goods_list_num!=0) {
					$shop_price_rate = $goods_list_num;
				}
				$arr[$rows['goods_id']]['shop_price_c'] = $shop_price_rate;
				$arr[$rows['goods_id']]['shop_price']   = price_format($shop_price_rate);
				$market_price_rate = get_price_area($rows['goods_id'],0,'market_price',0,0,$_SESSION['area_rate_id']);
				$arr[$rows['goods_id']]['market_price'] = price_format($market_price_rate);
				if($promote_price > 0){
					$promote_price_rate = get_price_area($rows['goods_id'],0,'promote_price',0,0,$_SESSION['area_rate_id']);
					$arr[$rows['goods_id']]['promote_price']    = price_format($promote_price_rate);
					$arr[$rows['goods_id']]['promote_price_c'] = $promote_price_rate;
				}else{
					$arr[$rows['goods_id']]['promote_price']    = '';
				}
			}else
			{
				$arr[$rows['goods_id']]['market_price']     = price_format($rows['market_price']);
				$arr[$rows['goods_id']]['shop_price']       = price_format($rows['shop_price']);
				$arr[$rows['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
			}
			$now = gmtime();
			$sql_v="select sum(price) from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$rows['goods_id'].
				" and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price')";

			$volume_type = $GLOBALS['db']->getOne($sql_v);
			if($volume_type > 0){
				$arr[$rows['goods_id']]['volume_type'] = 1;
			}


			$sql_g="select sum(p.price) from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods_activity')." as f where p.goods_id = ".$rows['goods_id'].
				" and p.price_type = 'group_price' and p.hd_id = f.act_id and f.act_type = 1 and f.start_time >= ".$now." and f.end_time <= ".$now." and p.areaid_rate =".$_SESSION['area_rate_id'].
				" and p.areaid = 0 and f.areaid like '%".$_SESSION['area_rate_id']."%'";

			$group_type = $GLOBALS['db']->getOne($sql_g);

			if($group_type > 0){
				$arr[$rows['goods_id']]['group_type'] = 1;
			}
			if($volume_type >0 || $group_type >0 || $promote_price > 0 || $rows['is_shipping']>0){
				$arr[$rows['goods_id']]['actp_type'] = 1;
			}
			$arr[$rows['goods_id']]['is_shipping']      = $rows['is_shipping'];
			$arr[$rows['goods_id']]['type']             = $rows['goods_type'];
			$arr[$rows['goods_id']]['goods_thumb']      = get_image_path($rows['goods_id'], $rows['goods_thumb'], true);
			$arr[$rows['goods_id']]['goods_img']        = get_image_path($rows['goods_id'], $rows['goods_img']);
			$arr[$rows['goods_id']]['url']              = build_uri('goods', array('gid'=>$rows['goods_id']), $rows['goods_name']);
			$gallery_list = get_goods_gallery($rows['goods_id']); // 商品相册
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
			$arr[$rows['goods_id']]['pictures']         = $gallery_list_one; // 商品相册
			$arr[$rows['goods_id']]['pictures_t']         = $gallery_list_tow; // 商品相册
			$arr[$rows['goods_id']]['properties']       = $properties['pro']; // 商品属性
			$arr[$rows['goods_id']]['specification']    = $properties['spe']; // 商品规格

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

			$sql = "select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where areaid in ".$area_value." and goods_id=".$rows['goods_id'];

			$arr[$rows['goods_id']]['number']    = $GLOBALS['db']->getOne($sql);
		}
		$list = $arr;

	} else {
		$counts_ajaxp = 1;
		$row['act_range'] = $_LANG['far_all'];
	}
	$html = '';
	foreach($list as $value){
		if($value['goods_id'] > 0){
			$html .= '<div class="listitems"><dl><dt><a href="'.$value['url'].'"><img src="'.$value['goods_img'].'" width="240" height="240"  alt="{$goods.name}"/></a></dt><dd></dd>
          <dd class="items-info">'.$value['name'].'</dd><dd class="poab">';

			if($value['is_shipping']){
				$html .='<span class="addspan" style="background:#00AFC5;">免運商品</span>';
			}
			$html .='<span class="addspan"> 促銷 </span>';
			if($value['volume_type'] == 1){
				$html .='<span class="addspan"> 數量優惠活動 </span>';
			}
			if($value['group_type'] == 1){
				$html .='<span class="addspan"> 團購</span>';
			}
			$html .='</dd><dd class="poab mt50">售價：<input type="hidden" id="beigoodsprice_'.$value['goods_id'].'" value="';
			if($value['promote_price'] > 0){
				$html .= $value['promote_price_c'];
			}else{
				$html .= $value['shop_price_c'];
			}
			$html .='"><span id="onebeigoodsprice_'.$value['goods_id'].'">';
			if($value['promote_price'] > 0){
				$html .=$value['promote_price'];
			}else{
				$html .=$value['shop_price'];
			}
			$html .='</span></dd></dl><div class="more"><dl>
          <dt><a href="'.$value['goods_id'].'"><img src="'.$value['goods_img'].'" width="240" height="240"  alt="'.$value['name'].'"/></a></dt><dd>
          </dd><dd class="items-info">'.$value['name'].'</dd> <dd class="poab"><dd class="poab">';

			if($value['is_shipping']){
			$html .='<span class="addspan" style="background:#00AFC5;">免運商品</span>';
			}
			$html .='<span class="addspan"> 促銷 </span>';
			if($value['volume_type'] == 1){
				$html .='<span class="addspan"> 數量優惠活動 </span>';
			}
			if($value['group_type'] == 1){
				$html .='<span class="addspan"> 團購</span>';
			}
			$html .='</dd><dd class="poab mt50">售價：<input type="hidden" id="beigoodsprice_'.$value['goods_id'].'" value="';
			if($value['promote_price'] > 0){
				$html .= $value['promote_price_c'];
			}else{
				$html .= $value['shop_price_c'];
			}
			$html .='"><span id="onebeigoodsprice_'.$value['goods_id'].'">';
			if($value['promote_price'] > 0){
				$html .=$value['promote_price'];
			}else{
				$html .=$value['shop_price'];
			}
			$html .= '</span></dd></dl><div class="showpic"><ul>';

			foreach($value['pictures'] as $vg){
				$html .='<li><img src="'.$vg['thumb_url'].'"  alt=""/></li>';
			}
			$html .='</ul><ul>';

			foreach($value['pictures_t'] as $vg){
				$html .='<li><img src="'.$vg['thumb_url'].'"  alt=""/></li>';
			}

			$html .='</ul></div><div class="showinfo"><div id="div_'.$value['goods_id'].'_'.$act_idc.'">';

			foreach($value['specification'] as $ks=>$vs){
				$html .='<div class="infotop"><span class="chetitle">'.$vs['name'].'：</span><ul >';
				foreach($vs['values'] as $vv){
					$html .= '<li onclick="changeP(spec_'.$ks.'_'.$value['goods_id'].','.$vv['id'].','.$value['goods_id'].','.$ks.','.$act_idc.')" > <span  name="sp_url_'.$ks.'_'.$value['goods_id'].'" id="url_'.$vv['id'].'_'.$value['goods_id'].'_'.$act_idc.'" >'.$vv['label'].'</span>
                <input style="display:none" id="spec_value_'.$vv['id'].'_'.$value['goods_id'].'_'.$act_idc.'" type="radio" name="spec_'.$ks.'_'.$value['goods_id'].'" value="'.$vv['id'].'" /></li>';
				}
				$html .='<input type="hidden" name="attr_'.$value['goods_id'].'_'.$act_idc.'" id="attr_'.$ks.'_'.$value['goods_id'].'_'.$act_idc.'"  /></ul></div> ';
			}
			$html .='</div>';

			foreach($value['specification'] as $ks=>$vs){
				$html .='<input type="hidden" name="attr_a_'.$value['goods_id'].'_'.$act_idc.'" id="attr_a_'.$ks.'_'.$value['goods_id'].'_'.$act_idc.'" />';
			}
			$html .='<div class="infotop"><span class="chetitle">數量：</span><span class="reduce" style="float:left;">-</span><input name="number_'.$value['goods_id'].'_'.$act_idc.'" type="text" id="number_'.$value['goods_id'].'_'.$act_idc.'" value="1" size="1" style="float:left; height:22px; border:0px; text-align:center; width:25px;"/><span class="add" style="float:left;">+</span>
              <input type="button" value="加入購物車" class="addche" ';
			if($value['specification']){
				$html .='onclick="addtocart('.$value['goods_id'].'},0,'.$act_idc.')"';
			}else{
				$html .= 'onclick="addtocart('.$value['goods_id'].'},1,'.$act_idc.')"';
			}
			$html .= '/></div><div class="share"><span> <img src="images/20x20.png" width="20" height="20"  alt=""/></span><span><img src="images/weibo.jpg" width="20" height="20"  alt=""/></span></div></div></div></div>';

		}
	}


	$results['html'] = $html;
	$results['counts_ajax'] = $counts_ajax;
	$results['counts_ajaxp'] = $counts_ajaxp;
	exit(json_encode($results));

}
elseif ($_REQUEST['step'] == 'del_to_activity')//删除指定活动
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$result = array('error' => 0, 'message' => '11', 'content' => '');
	$activity_id = intval($_POST['activity_id']);

	/*取信息*/
	$sql = "SELECT zengsong FROM " .$GLOBALS['ecs']->table('cart'). " WHERE zengsong >0 and carta_id = '$activity_id'";
	$zengsong = $GLOBALS['db']->getOne($sql);

	if($zengsong == 1 || $zengsong == 3)//无设置赠送商品
	{
		$sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$activity_id."%'";

		$cart_ze = $GLOBALS['db']->getAll($sql);

		foreach($cart_ze as $v){
			$catid = explode(',',$v['carta_id']);
			if(count($catid) >1){
				$rc = array();
				foreach($catid as $vc){
					if($vc != $activity_id){
						$rc[]=$vc;
					}
				}
				$cat = implode(',',$rc);

				$sql = "select buy_goods_id from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$activity_id;
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

		//删除增品

		$sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = ".$zengsong." and carta_id = ".$activity_id;

		$cart_song = $GLOBALS['db']->getAll($sql);
		foreach($cart_song as $value){
			$sql = "select count(*) from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['prec_id'];
			if($GLOBALS['db']->getOne($sql)){
				$sql = "update ".$GLOBALS['ecs']->table('cart')." set goods_number = goods_number + ".$value['goods_number']." where rec_id = ".$value['prec_id'];
				$GLOBALS['db']->query($sql);

				$sql = "delete from ".$GLOBALS['ecs']->table('cart')." where rec_id = ".$value['rec_id'];
				$GLOBALS['db']->query($sql);
			}else{
				$sql = "select product_sn from ".$GLOBALS['ecs']->table('products')." where product_id = ".$value['product_id'];
				$product_sn = $GLOBALS['db']->getOne($sql);
				$goods_price = get_final_price_new($value['goods_id'],$value['goods_number'],true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);

				$sql = "update ".$GLOBALS['ecs']->table('cart')." set rec_id = ".$value['prec_id'].", goods_price = ".$goods_price.", zengsong = 0, activity_id = 0, song_buy = 0, prec_id=0, song_num=0, carta_id=0 ".
					" where rec_id = ".$value['rec_id'];

				$GLOBALS['db']->query($sql);
			}
		}


		$sql = "delete from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id = ".$activity_id;
		$GLOBALS['db']->query($sql);

	}
	elseif ($zengsong==2)//设置赠送商品
	{
		$sql = "select * from ".$GLOBALS['ecs']->table('cart')." where zengsong = 0 and carta_id like  '%".$activity_id."%'";

		$cart_ze = $GLOBALS['db']->getAll($sql);

		foreach($cart_ze as $v){
			$catid = explode(',',$v['carta_id']);
			if(count($catid) >1){
				$rc = array();
				foreach($catid as $vc){
					if($vc != $activity_id){
						$rc[]=$vc;
					}
				}
				$cat = implode(',',$rc);

				$sql = "select buy from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id=".$activity_id;
				$by_goods = $GLOBALS['db']->getOne($sql);

				$sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=song_num - $by_goods,carta_id='".$cat."'".
					" where rec_id = ".$v['rec_id'];
			}else{
				$sql = "update ".$GLOBALS['ecs']->table('cart')." set song_num=0,carta_id=0".
					" where rec_id = ".$v['rec_id'];
			}

			$GLOBALS['db']->query($sql);
		}

		$sql = "delete from ".$GLOBALS['ecs']->table('cart')." where zengsong = 2 and  carta_id = ".$activity_id;
		$GLOBALS['db']->query($sql);

		$sql = "delete from ".$GLOBALS['ecs']->table('cart_activity')." where recs_id = ".$activity_id;
		$GLOBALS['db']->query($sql);

	}
	else{
		$result['error'] = 1;
	}

	die($json->encode($result));
}
elseif($_REQUEST['step'] == 'add_to_activity'){ // 处理选择哪个数量的活动

	include('includes/cls_json.php');
	$json   = new JSON;
	$activity_id = intval($_POST['activity_id']);
	$number = intval($_POST['number']);
	$type = intval($_POST['type']);
	$song = $_POST['song'];
	$result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
	$zongsongsz = intval($_POST['zengsong']);
	if($type==3)//买几送几
	{
		if($zongsongsz == 1)//设置有赠品
		{
			$sql = " SELECT act_range_ext FROM ".$ecs->table('favourable_activity')." WHERE act_id = $activity_id";
			$act_range_ext = $GLOBALS['db']->getRow($sql);
			$sql = " SELECT rec_id,goods_id,goods_number-song_num as goods_number,goods_price,goods_attr_id,product_id FROM ".$GLOBALS['ecs']->table('cart').
			" WHERE session_id = '" .SESS_ID. "' AND goods_id in(".$act_range_ext['act_range_ext'].") ".
			" AND parent_id = 0 " .
			" AND extension_code not like '%package_buy%' " .
			" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 AND goods_number>song_num ";
				
			$cart_list = $GLOBALS['db']->getAll($sql); //查询这个活动产品有哪些加入购物车了。
			if(count($cart_list)==1)//只有一种货品
			{
				if($cart_list[0]['goods_number']<=0)
				{
					$result['message'] = '條件不符合活動';
				}else
				{
					$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "' AND goods_id=".$cart_list[0]['goods_id'].
					" AND parent_id = 0 " .
					" AND extension_code not like '%package_buy%' " .
					" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 AND goods_number>song_num ";
					$goods_song = $GLOBALS['db']->getRow($sql);
						
					$song_goods = array();
					$song_goods[0]['rec_id'] = $goods_song['rec_id'];
					$song_goods[0]['goods_id'] = $cart_list[0]['goods_id'];
					$song_goods[0]['ysong'] = $number;
					$song_string = serialize($song_goods);
					
					//插入赠送商品，减少购物车正常商品的数量
					$zengsong = array(
							'session_id'    => SESS_ID,
							'act_id'       => $activity_id,
							'buy_goods_id'      => $song_string,
							'buy'      => $number,
							'song'    => $song,
							'ysong'    => 0,
							'is_finish'  => 0
								
					);
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
					$id = $GLOBALS['db']->insert_id();
					if(empty($goods_song['carta_id']))
					{
					
					}else
					{
						$id = $goods_song['carta_id'].",".$id;
					}
					$number = $goods_song['song_num']+$number;
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  song_num = '$number' " .", carta_id = '$id' " .
					" WHERE rec_id=".$goods_song['rec_id'];
					$GLOBALS['db']->query($sql);
					$result['message'] = '請選擇贈送商品';
				}
			}else//多种商品加入购物车 
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
				$zengsong_s = $song;
				
				//插入赠送商品，减少购物车正常商品的数量
				$zengsongss = array(
						'session_id'    => SESS_ID,
						'act_id'       => $activity_id,
						'buy_goods_id'      => '',
						'buy'      => $number,
						'song'    => $song,
						'ysong'    => 0,
						'is_finish'  => 0
				
				);
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsongss, 'INSERT');
				$id = $GLOBALS['db']->insert_id();
				$c_id = $id;
				$i= 1;
				$song_goods = array();
				foreach ($cart_list as $sk=>$sv)//循环划出正常商品赠送数量
				{
					
					$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "' AND goods_id=".$sv['goods_id'].
					" AND parent_id = 0 " .
					" AND extension_code not like '%package_buy%' " .
					" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 AND goods_number>song_num ";
					
					$goods_song = $GLOBALS['db']->getRow($sql);
					
					if(($goods_song['goods_number']-$goods_song['song_num'])>0)
					{
						$yisong = $goods_song['goods_number']-$goods_song['song_num'];
						$number1 = $number ;
						$number = $number - $yisong;
						
						if($number>0)
						{
							
						}else 
						{
							$yisong = $number1;
							$i = 0;
						}
						if(empty($goods_song['carta_id']))
						{
							
						}else
						{
							$id = $goods_song['carta_id'].",".$id;
						}
						$song_goods[$sk]['rec_id'] = $goods_song['rec_id'];
						$song_goods[$sk]['goods_id'] = $sv['goods_id'];
						$song_goods[$sk]['ysong'] = $yisong;
						$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  " ." song_num = '$yisong' " .", carta_id = '$id' " .
						" WHERE rec_id=".$goods_song['rec_id'];
						$GLOBALS['db']->query($sql);
					}
					if($i==0)
					{
						break;
					}
					
					
				}
				$song_string = serialize($song_goods);
				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart_activity') . " SET buy_goods_id ='".$song_string .
				"' WHERE recs_id = ".$c_id ;
					
				$GLOBALS['db']->query($sql);
				$result['message'] = '請選擇贈送商品';
				
				
			}
		}else //没设置有赠品处理
		{

			$sql = " SELECT act_range_ext FROM ".$ecs->table('favourable_activity')." WHERE act_id = $activity_id";
			$act_range_ext = $GLOBALS['db']->getRow($sql);
			$sql = " SELECT goods_id,goods_number-song_num as goods_number,goods_price,goods_attr_id,product_id FROM ".$GLOBALS['ecs']->table('cart').
			" WHERE session_id = '" .SESS_ID. "' AND goods_id in(".$act_range_ext['act_range_ext'].") ".
			" AND parent_id = 0 " .
			" AND extension_code not like '%package_buy%' " .
			" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 AND goods_number>song_num ";
			
			$cart_list = $GLOBALS['db']->getAll($sql); //查询这个活动产品有哪些加入购物车了。
			if(count($cart_list)==1)//只有一种货品
			{
				if($cart_list[0]['goods_number']<=0)
				{
					$result['message'] = '條件不符合活動';
				}else
				{
					$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "' AND goods_id=".$cart_list[0]['goods_id'].
					" AND parent_id = 0 " .
					" AND extension_code not like '%package_buy%' " .
					" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 AND goods_number>song_num ";
					$goods_song = $GLOBALS['db']->getRow($sql);
					 
					$song_goods = array();
					$song_goods[0]['rec_id'] = $goods_song['rec_id'];
					$song_goods[0]['goods_id'] = $cart_list[0]['goods_id'];
					$song_goods[0]['ysong'] = $number-$song;
					$song_string = serialize($song_goods);
					//插入赠送商品，减少购物车正常商品的数量
					$zengsong = array(
							'session_id'    => SESS_ID,
							'act_id'       => $activity_id,
							'buy_goods_id'      => $song_string,
							'buy'      => $number,
							'song'    => $song,
							'ysong'    => $song,
							'is_finish'  => 1
					
					);
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
					$id = $GLOBALS['db']->insert_id();
					
					
					//插入赠送商品，减少购物车正常商品的数量
					$zengsong = array(
							'user_id'       => $_SESSION['user_id'],
							'session_id'    => SESS_ID,
							'goods_id'      => $cart_list[0]['goods_id'],
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
							'activity_id'=>$activity_id,// 增加活动ID，未测试
							'song_buy'=>$number,
							'prec_id'=>$goods_song['rec_id'],
							'carta_id'=>$id
								
					);
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');
					
					$num_update = $goods_song['goods_number'] - $song;
					$yisong = $goods_song['song_num']+$number-$song; //设置已赠送过的商品
					if(empty($goods_song['carta_id']))
					{
						
					}else 
					{
						$id = $goods_song['carta_id'].",".$id;
					}
					if($num_update == 0)
					{
						$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$goods_song['rec_id'];
						$GLOBALS['db']->query($sql);
					}else
					{
						$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num_update'" .", song_num = '$yisong' " .", carta_id = '$id' " .
						" WHERE rec_id=".$goods_song['rec_id'];
						$GLOBALS['db']->query($sql);
					}
					$result['message'] = '優惠商品已成功加入購物車';
				}
					
			}else
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
				$zengsong_s = $song;
				
				//插入赠送商品，减少购物车正常商品的数量
				$zengsongss = array(
						'session_id'    => SESS_ID,
						'act_id'       => $activity_id,
						'buy_goods_id'      => 0,
						'buy'      => $number,
						'song'    => $song,
						'ysong'    => $song,
						'is_finish'  => 1
				
				);
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsongss, 'INSERT');
				$id = $GLOBALS['db']->insert_id();
				$c_id = $id;
				$ysong_list = array();
				
				foreach ($cart_list as $ck=>$cv)
				{
					if($cv['goods_number']<=0)
					{
						//过滤已赠送过的购买商品
					}else
					{
						if($zengsong_s == 0)//送完跳出循环
						{
							break;
						}
						$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "' AND goods_id=".$cv['goods_id'].
						" AND parent_id = 0 " .
						" AND extension_code not like '%package_buy%' " .
						" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 AND goods_number>song_num ";
						$goods_song = $GLOBALS['db']->getRow($sql);
						$songs= 0;
						if($zengsong_s>=$goods_song['goods_number'])//一件货品数不够赠送数大，全设置为赠送商品
						{
							$songs = $goods_song['goods_number'];
						}else //如果余下的赠送数小于这件货品，则就送余下的数即可。
						{
							$songs = $zengsong_s;
						}
						$zengsong_s = $zengsong_s - $songs; //减掉已赠送的数量。
						//插入赠送商品，减少购物车正常商品的数量
						$zengsong = array(
								'user_id'       => $_SESSION['user_id'],
								'session_id'    => SESS_ID,
								'goods_id'      => $cv['goods_id'],
								'goods_sn'      => $goods_song['goods_sn'],
								'product_id'    => $goods_song['product_id'],
								'goods_name'    => $goods_song['goods_name'],
								'market_price'  => $goods_song['market_price'],
								'goods_number'   =>$songs,
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
								'activity_id'=>$activity_id,// 增加活动ID，未测试
								'song_buy'=>$number,
								'prec_id'=>$goods_song['rec_id'],
								'carta_id'=>$c_id
			
						);
						$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');
			
						
						
						$num_update = $goods_song['goods_number'] - $songs;
						if(empty($goods_song['carta_id']))
						{
						
						}else
						{
							$c_id = $goods_song['carta_id'].",".$c_id;
						}
						if($num_update == 0)
						{
							$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$goods_song['rec_id'];
							$GLOBALS['db']->query($sql);
						}else
						{
							$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num_update'" .", carta_id = '$c_id' " .
							" WHERE rec_id=".$goods_song['rec_id'];
							$GLOBALS['db']->query($sql);
							$ysong_list[$ck] = $goods_song['rec_id'];
						}
						$result['message'] = '優惠商品已成功加入購物車';
					}
				}
					
				$sql = " SELECT rec_id, goods_id,goods_number-song_num as goods_number,goods_price,goods_attr_id,product_id,carta_id,song_num FROM ".$GLOBALS['ecs']->table('cart').
				" WHERE session_id = '" .SESS_ID. "' AND goods_id in(".$act_range_ext['act_range_ext'].") ".
				" AND parent_id = 0 " .
				" AND extension_code not like '%package_buy%' " .
				" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 AND goods_number>song_num order by rec_id";
					
				$cart_list_yi = $GLOBALS['db']->getAll($sql); //查询这个活动产品有哪些加入购物车了。
				$yisong = $number -$song; //还有多少个已赠送商品需要设置。
				
				
				$song_goods = array();
				
				foreach ($cart_list_yi as $ky=>$valuey)
				{
					if($valuey['goods_number']<=0)
					{
						//过滤已赠送过的购买商品
					}else
					{
						if($yisong == 0)//送完跳出循环
						{
							break;
						}
						if($valuey['goods_number']<$yisong)
						{
							$yisong = $yisong- $valuey['goods_number'];
						}else
						{
							$valuey['goods_number'] = $yisong;
							$yisong = 0;
						}
						
						if(empty($valuey['carta_id']))
						{
						
						}else
						{
							$id = $valuey['carta_id'].",".$id;
						}
						
						
						$where = " , carta_id = '$id' ";
						foreach ($ysong_list as $yk=>$yv)
						{
							if($valuey['rec_id'] == $yv)
							{
								$where ="";
								break;
							}
						}
						
						$song_goods[$ky]['rec_id'] = $valuey['rec_id'];
						$song_goods[$ky]['goods_id'] = $valuey['goods_id'];
						$song_goods[$ky]['ysong'] = $valuey['goods_number'];
						
						
						$valuey['goods_number'] = $valuey['goods_number']+ $valuey['song_num'];
						$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET song_num =".$valuey['goods_number'] . $where.
						" WHERE rec_id = ".$valuey['rec_id'] ;
							
						$GLOBALS['db']->query($sql);
					}
				}
				$song_string = serialize($song_goods);
				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart_activity') . " SET buy_goods_id ='".$song_string .
				"' WHERE recs_id = ".$c_id ;
					
				$GLOBALS['db']->query($sql);
					
			}
			
		}
		
		
	}elseif ($type==4)//折扣优惠
	{
		
		$sql = " SELECT act_range_ext FROM ".$ecs->table('favourable_activity')." WHERE act_id = $activity_id";
		$act_range_ext = $GLOBALS['db']->getRow($sql);
		$sql = " SELECT goods_id,goods_number-song_num as goods_number,goods_price,goods_attr_id,product_id FROM ".$GLOBALS['ecs']->table('cart').
		" WHERE session_id = '" .SESS_ID. "' AND goods_id in(".$act_range_ext['act_range_ext'].") ".
		" AND parent_id = 0 " .
		" AND extension_code not like '%package_buy%' " .
		" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0  AND goods_number>song_num ";
		
		$cart_list = $GLOBALS['db']->getAll($sql); //查询这个活动产品有哪些加入购物车了。
		if(count($cart_list)==1)//只有一种货品
		{
			if($cart_list[0]['goods_number']<=0)
			{
				$result['message'] = '條件不符合活動';
			}else
			{
				$sql = " SELECT * FROM ".$GLOBALS['ecs']->table('cart')." WHERE session_id = '" .SESS_ID. "' AND goods_id=".$cart_list[0]['goods_id'].
				" AND parent_id = 0 " .
				" AND extension_code not like '%package_buy%' " .
				" AND rec_type = 'CART_GENERAL_GOODS' and zengsong=0 ";
				$goods_song = $GLOBALS['db']->getRow($sql);
					
				$sql = " SELECT price FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id =".$cart_list[0]['goods_id']." AND price_type='shop_price' and  areaid=".$_SESSION['area_rate_id']." limit 1 ";
				$goods_shop_price = $GLOBALS['db']->getRow($sql);
				$goods_shop_price['price'] = ceil($goods_shop_price['price'] * $song/10) ;
				
				if(!empty($cart_list[0]['goods_attr_id']))//计算价格最便宜的商品用来打折扣
				{
					$sql = " SELECT sum(price) as price  FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$cart_list[0]['goods_id']." and areaid=".$_SESSION['area_rate_id']." and price_type='attr_price' and hd_id in(".$cart_list[0]['goods_attr_id'].") ";
					$goods_attr_price =$GLOBALS['db']->getOne($sql);
					$goods_shop_price['price'] = $goods_shop_price['price']+$goods_attr_price;
				}
				
				$song_goods = array();
				$song_goods[0]['rec_id'] = $goods_song['rec_id'];
				$song_goods[0]['goods_id'] = $cart_list[0]['goods_id'];
				$song_goods[0]['ysong'] = $number;
				$song_string = serialize($song_goods);
				//插入赠送商品，减少购物车正常商品的数量
				$zengsong = array(
						'session_id'    => SESS_ID,
						'act_id'       => $activity_id,
						'buy_goods_id'      => $song_string,
						'buy'      => $number,
						'song'    => $song,
						'ysong'    => $song,
						'is_finish'  => 1
							
				);
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
				$id = $GLOBALS['db']->insert_id();
				
				//插入赠送商品，减少购物车正常商品的数量
				$zengsong = array(
						'user_id'       => $_SESSION['user_id'],
						'session_id'    => SESS_ID,
						'goods_id'      => $cart_list[0]['goods_id'],
						'goods_sn'      => $goods_song['goods_sn'],
						'product_id'    => $goods_song['product_id'],
						'goods_name'    => $goods_song['goods_name'],
						'market_price'  => $goods_song['market_price'],
						'goods_number'   =>$number,
						'goods_price'   =>$goods_shop_price['price'],   //折扣价格。
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
						'zengsong' => 3,
						'activity_id'=>$activity_id,// 增加活动ID，未测试
						'song_buy'=>$number,
						
						'prec_id'=>$goods_song['rec_id'],
						'carta_id'=>$id
				
				);
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');
				
				
				$num_update = $goods_song['goods_number'] - $number;
					
				if($num_update == 0)
				{
					$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$goods_song['rec_id'];
					$GLOBALS['db']->query($sql);
				}else
				{
					
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num_update'" .
					" WHERE rec_id=".$goods_song['rec_id'];
					$GLOBALS['db']->query($sql);
				}
				$result['message'] = '購物車商品計算折扣完畢';
			}
		}else//多种商品折扣 
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
						$goods_shop_price = ceil($gv['price']*$song/10);
						$cart_list[$ck]['attr_price']  = $cart_list[$ck]['attr_price']+$goods_shop_price;
					}
				}
			}
			$cart_list = array_sort($cart_list, 'attr_price','desc');//按价格排序
				
			//开始根据设置赠送数量赠送商品
			$zengsong_s = $number;
			
			//插入赠送商品，减少购物车正常商品的数量
			$zengsongss = array(
					'session_id'    => SESS_ID,
					'act_id'       => $activity_id,
					'buy_goods_id'      => 0,
					'buy'      => $number,
					'song'    => $song,
					'ysong'    => $song,
					'is_finish'  => 1
			
			);
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsongss, 'INSERT');
			$id = $GLOBALS['db']->insert_id();
			$c_id = $id;
			
			
			foreach ($cart_list as $ck=>$cv)
			{
				if($cv['goods_number']<=0)
				{
					//过滤已赠送过的购买商品
				}else
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
					$songs= 0;
					if($zengsong_s>=$goods_song['goods_number'])//一件货品数不够赠送数大，全设置为赠送商品
					{
						$songs = $goods_song['goods_number'];
					}else //如果余下的赠送数小于这件货品，则就送余下的数即可。
					{
						$songs = $zengsong_s;
					}
					$zengsong_s = $zengsong_s - $songs; //减掉已赠送的数量。
						
					//插入赠送商品，减少购物车正常商品的数量
					$zengsong = array(
							'user_id'       => $_SESSION['user_id'],
							'session_id'    => SESS_ID,
							'goods_id'      => $cv['goods_id'],
							'goods_sn'      => $goods_song['goods_sn'],
							'product_id'    => $goods_song['product_id'],
							'goods_name'    => $goods_song['goods_name'],
							'market_price'  => $goods_song['market_price'],
							'goods_number'   =>$songs,
							'goods_price'   =>$cv['attr_price'],
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
							'zengsong' => 3,
							'activity_id'=>$activity_id,// 增加活动ID，未测试
							'song_buy'=>$number,
							'prec_id'=>$goods_song['rec_id'],
							'carta_id'=>$c_id
								
					);
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');
						
					$num_update = $goods_song['goods_number'] - $songs;
					if(empty($goods_song['carta_id']))
					{
					
					}else
					{
						$c_id = $goods_song['carta_id'].",".$c_id;
					}
					if($num_update == 0)
					{
						$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$goods_song['rec_id'];
						$GLOBALS['db']->query($sql);
					}else
					{
						$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num_update'" .", carta_id = '$c_id' " .
						" WHERE rec_id=".$goods_song['rec_id'];
						$GLOBALS['db']->query($sql);
					}
					$result['message'] = '購物車商品計算折扣完畢';
				}
			}
			
		}
	}
	
	
	
	die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'goods_att_p')//输入不可选组合
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
	
	$attr = isset($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array(); //已选择属性组成的字符串
	$goodsid = $_REQUEST['goods'];  //商品ID
	$attrvalue = $_REQUEST['attrvalue']; //选择属性值
	$act_id = $_REQUEST['act_id'];//活动ID
	/* 对属性进行重新排序和分组 */
	$messageall = get_goods_properties_two($goodsid,$attrvalue,$attr);
	
	$co = count($messageall['spe']);
	$mes = '';
	$mrid = '';
	
	$price_total = 0;
	foreach ($messageall['spe'] as $key=>$value)
	{
		$mes =$mes.'<div class="g-choose"> <span>'.$value['name']."</span><ul >";
		$mrid = '';
		foreach ($value['values'] as $v)
		{
			
			if($v['xianshi'] == 1)
			{
				if($v['css'] == 1&&$co>1)
				{
					$mrid = $v['id'];
					$mes = $mes.'<li id="attr_'.$v['id'].'" onclick="select_attr('.$v['id'].','.$key.','.$goodsid.','.$act_id.');" class="on" >'.$v['label'].
					'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"  checked="true"  />';
				}elseif ($co==1&&$v['id'] == $attrvalue)
				{
					$mrid = $v['id'];
					$mes = $mes.'<li id="attr_'.$v['id'].'" onclick="select_attr('.$v['id'].','.$key.','.$goodsid.','.$act_id.');" class="on" >'.$v['label'].
					'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"  checked="true"  />';
				}
				else
				{
					$mes = $mes.'<li id="attr_'.$v['id'].'" onclick="select_attr('.$v['id'].','.$key.','.$goodsid.','.$act_id.');"  >'.$v['label'].
					'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"    />';
				}
			}else 
			{
				$mes = $mes.'<li id="attr_'.$v['id'].'"   class="disabled" >'.$v['label'];
			}
			$mes = $mes.'</li> ';
		}
		$mes = $mes."<input type=\"hidden\" name=\"attr_b".$goodsid."_".$act_id."\" id=\"attr_b".$key."_".$goodsid."_".$act_id."\" value=\"$mrid\" />";
		$mes = $mes."</ul></div>";
	}
	$properties = get_goods_properties($goodsid);  // 获得商品的规格和属性
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
	if(empty($properties['spe']))
	{
	$mes = $mes.'<div class="g-btn mt10"><input type="button" value="確定" class="" onclick="addcart('.$goodsid.',1,'.$act_id.');"><input type="button" value="取消" class=""></div>';
	}else 
	{
		$mes = $mes.'<div class="g-btn mt10"><input type="button" value="確定" class="" onclick="addcart('.$goodsid.',0,'.$act_id.');"><input type="button" value="取消" class=""></div>';
		
	}
	$result['message'] = $mes;
	$result['goods_id'] = $goodsid;
	$result['act_id'] = $act_id;
	die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'goods_att_ps')//输入不可选组合
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');

	$attr = isset($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array(); //已选择属性组成的字符串
	$goodsid = $_REQUEST['goods'];  //商品ID
	$attrvalue = $_REQUEST['attrvalue']; //选择属性值
	$act_id = $_REQUEST['act_id'];//活动ID
	/* 对属性进行重新排序和分组 */
	$messageall = get_goods_properties_two($goodsid,$attrvalue,$attr);

	$co = count($messageall['spe']);
	$mes = '';
	$mrid = '';

	$price_total = 0;
	foreach ($messageall['spe'] as $key=>$value)
	{
		$mes =$mes.'<div class="g-choose"> <span>'.$value['name']."</span><ul >";
		$mrid = '';
		foreach ($value['values'] as $v)
		{
				
			if($v['xianshi'] == 1)
			{
				if($v['css'] == 1&&$co>1)
				{
					$mrid = $v['id'];
					$mes = $mes.'<li id="attr_'.$v['id'].'" onclick="select_attr('.$v['id'].','.$key.','.$goodsid.','.$act_id.');" class="on" >'.$v['label'].
					'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"  checked="true"  />';
				}elseif ($co==1&&$v['id'] == $attrvalue)
				{
					$mrid = $v['id'];
					$mes = $mes.'<li id="attr_'.$v['id'].'" onclick="select_attr('.$v['id'].','.$key.','.$goodsid.','.$act_id.');" class="on" >'.$v['label'].
					'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"  checked="true"  />';
				}
				else
				{
					$mes = $mes.'<li id="attr_'.$v['id'].'" onclick="select_attr('.$v['id'].','.$key.','.$goodsid.','.$act_id.');"  >'.$v['label'].
					'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"    />';
				}
			}else
			{
				$mes = $mes.'<li id="attr_'.$v['id'].'"   class="disabled" >'.$v['label'];
			}
			$mes = $mes.'</li> ';
		}
		$mes = $mes."<input type=\"hidden\" name=\"attr_b".$goodsid."_".$act_id."\" id=\"attr_b".$key."_".$goodsid."_".$act_id."\" value=\"$mrid\" />";
		$mes = $mes."</ul></div>";
	}
	$properties = get_goods_properties($goodsid);  // 获得商品的规格和属性
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
	if(empty($properties['spe']))
	{
		$mes = $mes.'<div class="g-btn mt10"><input type="button" value="確定" class="" onclick="addcart('.$goodsid.',1,'.$act_id.');"><input type="button" value="取消" class=""></div>';
	}else
	{
		$mes = $mes.'<div class="g-btn mt10"><input type="button" value="確定" class="" onclick="addcart('.$goodsid.',0,'.$act_id.');"><input type="button" value="取消" class=""></div>';

	}
	$result['message'] = $mes;
	$result['goods_id'] = $goodsid;
	$result['act_id'] = $act_id;
	die($json->encode($result));
}
elseif($_REQUEST['step'] == 'add_to_flowcart'){

	include('includes/cls_json.php');
	$json   = new JSON;
	
	$result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
	$goods_string = $_POST['goods']; //购物车参与该活动列表
	$goods_string = substr($goods_string,0,strlen($goods_string)-1);
	$goods_string = explode(',', $goods_string);
	$buy = $_POST['buy'];//买几值
	$act_id = $_POST['act_id'];//活动ID
	$pan = $_POST['pan'];//判断是否有赠品
	
	$result['actid'] = $act_id;
	$sql = "SELECT act_type,buy,act_name FROM ".$ecs->table('favourable_activity')." WHERE act_id=".$act_id;
	$act_type = $db->getRow($sql);
	
	$buy_list = unserialize($act_type['buy']);
	$song = 0; //查看送的值
	foreach ($buy_list as $key=>$value)
	{
		
		if($key==$buy)
		{
			
			$song = $value;
		}
	}
	
	$result['songs'] = $song;
	
	if($act_type['act_type'] == 3)//買几送几設置
	{
		if($pan == 1)//设置有赠品
		{
			foreach ($goods_string as $k=>$v)
			{
				$goodsinfo = explode('_', $v);
				$goods_wuz = $goods_wuz.$goodsinfo[0].',';
			}
			$goods_wuz = $goods_wuz.'0';
			$sql = " SELECT * FROM ".$ecs->table('cart')." WHERE rec_id in(".$goods_wuz.") ";
			$cart_list_wuz = $db->getAll($sql);
			
			//插入赠送商品，减少购物车正常商品的数量
			$zengsongss = array(
					'session_id'    => SESS_ID,
					'act_id'       => $act_id,
					'buy_goods_id'      => 0,
					'buy'      => $buy,
					'song'    => $song,
					'ysong'    => 0,
					'is_finish'  => 0
						
			);
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsongss, 'INSERT');
			$id = $GLOBALS['db']->insert_id();
			
			$song_goods = array();
			foreach ($cart_list_wuz as $key=>$value)
			{
				foreach ($goods_string as $k=>$v)
				{
					$goodsinfo = explode('_', $v);
						
					if($goodsinfo[0] === $value['rec_id'])
					{
						$can_song = $goodsinfo[1];
					}
				}
				
				$sql = " SELECT price FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$value['goods_id']." AND price_type='shop_price' and  areaid=".$_SESSION['area_rate_id']." GROUP BY goods_id ";
				$price_yuan = $db->getOne($sql);
				if(!empty($value['goods_attr_id']))
				{
				$sql = " SELECT sum(price) FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$value['goods_id']." and areaid=".$_SESSION['area_rate_id']." and price_type='attr_price' and hd_id in(".$value['goods_attr_id'].") ";
				$price_arrt = $db->getOne($sql);
				}else 
				{
					$price_arrt = 0;
				}
				$price_que = $price_arrt + $price_yuan;
				if($price_que!=$value['goods_price'])
				{
					$bf_goods_cart = $value;
					$goods_cart = $bf_goods_cart;
					
					$goods_cart['goods_price'] = $price_que;
					$goods_cart['goods_number'] = $can_song;
					$goods_cart['zengsong'] = 0;
					$goods_cart['is_jianshu'] = 1;
					$goods_cart['song_buy'] = $buy;
					$goods_cart['activity_id'] = $act_id;
					$goods_cart['carta_id'] = $id;
					$goods_cart['song_num'] = $can_song;
					$goods_cart['prec_id'] = $id;
					$goods_cart['cf_huodong'] = $value['cf_huodong'];
					unset($goods_cart['rec_id']);
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
					$nid = $GLOBALS['db']->insert_id();
					
					if($can_song>=$value['goods_number'])
					{
						$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$value['rec_id'];
						$GLOBALS['db']->query($sql);
					}else 
					{
						$bfnumber = $value['goods_number'] - $can_song;
						$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$bfnumber."' " .", carta_id = '".$bf_goods_cart['carta_id']."' " .
								" WHERE rec_id=".$bf_goods_cart['rec_id'];
						$GLOBALS['db']->query($sql);
						
						
						$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$value['product_id']);
						$goods_price = get_final_price_new1($value['goods_id'],$bfnumber,true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);
						
						$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=".$goods_price.
						" WHERE rec_id=".$value['rec_id'];
						$GLOBALS['db']->query($sql);
					}
					
					$song_goods[$key]['rec_id'] = $nid;
					$song_goods[$key]['goods_id'] = $bf_goods_cart['goods_id'];
					$song_goods[$key]['ysong'] = $can_song;
				}else  
				{
				
					$cidd = '';
					//划出参与活动商品
					if($zeng_goods['carta_id']==0||empty($zeng_goods['carta_id']))
					{
						$cidd = $id;
					}else
					{
						$cidd = $zeng_goods['carta_id'].','.$id;
					}
					$song_numz = $can_song + $value['song_num'];
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  song_num = '".$song_numz."' " .", carta_id = '".$cidd."' " .
							" WHERE rec_id=".$value['rec_id'];
					$GLOBALS['db']->query($sql);
					
					$song_goods[$key]['rec_id'] = $value['rec_id'];
					$song_goods[$key]['goods_id'] = $value['goods_id'];
					$song_goods[$key]['ysong'] = $can_song;
				}
			}
			//需要更新记录表信息
			$song_string = serialize($song_goods);
			$sql = "UPDATE " . $GLOBALS['ecs']->table('cart_activity') . " SET buy_goods_id ='".$song_string .
			"' WHERE recs_id = ".$id ;
			
			$GLOBALS['db']->query($sql);
			$result['message'] = '1';
		}else //取参与活动商品最便宜的几件送
		{
			
			$goods_wuz = '';
			foreach ($goods_string as $k=>$v)
			{
				$goodsinfo = explode('_', $v);
				$goods_wuz = $goods_wuz.$goodsinfo[0].',';
			}
			$goods_wuz = $goods_wuz.'0';
			$sql = " SELECT * FROM ".$ecs->table('cart')." WHERE rec_id in(".$goods_wuz.") ";
			$cart_list_wuz = $db->getAll($sql);
			$goods_ids= '';
			foreach ($cart_list_wuz as $ck=>$cv)
			{
				$goods_ids = $goods_ids.$cv['goods_id'].",";
			}
			$goods_ids = substr($goods_ids,0,strlen($goods_ids)-1);
			
			$sql = " SELECT price,goods_id FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id in(".$goods_ids.") AND price_type='shop_price' and  areaid=1 GROUP BY goods_id ";
				
				
			$goods_yprice = $GLOBALS['db']->getAll($sql); //取各个商品地区店售价
			foreach ($cart_list_wuz as $ck=>$cv)  //查询购物车货品列表属性价格
			{
				$cart_list_wuz[$ck]['attr_price'] = 0;
				if(!empty($cv['goods_attr_id']))
				{
					$sql = " SELECT sum(price) FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$cv['goods_id']." and areaid=1 and price_type='attr_price' and hd_id in(".$cv['goods_attr_id'].") ";
						
					$cart_list_wuz[$ck]['attr_price'] = $GLOBALS['db']->getOne($sql);
				}
			}
				
			foreach ($cart_list_wuz as $ck=>$cv)//合并属性价格 用于排序哪个货品价格更便宜
			{
				foreach ($goods_yprice as $gk=>$gv)
				{
					if($cv['goods_id'] == $gv['goods_id'])
					{
						$cart_list_wuz[$ck]['attr_price']  = $cart_list_wuz[$ck]['attr_price']+$gv['price'];
					}
				}
			}
			$cart_list_wuz = array_sort($cart_list_wuz, 'attr_price','asc');//按价格排序
			
			$zengsong_s = $song;
			
			//插入赠送商品，减少购物车正常商品的数量
			$zengsongss = array(
					'session_id'    => SESS_ID,
					'act_id'       => $act_id,
					'buy_goods_id'      => 0,
					'buy'      => $buy,
					'song'    => $song,
					'ysong'    => $song,
					'is_finish'  => 1
			
			);
			
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsongss, 'INSERT');
			$id = $GLOBALS['db']->insert_id();
			$c_id = $id;
			$song_goods = array();
			$can_song = 0;
			foreach ($cart_list_wuz as $key=>$value)
			{
				foreach ($goods_string as $k=>$v)
				{
					$goodsinfo = explode('_', $v);
					
					if($goodsinfo[0] === $value['rec_id'])
					{
						$can_song = $goodsinfo[1];
						//echo  $goodsinfo;
						
					}
					
					
				}
				
				$sql = " SELECT price FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$value['goods_id']." AND price_type='shop_price' and  areaid=".$_SESSION['area_rate_id']." GROUP BY goods_id ";
				$price_yuan = $db->getOne($sql);
			if(!empty($value['goods_attr_id']))
				{
				$sql = " SELECT sum(price) FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$value['goods_id']." and areaid=".$_SESSION['area_rate_id']." and price_type='attr_price' and hd_id in(".$value['goods_attr_id'].") ";
				$price_arrt = $db->getOne($sql);
				}else 
				{
					$price_arrt = 0;
				}
				$price_que = $price_arrt + $price_yuan;
				$ccid = $value['rec_id'];
				$yuangoods_number = $value['goods_number'];
				$yuangoods_number = $yuangoods_number- $can_song;
				/*
				 * 
				 * 需要插入该送的赠品。要用赠送数量来判断结束
				 * */
				$zeng_goods = $value;
				
					if($song>=0) //这里错误。22号修改。$song应该到这判断大于0
					{
						if($song>=$can_song)
						{
							$zeng_goods['goods_price'] = 0;
							$zeng_goods['goods_number'] = $can_song;
							$zeng_goods['zengsong'] = 1;
							$zeng_goods['song_buy'] = $buy;
							$zeng_goods['activity_id'] = $act_id;
							$zeng_goods['carta_id'] = $id;
							$zeng_goods['prec_id'] = $zeng_goods['rec_id'];
							$zeng_goods['cf_huodong'] = $zeng_goods['cf_huodong'];
							
								
							unset($zeng_goods['rec_id']);
							
							$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zeng_goods, 'INSERT');
							
							if($yuangoods_number == 0)//该正常商品赠送完，要删除该记录
							{
								$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$value['rec_id'];
								$GLOBALS['db']->query($sql);
							}else //更新该商品赠送记录
							{
									$cidd = '';
									//划出参与活动商品
									if($zeng_goods['carta_id']==0||empty($zeng_goods['carta_id']))
									{
										$cidd = $id;
									}else
									{
										$cidd = $zeng_goods['carta_id'].','.$id;
									}
								
								$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$yuangoods_number."' " .", carta_id = '".$cidd."' " .
										" WHERE rec_id=".$value['rec_id'];
								$GLOBALS['db']->query($sql);
							}
						}else 
						{
							$zeng_goods['goods_price'] = 0;
							$zeng_goods['goods_number'] = $song;
							$zeng_goods['zengsong'] = 1;
							$zeng_goods['song_buy'] = $buy;
							$zeng_goods['activity_id'] = $act_id;
							$zeng_goods['carta_id'] = $id;
							$zeng_goods['prec_id'] = $zeng_goods['rec_id'];
							$zeng_goods['cf_huodong'] = $zeng_goods['cf_huodong'];
								
								
							unset($zeng_goods['rec_id']);
							if($song >0)
							{
							$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zeng_goods, 'INSERT');
							}
							$yuangoods_number = $yuangoods_number - $song+$can_song;
							if($yuangoods_number == 0)//该正常商品赠送完，要删除该记录
							{
								$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$value['rec_id'];
								$GLOBALS['db']->query($sql);
							}else //更新该商品赠送记录
							{
								if($price_que!=$value['goods_price'])
								{
									$bf_goods_cart = $value;
									$goods_cart = $bf_goods_cart;
									//$huanumber = $can_song - $song;
									$bfnumber = $yuangoods_number -($can_song-$song) ;
									$goods_cart['goods_price'] = $price_que;
									$goods_cart['goods_number'] = $can_song-$song;
									$goods_cart['zengsong'] = 0;
									$goods_cart['song_buy'] = $buy;
									$goods_cart['activity_id'] = $act_id;
									$goods_cart['carta_id'] = $id;
									$goods_cart['song_num'] = $can_song-$song;
									$goods_cart['is_jianshu'] = 1;
									$goods_cart['prec_id'] = $id;
									$goods_cart['cf_huodong'] = $value['cf_huodong'];
									unset($goods_cart['rec_id']);
									$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
									$ccid = $GLOBALS['db']->insert_id();
									if($bfnumber<= 0 )
									{
										$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$value['rec_id'];
										$GLOBALS['db']->query($sql);
									}else
									{
										
										
										$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$bfnumber."' " .", carta_id = '".$bf_goods_cart['carta_id']."' " .
												" WHERE rec_id=".$value['rec_id'];
										$GLOBALS['db']->query($sql);
										$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$value['product_id']);
										$goods_price = get_final_price_new1($value['goods_id'],$bfnumber,true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);
										
										$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=".$goods_price.
										" WHERE rec_id=".$value['rec_id'];
										$GLOBALS['db']->query($sql);
									}
									
								}else 
								{
										$cidd = '';
										//划出参与活动商品
										if($zeng_goods['carta_id']==0||empty($zeng_goods['carta_id']))
										{
											$cidd = $id;
										}else
										{
											$cidd = $zeng_goods['carta_id'].','.$id;
										}
									$song_num = $zeng_goods['song_num']+$can_song-$song;
									$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$yuangoods_number."' " .", carta_id = '".$cidd."' " .
											", song_num = '".$song_num."' " .
											" WHERE rec_id=".$value['rec_id'];
									$GLOBALS['db']->query($sql);
								}
						}
						$cunshu = $can_song -$song;
						$song_goods[$key]['rec_id'] = $ccid;
						$song_goods[$key]['goods_id'] = $value['goods_id'];
						$song_goods[$key]['ysong'] = $cunshu;
						
						}
						$song = $song-$can_song;
						//$can_song = $song;
					
				}else 
				{
					if($price_que!=$value['goods_price'])
					{
						$bf_goods_cart = $value;
						$goods_cart = $bf_goods_cart;
						$ccid11 = $goods_cart['rec_id'];
						$goods_cart['goods_price'] = $price_que;
						$goods_cart['goods_number'] = $can_song;
						$goods_cart['zengsong'] = 0;
						$goods_cart['song_buy'] = $buy;
						$goods_cart['activity_id'] = $act_id;
						$goods_cart['carta_id'] = $id;
						$goods_cart['song_num'] = $can_song;
						$goods_cart['is_jianshu'] = 1;
						$goods_cart['prec_id'] = $id;
						$goods_cart['cf_huodong'] = $value['cf_huodong'];
						unset($goods_cart['rec_id']);
						$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
						$ccid = $GLOBALS['db']->insert_id();
						
						if($can_song>=$value['goods_number'])
						{
							$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$value['rec_id'];
							$GLOBALS['db']->query($sql);
						}else
						{
							$bfnumber = $value['goods_number'] - $can_song;
							$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$bfnumber."' " .", carta_id = '".$bf_goods_cart['carta_id']."' " .
												" WHERE rec_id=".$value['rec_id'];
							$GLOBALS['db']->query($sql);
							$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$value['product_id']);
							$goods_price = get_final_price_new1($value['goods_id'],$bfnumber,true,$value['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);
										
							$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=".$goods_price.
										" WHERE rec_id=".$value['rec_id'];
										$GLOBALS['db']->query($sql);
						}
						
						
					}else 
					{
					//更新划出已参与活动商品
					if($zeng_goods['carta_id']==0||empty($zeng_goods['carta_id']))
					{
						$zeng_goods['carta_id'] = $id;
					}else
					{
						$zeng_goods['carta_id']= $zeng_goods['carta_id'].','.$id;
					}
					$song_num = $zeng_goods['song_num']+$can_song;
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  song_num = '".$song_num."' " .", carta_id = '".$zeng_goods['carta_id']."' " .
							" WHERE rec_id=".$zeng_goods['rec_id'];
					
					$GLOBALS['db']->query($sql);
					}
					
					$song_goods[$key]['rec_id'] = $ccid;
					$song_goods[$key]['goods_id'] = $value['goods_id'];
					$song_goods[$key]['ysong'] = $can_song;
				}
				
				
			}
			//需要更新记录表信息 
			$song_string = serialize($song_goods);
			$sql = "UPDATE " . $GLOBALS['ecs']->table('cart_activity') . " SET buy_goods_id ='".$song_string .
			"' WHERE recs_id = ".$c_id ;
				
			$GLOBALS['db']->query($sql);
			
		}
	}elseif ($act_type['act_type'] == 6)
	{
		
		//插入赠送商品，减少购物车正常商品的数量 $buy.'---11111111111111111111111111111111----'.$act_id.'===='.$_SESSION['area_rate_id']
		$zengsong = array(
				'session_id'    => SESS_ID,
				'act_id'       => $act_id,
				'buy_goods_id'      => 0,
				'buy'      => $buy,
				'song'    => 0,
				'ysong'    => $buy,
				'is_finish'  => 1
		
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
		$id = $GLOBALS['db']->insert_id();
		
		foreach ($goods_string as $k=>$v)
			{
				$goodsinfo = explode('_', $v);
				$goods_wuz = $goods_wuz.$goodsinfo[0].',';
			}
			$goods_wuz = $goods_wuz.'0';
			$sql = " SELECT * FROM ".$ecs->table('cart')." WHERE rec_id in(".$goods_wuz.") ";
			$cart_list_wuz = $db->getAll($sql);
			$goods_ids= '';
			foreach ($cart_list_wuz as $ck=>$cv)
			{
				$goods_ids = $goods_ids.$cv['goods_id'].",";
			}
			$goods_ids = substr($goods_ids,0,strlen($goods_ids)-1);
			
			$sql = " SELECT price,goods_id FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id in(".$goods_ids.") AND price_type='shop_price' and  areaid=1 GROUP BY goods_id ";
				
				
			$goods_yprice = $GLOBALS['db']->getAll($sql); //取各个商品地区店售价
			foreach ($cart_list_wuz as $ck=>$cv)  //查询购物车货品列表属性价格
			{
				$cart_list_wuz[$ck]['attr_price'] = 0;
				if(!empty($cv['goods_attr_id']))
				{
					$sql = " SELECT sum(price) FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$cv['goods_id']." and areaid=1 and price_type='attr_price' and hd_id in(".$cv['goods_attr_id'].") ";
						
					$cart_list_wuz[$ck]['attr_price'] = $GLOBALS['db']->getOne($sql);
				}
			}
				
			foreach ($cart_list_wuz as $ck=>$cv)//合并属性价格 用于排序哪个货品价格更便宜
			{
				foreach ($goods_yprice as $gk=>$gv)
				{
					if($cv['goods_id'] == $gv['goods_id'])
					{
						$cart_list_wuz[$ck]['attr_price']  = $cart_list_wuz[$ck]['attr_price']+$gv['price'];
					}
				}
			}
			$cart_list_wuz = array_sort($cart_list_wuz, 'attr_price','asc');//按价格排序
			sort($buy_list);
			$zk_key = 0;
			$tlin_number = 0;
			
			foreach ($goods_string as $k=>$v)
			{
				$goodsinfo = explode('_', $v);
				$tlin_number = $tlin_number+(int)$goodsinfo[1];
				
			}
			$total_num = count($buy_list);
			
			if($total_num!=$tlin_number)//处理购物车数量不够活动多。删除更优惠的折扣。不然就会按更优惠的折扣计算商品了。
			{
					
				$quchu = $total_num - $tlin_number;
				for($q=0;$q<$quchu;$q++)
				{
				unset($buy_list[$q]);
			
				$zk_key = $zk_key+1;
				}
				}
			
				
			foreach ($cart_list_wuz as $key=>$value)
			{
				
				$lin_number =0;
				
				foreach ($goods_string as $k=>$v)
				{
					$goodsinfo = explode('_', $v);
					
					if($goodsinfo[0]==$value['rec_id'])
					{
						$lin_number = $goodsinfo[1];
					}
				}
				
				$goods_cart = $value;
				$goods_number = $goods_cart['goods_number']-$goods_cart['song_num'];
					
				$bf_goods_cart = $goods_cart;
				$bf_goods_cart['goods_number'] = $bf_goods_cart['goods_number'] - $lin_number;//这个还得对应上数量
				
				for ($i=1;$i<=$lin_number;$i++)
				{	
					
					$goods_cart['goods_price'] = $value['attr_price']*$buy_list[$zk_key]/10;
					$goods_cart['goods_number'] = 1;
					$goods_cart['zengsong'] = 6;
					$goods_cart['song_buy'] = $buy;
					$goods_cart['activity_id'] = $act_id;
					$goods_cart['extension_code'] = 'package_buy_all'.$act_id;
					$goods_cart['carta_id'] = $id;
					$goods_cart['prec_id'] = $goods_cart['rec_id'];
					$goods_cart['cf_huodong'] = $goods_cart['cf_huodong'];
					unset($goods_cart['rec_id']);
					$zk_key= $zk_key+1;
					$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
				}
				
				if($bf_goods_cart['carta_id']==0||empty($bf_goods_cart['carta_id']))
				{
					$bf_goods_cart['carta_id'] = $id;
				}else
				{
					$bf_goods_cart['carta_id']= $bf_goods_cart['carta_id'].','.$id;
				}
					
				if($bf_goods_cart['goods_number'] !=0)
				{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$bf_goods_cart['goods_number']."' " .", carta_id = '".$bf_goods_cart['carta_id']."' " .
							" WHERE rec_id=".$bf_goods_cart['rec_id'];
					$GLOBALS['db']->query($sql);
						
					$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$bf_goods_cart['product_id']);
					$goods_price = get_final_price_new1($bf_goods_cart['goods_id'],$bf_goods_cart['goods_number'],true,$bf_goods_cart['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);
						
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=".$goods_price.
					" WHERE rec_id=".$bf_goods_cart['rec_id'];
					$GLOBALS['db']->query($sql);
						
				}else
				{
					$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$bf_goods_cart['rec_id'];
					$GLOBALS['db']->query($sql);
				}
			}
		
	}
	elseif($act_type['act_type'] == 5)//买几总价多少设置
	{
		$cun_zj =array();
		foreach ($buy_list as $key=>$value)
		{
			
			if($buy_list[$key]['buy'] ==$buy)
			{
				$cun_zj = $buy_list[$key];
			}
		}
		foreach ($cun_zj as $key=>$value)
		{
			if($key == $_SESSION['area_rate_id'])
			{
				$song = $value;
			}
		}
		
		//插入赠送商品，减少购物车正常商品的数量 $buy.'---11111111111111111111111111111111----'.$act_id.'===='.$_SESSION['area_rate_id']
		$zengsong = array(
				'session_id'    => SESS_ID,
				'act_id'       => $act_id,
				'buy_goods_id'      => 0,
				'buy'      => $buy,
				'song'    => 0,
				'ysong'    => $buy,
				'is_finish'  => 1
		
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
		$id = $GLOBALS['db']->insert_id();
		
		
	
		
		
		foreach ($goods_string as $k=>$v)
		{
			$goodsinfo = explode('_', $v);
			$sql = "SELECT * FROM ".$ecs->table('cart')." WHERE rec_id=".$goodsinfo[0];
			$goods_cart = $db->getRow($sql);
			$goods_number = $goods_cart['goods_number']-$goods_cart['song_num'];
			
			$bf_goods_cart = $goods_cart;
			$bf_goods_cart['goods_number'] = $bf_goods_cart['goods_number'] - $goodsinfo[1];
			
			$goods_cart['goods_price'] = 0;
			$goods_cart['goods_number'] = $goodsinfo[1];
			$goods_cart['zengsong'] = 5;
			$goods_cart['song_buy'] = $buy;
			$goods_cart['activity_id'] = $act_id;
			$goods_cart['extension_code'] = 'package_buy_all'.$act_id;
			$goods_cart['carta_id'] = $id;
			$goods_cart['prec_id'] = $goods_cart['rec_id'];
			$goods_cart['cf_huodong'] = $goods_cart['cf_huodong'];
			unset($goods_cart['rec_id']);
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
			
			if($bf_goods_cart['carta_id']==0||empty($bf_goods_cart['carta_id']))
			{
				$bf_goods_cart['carta_id'] = $id;
			}else
			{
				$bf_goods_cart['carta_id']= $bf_goods_cart['carta_id'].','.$id;
			}
			
			if($bf_goods_cart['goods_number'] !=0)
			{
				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$bf_goods_cart['goods_number']."' " .", carta_id = '".$bf_goods_cart['carta_id']."' " .
						" WHERE rec_id=".$bf_goods_cart['rec_id'];
				$GLOBALS['db']->query($sql);
					
				$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$bf_goods_cart['product_id']);
				$goods_price = get_final_price_new1($bf_goods_cart['goods_id'],$bf_goods_cart['goods_number'],true,$bf_goods_cart['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);
					
				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=".$goods_price.
				" WHERE rec_id=".$bf_goods_cart['rec_id'];
				$GLOBALS['db']->query($sql);
					
			}else
			{
				$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$bf_goods_cart['rec_id'];
				$GLOBALS['db']->query($sql);
			}
			
		}
		//插入一条总价的记录
		$zongjia = array(
				'user_id' => $_SESSION['user_id'],
				'session_id' => SESS_ID,
				'goods_id' => 0,
				'goods_sn' => 0,
				'goods_name' => $act_type['act_name'],
				'market_price' =>0,                           
				'goods_price' =>$song,
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
				'activity_id' => $act_id,
				'song_buy'=>$buy,
				'prec_id' => 0,
				'song_num' => 0,
				'carta_id' =>$id,
				'is_jianshu' =>0,
				'cf_huodong'=> 1,
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zongjia, 'INSERT');

		//插入一条总价的记录
	}
elseif ($act_type['act_type'] == 4)//買几打折設置
	{		
		//插入赠送商品，减少购物车正常商品的数量
		$zengsong = array(
				'session_id'    => SESS_ID,
				'act_id'       => $act_id,
				'buy_goods_id'      => 0,
				'buy'      => $buy,
				'song'    => $song,
				'ysong'    => $buy,
				'is_finish'  => 1
		
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_activity'), $zengsong, 'INSERT');
		$id = $GLOBALS['db']->insert_id();
		
		foreach ($goods_string as $k=>$v)
		{
			$goodsinfo = explode('_', $v);
			
			$sql = "SELECT * FROM ".$ecs->table('cart')." WHERE rec_id=".$goodsinfo[0];
			$goods_cart = $db->getRow($sql);
			$goods_number = $goods_cart['goods_number']-$goods_cart['song_num'];
			
				$bf_goods_cart = $goods_cart;
				$bf_goods_cart['goods_number'] = $bf_goods_cart['goods_number'] - $goodsinfo[1];
				$sql = " SELECT price FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$goods_cart['goods_id']." AND price_type='shop_price' and  areaid_rate=".$_SESSION['area_rate_id']." GROUP BY goods_id ";
				$price_yuan = $db->getOne($sql);
		if(!empty($value['goods_attr_id']))
				{
				$sql = " SELECT sum(price) FROM ".$GLOBALS['ecs']->table('price_area')." WHERE goods_id=".$value['goods_id']." and areaid=".$_SESSION['area_rate_id']." and price_type='attr_price' and hd_id in(".$value['goods_attr_id'].") ";
				$price_arrt = $db->getOne($sql);
				}else 
				{
					$price_arrt = 0;
				}
				$price_que = $price_arrt + $price_yuan;
				$goods_cart['goods_price'] = ceil($price_que*$song/10);
				$goods_cart['goods_number'] = $goodsinfo[1];
				$goods_cart['zengsong'] = 3;
				$goods_cart['song_buy'] = $buy;
				$goods_cart['activity_id'] = $act_id;
				$goods_cart['carta_id'] = $id;
				$goods_cart['prec_id'] = $goods_cart['rec_id'];
				$goods_cart['cf_huodong'] = $goods_cart['cf_huodong'];
				unset($goods_cart['rec_id']);
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $goods_cart, 'INSERT');
				
				if($bf_goods_cart['carta_id']==0||empty($bf_goods_cart['carta_id']))
				{
					$bf_goods_cart['carta_id'] = $id;
				}else 
				{
					$bf_goods_cart['carta_id']= $bf_goods_cart['carta_id'].','.$id;
				}
				if($bf_goods_cart['goods_number'] !=0)
				{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_number = '".$bf_goods_cart['goods_number']."' " .", carta_id = '".$bf_goods_cart['carta_id']."' " .
					" WHERE rec_id=".$bf_goods_cart['rec_id'];
					$GLOBALS['db']->query($sql);
					
					$product_sn = $GLOBALS['db']->getOne('select product_sn from '.$GLOBALS['ecs']->table('products')." where product_id=".$bf_goods_cart['product_id']);
					$goods_price = get_final_price_new1($bf_goods_cart['goods_id'],$bf_goods_cart['goods_number'],true,$bf_goods_cart['goods_attr_id'],$_SESSION['area_rate_id'],$product_sn);
					
					$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET  goods_price=".$goods_price.
					" WHERE rec_id=".$bf_goods_cart['rec_id'];
					$GLOBALS['db']->query($sql);
					
				}else
				{
					$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id=".$bf_goods_cart['rec_id'];
					$GLOBALS['db']->query($sql);
				}
			
		}
		
	}
	
	die($json->encode($result));
}
elseif($_REQUEST['step'] == 'add_to_cart'){

    include('includes/cls_json.php');
    $json   = new JSON;

    $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');

    $goods_id = intval($_POST['goods']);
    $attr = trim($_POST['attr']);
    $act_id = intval($_POST['act_id']);

   
    
    $sql = "SELECT * FROM ".$ecs->table('cart_activity')." where act_id=$act_id  and is_finish=0 and session_id = '".SESS_ID."'  limit 1 ";
    $cart_activity = $GLOBALS['db']->getRow($sql);
   
    if(empty($cart_activity)) //判斷贈品是否存在
    {
    	
    	$result['error'] = 1;
    	$result['message'] = 3;//"当前购物车的商品还没有满足该活动的条件或沒選擇贈送套餐，不能添加赠品";
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
    	
                //赠品加入購物車
                $sql = "select goods_sn,goods_name,shop_price,suppliers_id,is_real,is_shipping,fuwu from " . $GLOBALS['ecs']->table('goods') . " where goods_id = $goods_id";
                $goods_info = $GLOBALS['db']->getRow($sql);

                $attrs = str_replace(",", "|", $attr);
                $sql = "select product_id,product_status from " . $GLOBALS['ecs']->table('products') . " where goods_id=$goods_id and areaid = 0 and product_status = 1 and goods_attr = '" . $attrs . "'";
                $product = $GLOBALS['db']->getRow($sql);

                $attr_list = explode(',', $attr);
                $at = array();
                if(!empty($attr_list[0]))
                {
                	
                foreach ($attr_list as $v) {
                    $sql = "select attr_value from " . $GLOBALS['ecs']->table('goods_attr') . " where goods_attr_id = $v";
                    $at[] = $GLOBALS['db']->getOne($sql);
                }
                $attr_value = implode('  ', $at);
                }else 
                {
                	$attr_value = '';
                }
                if (!empty($product) && !empty($product['product_status'])) {
                    //插入赠送商品，减少购物车正常商品的数量
                    $zengsong = array(
                        'user_id' => $_SESSION['user_id'],
                        'session_id' => SESS_ID,
                        'goods_id' => $goods_id,
                        'goods_sn' => $goods_info['goods_sn'],
                        'product_id' => $product['product_id'],
                        'goods_name' => $goods_info['goods_name'],
                        'market_price' => $goods_info['shop_price'],
                        'goods_number' => 1,
                        'goods_price' => 0,
                        'goods_attr' => $attr_value,
                        'parent_id' => 0,
                        'goods_attr_id' => $attr,
                        'is_real' => $goods_info['is_real'],
                        'extension_code' => '',
                        'is_gift' => 0,
                        'is_shipping' => $goods_info['is_shipping'],
                        'rec_type' => CART_GENERAL_GOODS,
                        'areaid' => $_SESSION['area_rate_id'],
                        'suppliers_id' => $goods_info['suppliers_id'],
                        'fuwu' => $goods_info['fuwu'],
                        'fen_cheng' => 0,
                        'zengsong' => 2,
                        'activity_id' => $act_id,
                        'song_buy'=>0,
                        'prec_id'=>0,
                        'carta_id'=>$cart_activity['recs_id'],
                        'cf_huodong'=>1
                    );

                    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $zengsong, 'INSERT');

                    $result['error'] = 0;
                    $result['message'] = 0;//'赠品成功加入购物车';

                    $result['content'] = insert_cart_info();
                    $result['content1'] = insert_cart_order(array('id'=>'left'));
                    $result['content2'] = insert_cart_order(array('id'=>'right'));
                    $result['one_step_buy'] = $_CFG['one_step_buy'];
                }else{
                    if(!empty($product['product_status'])){
                        $result['error'] = 1;
                        $result['message'] = 1;//"该赠品的属性{$attr_value}没货，请重新选择";
                    }else{
                        $result['error'] = 1;
                        $result['message'] = 5;//"该赠品的属性选择不正确，请重新选择";
                    }
                }
    }

    	die($json->encode($result));
}
function multi_array_sort($multi_array,$sort_key,$sort=SORT_ASC){
	if(is_array($multi_array)){
		foreach ($multi_array as $row_array){
			if(is_array($row_array)){
				$key_array[] = $row_array[$sort_key];
			}else{
				return false;
			}
		}
	}else{
		return false;
	}
	array_multisort($key_array,$sort,$multi_array);
	return $multi_array;
}

//追加分页
function appendPageAct($res){

    $result = '';
    foreach ($res['act_range_ext'] as $k => $v) {
        $result .= '<div class="wrap">';
        $result .= '<div class="item">';
        $result .= '<div class="pic-box">';
        $result .= '<a class="pic"  href="'.$v['url'].'">';
        $result .= '<img src="'.$v['goods_thumb'].'" alt="">';
        $result .= '</a>';
        $result .= '<div class="ps-wrap">';
        $result .= '<span class="ps-btn ps-prev iconfont icon-left disabled">';
        $result .= '</span>';
        $result .= '<div class="p-scroll">';
        $result .= '<ul style="transform:translateX(0)">';

        foreach ($v['pictures'] as $k2 => $v2) {
            if($k2 == 0)
            {
                $result .= '<li class="ps-item curr">';
            } 
            else 
            {
                $result .= '<li class="ps-item">';
            }

            if($v2['thumb_url'])
            {
                $result .= ' <img src="'.$v2['thumb_url'] .'" alt="">';
            } 
            else 
            {
                $result .= ' <img src="'.$v2['img_url'] .'" alt="">';
            }
            $result .= '</li>';
        }
        $result .= '</ul>';
        $result .= '</div>';
        $result .= '<span class="ps-btn ps-next iconfont icon-right">';
        $result .= '</span>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<div class="items-info">';
        $result .= '<a href="'.$v['url'].'" class="name">'.$v['goods_name'].'</a>';
        $result .= '<div class="price-highlight"></div><del><span id="towbeigoodsprice_'.$v['goods_id'].'">';
        
        $result .= $v['shop_price'];
         
        $result .= '</span>';
        $result .= '<input type="hidden" id="beigoodsprice_'.$v['goods_id'].'" value="'.$v['min_price'].'">';
        $result .= '</del>';
        $result .= '<div class="act">';
        foreach ($v['shop_price_c_z'] as $shop_price_c_z) {
        	$result .= '<p class="keynote">混選'.$shop_price_c_z['zk_s'].'件 每件<span id="onebeigoodsprice_'.$v['goods_id'].'">';
        	$result .= $shop_price_c_z['shop'] ;
        	$result .= '</span>起</p>';
        }
        
        foreach ($res['total'] as $shop_price_c_total) {
        	$result .= '<p class="keynote">混選'.$shop_price_c_total['buy'].'件 每件'.$shop_price_c_total['pj_price'].'起';
        	$result .= '</p>';
        }
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<div class="more-box">';
        
        $result .= '<div class="showinfo">';
        $result .= '<div id="div_'.$v['goods_id'].'">';

        foreach ($v['specification'] as $spec_key=> $spe) {
            $result .= '<dl>';
            $result .= '<dt class="box-w25">'.$spe['name'].':</dt>';
            $result .= '<dd class="box-w75">';
            $result .= '<ul class="infotop">';

            foreach($spe['values'] as $spe_value){
                $result .= '<li id="url_'.$spe_value['id'].'_'.$v['goods_id'].'" onclick="changeP(\'spec_'.$spec_key.'_'.$v['goods_id'].'\',\''.$spe_value['id'].'\','.$v['goods_id'].','.$spec_key.')" >';
                $result .= '<span name="sp_url_'.$spec_key.'_'.$v['goods_id'].'" >'.$spe_value['label'].'</span>';
                
                $result .= '<input style="display:none" id="spec_value_'.$spe_value['id'].'_'.$v['goods_id'].'" type="radio" name="spec_'.$spec_key.'_'.$v['goods_id'].'" value="'.$spe_value['id'].'"/>'; 
                $result .= '</li>';
            }

            $result .= '<input type="hidden" name="attr_'.$v['goods_id'].'" id="attr_'.$spec_key.'_'.$v['goods_id'].'"  />';
            $result .= '</ul>';
            $result .= '</dd>';
            $result .= '</dl>';
        }
        $result .= '</div>';

        foreach ($v['specification'] as $spec_key => $spe) {
            $result .= '<input type="hidden" name="attr_a_'.$v['goods_id'].'" id="attr_a_'.$spec_key.'_'.$v['goods_id'].'" />';
        }

        $result .= '<dl>';                  
        $result .= '<dt class="box-w25">數量:</dt>';
        $result .= '<dd class="box-w75">';
        $result .= '<div class="buy-num-box">';
        $result .= '<input type="text" name="number_'.$v['goods_id'].'" id="number_'.$v['goods_id'].'" min="1" class="buy-num" value="1" onkeyup="1==this.value.length?this.value=this.value.replace(/[^1-9]/g,\'\'):this.value=this.value.replace(/\D/g,\'\')" onafterpaste="1==this.value.length?this.value=this.value.replace(/[^1-9]/g,\'\'):this.value=this.value.replace(/\D/g,\'\')" >';
        $result .= '<div class="amount-btn">';
        $result .= '<a class="add" onclick="changeNum(this,1)">';
        $result .= '<i class="iconfont icon-up"></i>';
        $result .= '</a>';
        $result .= '<a class="reduce" onclick="changeNum(this,-1)">';
        $result .= '<i class="iconfont icon-down"></i>';
        $result .= '</a>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</dd>';
        $result .= '</dl>';
        $result .= '<div class="btn-box">';
        $result .= '<a  class="btn"'; 
        $result .= $v['specification'] ? 'onclick="addtocart('.$v['goods_id'].',0)"' : 'onclick="addtocart('.$v['goods_id'].',1)"';
        $result .= '>加入購物車</a>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';                                           
    }

    return $result;
}
