<?php

/**
 * ECSHOP 超值礼包列表
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: activity.php 16056 2009-05-21 05:44:14Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
include_once(ROOT_PATH . 'includes/lib_transaction.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/package.php');

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */
$cache_id = sprintf('%X', crc32($_SESSION['user_rank'] . '-' . $_CFG['lang']));
if (!$smarty->is_cached('package.dwt', $cache_id))
{

assign_template();
assign_dynamic('package');
$position = assign_ur_here(0, $_LANG['shopping_package']);
$sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";

$ad_hd_ban = $db->getRow($sql);

if($ad_hd_ban)
{
	$ad_hd_ban['ad_link'] = "affiche.php?ad_id=".$ad_hd_ban['ad_id']."&amp;uri=" .urlencode($ad_hd_ban["ad_link"]);
	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
}

$sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 11 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";

$ad_hd_bans = $db->getRow($sql);

if($ad_hd_bans)
{
	$ad_hd_bans['ad_link'] = "affiche.php?ad_id=".$ad_hd_bans['ad_id']."&amp;uri=" .urlencode($ad_hd_bans["ad_link"]);
	$ad_hd_bans['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_bans['ad_code'];
}
$smarty->assign('ad_hd_bans', $ad_hd_bans);
$smarty->assign('ad_hd_ban', $ad_hd_ban);

$smarty->assign('page_title',       $position['title']);    // 页面标题
$smarty->assign('ur_here',          $position['ur_here']);  // 当前位置
$smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
/* 读出所有礼包信息 */
$smarty->assign('bonus_left',    1); // 左：現金券圖lbi顯示
//$smarty->assign('search_type',   1); // 左：搜索lbi顯示
$smarty->assign('cat_left',      1); // 左：分類lbi顯示
$smarty->assign('bonus_img',       get_bonus_img());       // 現金券圖片
$now = gmtime();

    /* 初始化分页信息 */
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $size = 4;

    $sql = "SELECT count(*) FROM " . $ecs->table('goods_activity'). " WHERE `start_time` <= '$now' AND `end_time` >= '$now' AND is_online=1 AND `act_type` = '4' and areaid like '%".$_SESSION['area_rate_id']."%'";
    $re_count = $GLOBALS['db']->getOne($sql);

    $pager  = get_pager('package.php', '', $re_count, $page,$size);

    $smarty->assign('pager',  $pager);

    $sql = "SELECT * FROM " . $ecs->table('goods_activity'). " WHERE `start_time` <= '$now' AND `end_time` >= '$now' AND `act_type` = '4' AND is_online=1  and areaid like '%".$_SESSION['area_rate_id']."%' ORDER BY `end_time`";
//$res = $db->query($sql);
$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);
$list = array();
while ($row = $db->fetchRow($res))
{
	$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('price_area')." WHERE price_type='package_price' AND hd_id=".$row['act_id']." AND areaid_rate=".$_SESSION['area_rate_id']." and areaid=0  ";
	
	$price_list = $GLOBALS['db']->getRow($sql);
	$row['package_price'] = price_format($price_list['price']);
	$row['price_thumb'] = $price_list['price_thumb'];
    $row['start_time']  = local_date('Y-m-d H:i', $row['start_time']);
    $row['end_time']    = local_date('Y-m-d H:i', $row['end_time']);
    $ext_arr = unserialize($row['ext_info']);
    unset($row['ext_info']);
    if ($ext_arr)
    {
        foreach ($ext_arr as $key=>$val)
        {
            $row[$key] = $val;
        }
    }

    $sql = "SELECT pg.package_id,pg.product_id, pg.goods_id, pg.goods_number, pg.admin_id, ".
           " g.goods_sn, g.goods_name, g.market_price,g.shop_price, g.goods_thumb, ".
           " IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price " .
           " FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg ".
           "   LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g ".
           "   ON g.goods_id = pg.goods_id ".
           " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
           " WHERE pg.package_id = " . $row['act_id']. " ".
           " ORDER BY pg.goods_id";

    $goods_res = $GLOBALS['db']->getAll($sql);

    $subtotal = 0;
    foreach($goods_res as $key => $val)
    {
        $goods_res[$key]['goods_thumb']  = get_image_path($val['goods_id'], $val['goods_thumb'], true);
        $market_price_rate = get_price_area($val['goods_id'],0,'market_price',0,0,$_SESSION['area_rate_id']);
        $goods_res[$key]['market_price'] = price_format($market_price_rate);
        $shop_price_rate = get_price_area($val['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
        $goods_res[$key]['shop_price'] = price_format($shop_price_rate);
        $goods_res[$key]['rank_price']   = price_format($shop_price_rate);
        $properties = get_goods_properties_two($val['goods_id']);  // 获得商品的规格和属性
        if($val['product_id'] > 0)
        {
        	$sql = "SELECT goods_attr FROM ".$GLOBALS['ecs']->table('products')." WHERE product_id=".$val['product_id']." ";
        	$goods_attr = $GLOBALS['db']->getOne($sql);
        	if(!empty($goods_attr))//判断选择货品组合礼包。去掉属性筛选保留货品属性说明
        	{
        		$goods_res[$key]['g_pa'] = 1;
        		$goods_attr = explode('|',$goods_attr);
        		foreach ($properties['spe'] as $k=>$v)
        		{
        			$goods_attr_list = array();
        			foreach ($goods_attr as $gk=>$gv)
        			{
        				$pd = 0;
        				foreach ($v['values'] as $vk=>$vv)
        				{
        					if($gv == $vv['id'])
        					{
        						$goods_attr_list= $properties['spe'][$k]['values'][$vk];
        						$pd = 1;
        						continue;
        					}
        				}
        				if($pd == 1)
        				{
        					continue;
        				}
        			}
        			$properties['spe'][$k]['values'] = array();
        			$properties['spe'][$k]['values'][0] = $goods_attr_list;
        		}
        	}
        }
        $goods_res[$key]['goods_pro'] = $properties['pro'];// 商品属性
        $goods_res[$key]['goods_spe'] = $properties['spe'];// 商品规格
        $goods_res[$key]['goods_imgs_list'] =  array_slice(get_goods_gallery($val['goods_id']),0,12);
        $goods_res[$key]['product_id'] = $val['product_id'];
      
        $subtotal += $shop_price_rate * $val['goods_number'];
    }

    $row['goods_count'] = count($goods_res);
    $row['goods_list']    = $goods_res;
    $row['subtotal']      = price_format($subtotal);
    $row['saving']        = price_format(($subtotal - $row['package_price']));
    $sql = "SELECT price FROM ".$GLOBALS['ecs']->table('price_area')." WHERE price_type='package_price' and hd_id=".$row['act_id']." AND areaid=0 AND areaid_rate=".$_SESSION['area_rate_id'];
	$package_price = $GLOBALS['db']->getOne($sql);
    $row['package_price'] = price_format($package_price);

    $list[] = $row;
}
$time = gmtime();
 $smarty->assign('activity_show',get_activity_show1());//買几送優惠
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
$smarty->assign('list',             $list);

$smarty->assign('helps',            get_shop_help());       // 网店帮助
$smarty->assign('lang',             $_LANG);
$smarty->assign('categories', get_categories_tree()); // 分类树
$smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typepackage.xml" : 'feed.php?type=package'); // RSS URL
}
$smarty->display('package.dwt',$cache_id);

