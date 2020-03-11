<?php

/**
 * ECSHOP 团购商品前台文件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: group_buy.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
$smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
$sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";

$ad_hd_ban = $db->getRow($sql);

if($ad_hd_ban)
{
	$ad_hd_ban['ad_link'] = "affiche.php?ad_id=".$ad_hd_ban['ad_id']."&amp;uri=" .urlencode($ad_hd_ban["ad_link"]);
	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
}
$smarty->assign('ad_hd_ban', $ad_hd_ban);
/*------------------------------------------------------ */
//-- 团购商品 --> 团购活动商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{


    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('volume.dwt', $cache_id))
    {
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
    	$smarty->assign('bonus_left',    1); // 左：現金券圖lbi顯示
    	//$smarty->assign('search_type',   1); // 左：搜索lbi顯示
    	$smarty->assign('cat_left',      1); // 左：分類lbi顯示
    	$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
    	$smarty->assign('bonus_img',       get_bonus_img());       // 現金券圖片
    	
    	
    	$time = gmtime();
    	$order_type = $GLOBALS['_CFG']['recommend_order'];
    	$where = '';
    	if($_SESSION['area_rate_id']>0){
    		$where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' "; //显示该地区价格
    	}
    	/* 取得促销lbi的数量限制 */
    	$num = get_library_number("recommend_promotion");
    	$sql = 'SELECT count(g.goods_id)' .
    			'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
    			'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
    			"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
    			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
    			'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .$where.
    			" AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";
    	$sql .= $order_type == 0 ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY rnd ';
    	
    	$count = $db->getOne($sql);

		
		$size = 0;
		$sizebig = 0;
		if($count>0)
		{
			/* 取得每页记录数 */
			$size = isset($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 1;
			$size = 12;
			/* 计算总页数 */
			$page_count = ceil($count / $size);

			/* 取得当前页 */
			$page = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
			$page = $page > $page_count ? $page_count : $page;

			/* 缓存id：语言 - 每页记录数 - 当前页 */
			$cache_id = $_CFG['lang'] . '-' . $size . '-' . $page;
			$cache_id = sprintf('%X', crc32($cache_id));
			$sizebig = ($page - 1) * $size;
		}

		$goods_list = get_promote_goods1(" limit $sizebig,$size");

		$pager = get_pager('promote.php', array('act' => 'list'), $count, $page, $size);

		$smarty->assign('pager', $pager);
		if(count($goods_list)%2==1)
		{
			$pan = 1;
		}else
		{
			$pan = 0;
		}
		$smarty->assign('pan', $pan);
        $smarty->assign('page', 1);
    	 
    	$smarty->assign('goods_list',$goods_list); 
        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('categories', get_categories_tree()); // 分类树
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        //$smarty->assign('top_goods',  get_top10());           // 销售排行
        $smarty->assign('promotion_info', get_promotion_info());
        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typegroup_buy.xml" : 'feed.php?type=group_buy'); // RSS URL

        assign_dynamic('promote');
    }

    /* 显示模板 */
    $smarty->display('promote.dwt', $cache_id);
}
elseif ($_REQUEST['act'] == 'page_list')
{
    include('includes/cls_json.php');
    
    $json   = new JSON;
    $jsonRes    = array();

    $time = gmtime();
    $order_type = $GLOBALS['_CFG']['recommend_order'];
    $where = '';
    if($_SESSION['area_rate_id']>0){
        $where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' "; //显示该地区价格
    }
    /* 取得促销lbi的数量限制 */
    $num = get_library_number("recommend_promotion");
    $sql = 'SELECT count(g.goods_id)' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .$where.
            " AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";
    $sql .= $order_type == 0 ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY rnd ';
    
    $count = $db->getOne($sql);

    
    $size = 0;
    $sizebig = 0;
    if($count>0)
    {
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) + 1 : 1;
        $size = isset($_REQUEST['size']) ? intval($_REQUEST['size']) : 12;
        /* 计算总页数 */
        $page_count = ceil($count / $size);
    
        if ($page > $page_count)
        {   
           $jsonRes['err_msg'] = 1;
           die($json->encode($jsonRes));
        }
        $sizebig = ($page - 1) * $size;
    }
    else{
        $jsonRes['err_msg'] = 1;
        die($json->encode($jsonRes));
    } 

    $goods_list = get_promote_goods1(" limit $sizebig,$size");

    if (!empty($goods_list))
    {
        $jsonRes['page'] = $page;
         $jsonRes['aa'] = 3;
        $jsonRes['err_msg'] = 0;
        $jsonRes['result'] = appendPageProt($goods_list);
    } 
    else 
    { 
        $jsonRes['err_msg'] = 1;
    }

    die($json->encode($jsonRes)); 
}

function appendPageProt($res){
    $result = '';

    foreach ($res as $spec_key => $spec) {
        $result .= '<div class="wrap">';
        
        $result .= '<div class="item';
      if($spec['online_sale_z'] == 1)
      {
      	$result .=' online ';
      }
        $result .=' ">';
        $result .= '<div class="pic-box">';
        $result .= '<a class="pic">';
        $result .= '<img src="'.$spec['goods_img'].'" alt="">';
        $result .= '</a>';
        $result .= '</div>';
        $result .= '<div class="items-info">';
        $result .= '<p><a class="name">'.$spec['goods_name'].'</a></p>';
        $result .= '<del>'.$spec['shop_price'].'</del>';
        foreach ($spec['user_rank'] as $user_rank) {
            $result .= '<div class="normal-text">'.$user_rank['rank_name'].':<span class="main-color">'.$user_rank['price'].'</span></div>';
        }
        $result .='<p class="price-highlight"><span><b>'.$spec['promote_price'].'</b></span></p>';
        $result .='<div class="normal-text">開始時間：'.$spec['gmt_start_time'].'  </div>';
        $result .='<div class="normal-text">結束時間：'.$spec['gmt_end_time'].'  </div>';    
        $result .='<div class="btn-box">'; 
        $result .='<a href="goods.php?id='.$spec['id'].'" class="btn">立即搶購</a>';               
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
    }

    return $result;
}
?>