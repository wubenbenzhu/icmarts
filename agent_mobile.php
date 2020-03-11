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
	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
}
$smarty->assign('ad_hd_ban', $ad_hd_ban);
/*------------------------------------------------------ */
//-- 团购商品 --> 团购活动商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
	

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('agent1.dwt', $cache_id))
    {
    	
    	$sql = "SELECT  brand_id FROM ".$GLOBALS['ecs']->table('goods') ." WHERE dl_goods=1 group by brand_id";
    	$brand_list = $db->getAll($sql);
    	$brands = array();
    	foreach ($brand_list as $key=>$value)
    	{
    		$brands[$key] = $value['brand_id'];
    	}
    	
    
    	$brand_string = implode($brands, ',');
    	$sql = "SELECT * FROM ".$ecs->table('brand')." WHERE brand_id in(".$brand_string.") and is_show=1 and areaid like '%".$_SESSION['area_rate_id']."%'";
    	$brand = $db->getAll($sql);
    	
    	$smarty->assign('dl_brand',$brand);//有代理商品的品牌
    	
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
    			'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .$where." and dl_goods=1";
    			//" AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";
    	$brand_id = $_POST['brand_id'];
    	if(!empty($brand_id))
    	{
    		$sql.= " and g.brand_id=".$brand_id."  ";
    	}else 
    	{
    		$brand_id = 0;
    	}
    	
    	$sql .= ' ORDER BY g.goods_id desc' ;
    	
    	
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
		
        $keyword = $_POST['keywords'];
    	if(!empty($keyword))
    	{
    		
    	}else 
    	{
    		$keyword = '';
    	}
    	
		$goods_list = get_agent_goods1(" limit $sizebig,$size",$brand_id,$keyword);
		
		
		
		$pager = get_pager('agent.php', array('act' => 'list'), $count, $page, $size);

		$smarty->assign('pager', $pager);
		if(count($goods_list)%2==1)
		{
			$pan = 1;
		}else
		{
			$pan = 0;
		}
		$smarty->assign('pan', $pan);
    	$smarty->assign('brand_id',$brand_id);
    	$smarty->assign('keyword',$keyword);
    	
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

        assign_dynamic('agent');
    }

    /* 显示模板 */
    $smarty->display('agentdlnew.dwt', $cache_id);
}
elseif ($_REQUEST['act'] == 'ajax_page')//下滚分页
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$page = $_REQUEST['num'];  //页数
	$brand_id = $_REQUEST['brand_id'];
	$keyword = $_REQUEST['keyword'];
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	$page = ($page-1) * 12;
	
	$goods_list = get_agent_goods1(" limit $page,12",$brand_id,$keyword);
	$mes_string = '';
	foreach ($goods_list as $key=>$value)
	{
		$mes_string.= '<div class="col-md-3 col-xs-4">';
		$mes_string.= '<div class="thumbnail">';
		$mes_string.='<a href="" class="img-wrap"><img src="'.$value['thumb'].'" class="img-responsive"></a>';
		$mes_string.='<div class="caption clearfix"><p class="item-desc">'.$value['name'].'</p>';
		$mes_string.='<div class="item-price-wrap pull-left"><div class="item-price"><strong>'.$value['shop_price'].'</strong></div>';
		$mes_string.='<div class="item-price"><del>'.$value['market_price'].'</del></div>';
		$mes_string.='</div><div class="add-to-cart"><a href="" data-toggle="modal" data-target="#myModal" data-backdrop="true"><i class="glyphicon glyphicon-shopping-cart"></i></a></div>';
		
		
		$mes_string.='</div></div></div>';
		
		
		
	}
	$res['mes_no'] = 1;
	if($mes_string=='')
	{
		$res['mes_no'] = 0;
	}
	//print_r($goods_list);
	$res['message'] = $mes_string;
	die($json->encode($res));
}
elseif ($_REQUEST['act'] == 'goodsinfo')//详情页AJAX处理
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$goods_id = $_REQUEST['id'];
	$goods = get_goods_info($goods_id);
	
	$properties = get_goods_properties_two($goods_id);  // 获得商品的规格和属性
	$mes_string = '';
	$mes_string .= '<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal"><span>&times;</span></button>';
	$mes_string .='<h4 class="modal-title">';
	$mes_string .='<div class="row"><div class="col-xs-3">';
	$mes_string .='<img src="'.$goods['goods_thumb'].'" class="img-responsive"></div>';
	$mes_string .='<div class="col-xs-8"><p>'.$goods['goods_name'].'</p>';
	$mes_string .='<div class="item-price-wrap">';
	$mes_string .='<div class="item-price"><strong>'.$goods['shop_price_formated'].'</strong></div>';
	$mes_string .='<div class="item-price"><del>'.$goods['market_price'].'</del></div>';
	$mes_string .='</div></div></div></h4></div><div class="modal-body" id="suxing">';
	//属性
	$jiumes = '';
	foreach ($properties['spe'] as $key=>$value)
	{
		$jiumes .= '<input type="hidden" name="jiushuxing_'.$goods_id.'"  id="jiushuxing_'.$key.'" value="">';
		$mes_string .= '<div class="sku-bar"><p>'.$value['name'].'</p>';
		$mes_string .='<ul class="sku-ul">';
		foreach ($value['values'] as $v)
		{
			if($v['xianshi'] == 1&&$v['css'] == 1)
			{
				if($v['price']!=0.00&&$v['price']!='0.00'&&$v['price']!='0.000'&&$v['price']&&$v['price']!='0.0'&&$v['price']!='0')
				{
						
					$peiprice = $v['format_price'];
				}else
				{
					$peiprice = 0;
				}
				
				$mes_string .='<li ><span class="current"';
				$mes_string = $mes_string.'onclick="changeP('.$v['id'].','.$key.','.$goods_id.')"';
				$mes_string = $mes_string.' name="sp_url_'.$key.'" id="url_'.$v['id'].'" ';
				
				$mes_string = $mes_string.'> ';
				$mes_string .=$v['label'].'</span></li>';
				$mes_string = $mes_string.'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"';
				$mes_string = $mes_string.'checked >';
				$mes_string = $mes_string.'<input style="display:none" id="attr_'.$goods_id.'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"';
				$mes_string = $mes_string.'checked >';
			}else if($v['xianshi'] == 1)
			{
				if($v['price']!=0.00&&$v['price']!='0.00'&&$v['price']!='0.000'&&$v['price']&&$v['price']!='0.0'&&$v['price']!='0')
				{
						
					$peiprice = $v['format_price'];
				}else
				{
					$peiprice = 0;
				}
				
				$mes_string .='<li><span class="default"';
				$mes_string = $mes_string.'onclick="changeP('.$v['id'].','.$key.','.$goods_id.')"';
				$mes_string = $mes_string.' name="sp_url_'.$key.'" id="url_'.$v['id'].'" ';
			
				$mes_string = $mes_string.'> ';
				$mes_string .=$v['label'].'</span></li>';
				$mes_string = $mes_string.'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'">';
				
			}else 
			{
				$mes_string .='<li class="disabled">'.$v['label'].'</li>';
			}
		}
		$mes_string .='</ul></div>';
	}
	$mes_string .= '</div>';
	$mes_string .='<div class="modal-footer">';
	$mes_string .='<div class="input-group" style="margin-bottom:20px;">';
	$mes_string .='<span class="input-group-addon">數量</span>';
	$mes_string .='<input type="number" class="form-control"  min="1" value="1" id="number_'.$goods_id.'"></div>';
	if($properties['spe'])
	{
		$mes_string .='<div class="row"><div class="col-xs-6"><button class="btn btn-warning btn-block btn-lg" data-dismiss="modal" onclick="addtocart('.$goods_id.',0)">加入購物車</button></div>';
		$mes_string .='<div class="col-xs-6"><button class="btn btn-danger btn-block  btn-lg">立即結賬</button></div>';
	}else 
	{
		$mes_string .='<div class="row"><div class="col-xs-6"><button class="btn btn-warning btn-block btn-lg" data-dismiss="modal" onclick="addtocart('.$goods_id.',1)" >加入購物車</button></div>';
		$mes_string .='<div class="col-xs-6"><button class="btn btn-danger btn-block  btn-lg">立即結賬</button></div>';
	}
	
	$mes_string .='</div></div></div></div></div>';
	//属性
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	$res['message'] = $mes_string;
	$res['jiumes'] = $jiumes;
	die($json->encode($res));
}	
?>