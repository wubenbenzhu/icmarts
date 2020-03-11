<?php

/**
 * ECSHOP 品牌列表
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: brand.php 17217 2011-01-19 06:29:08Z liubo $
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

/* 获得请求的分类 ID */
if (!empty($_REQUEST['id']))
{
    $brand_id = intval($_REQUEST['id']);
}
if (!empty($_REQUEST['brand']))
{
    $brand_id = intval($_REQUEST['brand']);
}
if (empty($brand_id))
{
    /* 缓存编号 */
    $cache_id = sprintf('%X', crc32($_CFG['lang']));
    if (!$smarty->is_cached('brand_list.dwt', $cache_id))
    {
        assign_template();
        $position = assign_ur_here('', $_LANG['all_brand']);
        $smarty->assign('page_title',      $position['title']);    // 页面标题
        $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

        $smarty->assign('categories',      get_categories_tree()); // 分类树
        $smarty->assign('helps',           get_shop_help());       // 网店帮助
        $smarty->assign('top_goods',       get_top10());           // 销售排行
        $smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
        
        $smarty->assign('brand_list', get_brands());
    }
    $smarty->display('brand_list.dwt', $cache_id);
    exit();
}

/* 初始化分页信息 */
$page = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
$size = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
$cate = !empty($_REQUEST['cat'])   && intval($_REQUEST['cat'])   > 0 ? intval($_REQUEST['cat'])   : 0;

/* 排序、显示方式以及类型 */
$default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
$default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
$default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                              ? trim($_REQUEST['order']) : $default_sort_order_method;
$display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
$display  = in_array($display, array('list', 'grid', 'text')) ? $display : 'text';
setcookie('ECS[display]', $display, gmtime() + 86400 * 7);

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

/* 页面的缓存ID */
$cache_id = sprintf('%X', crc32($brand_id . '-' . $display . '-' . $sort . '-' . $order . '-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' . $_CFG['lang'] . '-' . $cate));

if (!$smarty->is_cached('brand.dwt', $cache_id))
{
	
	
	
	
    $brand_info = get_brand_info($brand_id);

    if (empty($brand_info))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    $sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 3 and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";
    $ad_hd = $db->getAll($sql);
    $ad_hd_num = count($ad_hd);
   
    $smarty->assign('ad_hd_num', $ad_hd_num);
    foreach ($ad_hd as $key=>$value)
    {
    	$ad_hd[$key]['ad_code'] = DATA_DIR . "/afficheimg/$value[ad_code]";
    }
    // $ad_hd[ad_code] = DATA_DIR . "/afficheimg/$ad_hd[ad_code]";
    $smarty->assign('ad_hd', $ad_hd);
   
    
    $smarty->assign('data_dir',    DATA_DIR);
    $smarty->assign('keywords',    htmlspecialchars($brand_info['brand_desc']));
    $smarty->assign('description', htmlspecialchars($brand_info['brand_desc']));
    $smarty->assign('brand_list', get_brands());
    $smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
   
   
    /* 赋值固定内容 */
    assign_template();
    $position = assign_ur_here($cate, $brand_info['brand_name']);
    $smarty->assign('page_title',     $position['title']);   // 页面标题
    $smarty->assign('ur_here',        $position['ur_here']); // 当前位置
    $smarty->assign('brand_id',       $brand_id);
    $smarty->assign('category',       $cate);
    $smarty->assign('cat_left',      1); // 左：分類lbi顯示
    $smarty->assign('categories',     get_categories_tree());        // 分类树
    $smarty->assign('helps',          get_shop_help());              // 网店帮助
    $smarty->assign('top_goods',      get_top10());                  // 销售排行
    $smarty->assign('show_marketprice', $_CFG['show_marketprice']);
    $smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
    $smarty->assign('brand_cat_list', brand_related_cat($brand_id)); // 相关分类
    $smarty->assign('feed_url',       ($_CFG['rewrite'] == 1) ? "feed-b$brand_id.xml" : 'feed.php?brand=' . $brand_id);
    $smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
    /* 调查 */
    $vote = get_vote();
    if (!empty($vote))
    {
        $smarty->assign('vote_id',     $vote['id']);
        $smarty->assign('vote',        $vote['content']);
    }

    $smarty->assign('best_goods',      brand_recommend_goods('best', $brand_id, $cate));
    $smarty->assign('promotion_goods', brand_recommend_goods('promote', $brand_id, $cate));
    $smarty->assign('brand',           $brand_info);
    $smarty->assign('promotion_info', get_promotion_info());

    $count = goods_count_by_brand($brand_id, $cate);

    $goodslist = brand_get_goods($brand_id, $cate, $size, $page, $sort, $order);

    if($display == 'grid')
    {
        if(count($goodslist) % 2 != 0)
        {
            $goodslist[] = array();
        }
    }
    
    $smarty->assign('goods_list',      $goodslist);
    $smarty->assign('script_name', 'brand');

    
    
    assign_pager('brand',              $cate, $count, $size, $sort, $order, $page, '', $brand_id, 0, 0, $display); // 分页
    assign_dynamic('brand'); // 动态内容
}

$smarty->display('brand.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得指定品牌的详细信息
 *
 * @access  private
 * @param   integer $id
 * @return  void
 */
function get_brand_info($id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('brand') . " WHERE brand_id = '$id'";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 获得指定品牌下的推荐和促销商品
 *
 * @access  private
 * @param   string  $type
 * @param   integer $brand
 * @return  array
 */
function brand_recommend_goods($type, $brand, $cat = 0)
{
    static $result = NULL;

    $time = gmtime();
    $where = '';
    if($_SESSION['area_rate_id']>0){
    	$where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' ";
    }
    if ($result === NULL)
    {
        if ($cat > 0)
        {
            $cat_where = "AND " . get_children($cat);
        }
        else
        {
            $cat_where = '';
        }

        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                    "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                    'promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, ' .
                    'b.brand_name, g.is_best, g.is_new, g.is_hot, g.is_promote ' .
                'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp '.
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 $where AND g.is_delete = 0 AND g.brand_id = '$brand' AND " .
                    "(g.is_best = 1 OR (g.is_promote = 1 AND promote_start_date <= '$time' AND ".
                    "promote_end_date >= '$time')) $cat_where" .
               'ORDER BY g.sort_order, g.last_update DESC';
        $result = $GLOBALS['db']->getAll($sql);
    }

    /* 取得每一项的数量限制 */
    $num = 0;
    $type2lib = array('best'=>'recommend_best', 'new'=>'recommend_new', 'hot'=>'recommend_hot', 'promote'=>'recommend_promotion');
    $num = get_library_number($type2lib[$type]);

    $idx = 0;
    $goods = array();
    foreach ($result AS $row)
    {
        if ($idx >= $num)
        {
            break;
        }

        if (($type == 'best' && $row['is_best'] == 1) ||
            ($type == 'promote' && $row['is_promote'] == 1 &&
            $row['promote_start_date'] <= $time && $row['promote_end_date'] >= $time))
        {
            if ($row['promote_price'] > 0)
            {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            }
            else
            {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id']           = $row['goods_id'];
            $goods[$idx]['name']         = $row['goods_name'];
            $goods[$idx]['brief']        = $row['goods_brief'];
            $goods[$idx]['brand_name']   = $row['brand_name'];
            $goods[$idx]['short_style_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                               sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price']   = price_format($row['shop_price']);
            $goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

            $idx++;
        }
    }

    return $goods;
}

/**
 * 获得指定的品牌下的商品总数
 *
 * @access  private
 * @param   integer     $brand_id
 * @param   integer     $cate
 * @return  integer
 */
function goods_count_by_brand($brand_id, $cate = 0)
{
	$where = '';
	if($_SESSION['area_rate_id']>0){
		$where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' ";
	}
    $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('goods'). ' AS g '.
            "WHERE brand_id = '$brand_id' AND g.is_on_sale = 1 $where AND g.is_alone_sale = 1 AND g.is_delete = 0";

    if ($cate > 0)
    {
        $sql .= " AND " . get_children($cate);
    }

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 获得品牌下的商品
 *
 * @access  private
 * @param   integer  $brand_id
 * @return  array
 */
function brand_get_goods($brand_id, $cate, $size, $page, $sort, $order)
{
    $cate_where = ($cate > 0) ? 'AND ' . get_children($cate) : '';
    $where = '';
    if($_SESSION['area_rate_id']>0){
    	$where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' ";
    }
    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
            "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 $where AND g.is_delete = 0 AND g.brand_id = '$brand_id' $cate_where".
            "ORDER BY $sort $order";

    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);
	$now = gmtime();
	
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
		$sql = "SELECT a.act_name FROM " . $GLOBALS['ecs']->table('goods_activity'). " as a,". $GLOBALS['ecs']->table('package_goods')." as p WHERE p.goods_id=".$row['goods_id']." and p.package_id=a.act_id and a.`start_time` <= '$now' AND a.`end_time` >= '$now' AND a.`act_type` = '4' AND a.is_online=1  and a.areaid like '%".$_SESSION['area_rate_id']."%' ORDER BY a.`end_time`";
    	$arr[$row['goods_id']]['package_goods_name'] = $GLOBALS['db']->getOne($sql);
		
    	$dl_pd = 0;
    	if($_SESSION['user_id'])
    	{
    		$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
    		$dl_pd = $GLOBALS['db']->getOne($sql);
    	}
    	$dl_goods = 0;
    	$sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$row['goods_id'];
    	$dl_goods = $GLOBALS['db']->getOne($sql);
    	
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            if($promote_price > 0)
            {
            	//$_SESSION['area_rate_id']
            
            	if($_SESSION['area_rate_id'] > 0)
            	{
            		if($dl_pd==1&&$dl_goods==1)
            		{
            			$promote_price = get_price_area($row['goods_id'],0,'promote_price',0,0,4);//代理登陆代理商品取香港价格
            		}else 
            		{
            			$promote_price = get_price_area($row['goods_id'],0,'promote_price',0,0,$_SESSION['area_rate_id']);//取地区促销价格
            		}
            	}
            }

        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote_small";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new_small";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best_small";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }

        if ($watermark_img != '')
        {
            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
        }

        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
        }
        $properties = get_goods_properties($row['goods_id']);  // 获得商品的规格和属性
       
       
        $arr[$row['goods_id']]['name']             = $row['goods_name'];
        $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']);
        
        if($_SESSION['area_rate_id'] > 0)
        {
        	if($dl_pd==1&&$dl_goods==1)
        	{
        		$sql = "SELECT price FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$row['goods_id']." and areaid=0 and areaid_rate=4 order by price " ;
        		 
        	}else 
        	{
        		$sql = "SELECT price FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$row['goods_id']." and areaid=0 and areaid_rate=".$_SESSION['area_rate_id']." order by price " ;
        		 
        	}
        	
        	$goods_list_num = $GLOBALS['db']->getOne($sql);
        	if($dl_pd==1&&$dl_goods==1)
        	{
        		$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,4);//代理登陆代理商品取香港价格
        	}else 
        	{
        		$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
        	}
        	$arr[$row['goods_id']]['shop_price_c'] = $shop_price_rate;
        	$min_price = $shop_price_rate;
        	/*if ($shop_price_rate>$goods_list_num&&$goods_list_num!=0) {
        		
        		$shop_price_rate = $goods_list_num;
        		$min_price = $goods_list_num;
        	}*/
        	
        	if($dl_pd==1&&$dl_goods==1)
        	{
        		
        		$arr[$row['goods_id']]['shop_price']   = 'HKD $ '.$shop_price_rate;
        		$market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,4);//代理登陆代理商品取香港价格
        		$arr[$row['goods_id']]['market_price'] = 'HKD $ '.$market_price_rate;
        	}else
        	{
        		//$arr[$row['goods_id']]['shop_price_c'] = $shop_price_rate;
        		$arr[$row['goods_id']]['shop_price']   = price_format($shop_price_rate);
        		$market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,$_SESSION['area_rate_id']);
        		$arr[$row['goods_id']]['market_price'] = price_format($market_price_rate);
        	}
        	
        	
        	$p_type = get_promotion_info($row['goods_id']);
        	if(!empty($p_type)){
        		 
        		if($p_type[0]['act_type']!=0)
        		{
        			$xlh = unserialize($p_type[0]['buy']);
        			$xlh_key = count($xlh);
        			 
        			 
        			foreach ($xlh as $key=>$v)
        			{
        	
					
			
        				if($p_type[0]['act_type']==4||$p_type[0]['act_type']==2)
        				{
        					if($p_type[0]['act_type']==4)
        					{
        						$zk_price = $shop_price_rate*$v/10;
        					}else 
        					{
        						$zk_price = $shop_price_rate*$v/100;
        					}
        					 
        					if($dl_pd==1&&$dl_goods==1)
        					{
        						//$goods[$idx]['promotion_type']['act_name'] = $p_type[0]['act_name']; //参加的活动
        						 
        						$arr[$row['goods_id']]['promotion_type']['act_price'] ='HKD $ '.$zk_price;
        					}else
        					{
        	
        						$arr[$row['goods_id']]['promotion_type']['act_price'] =price_format($zk_price);
        					}
        					 
        					if($min_price>$zk_price)
        					{
        						$min_price = $zk_price;
        					}
        					 
        				}elseif(isset($v['buy'])&&$v['buy']>0&&isset($v[$_SESSION['area_rate_id']])&&$v[$_SESSION['area_rate_id']]>0)
        				{
        					$zk_price = $v[$_SESSION['area_rate_id']]/$v['buy'];
        					//var_dump($_SESSION['area_rate_id']);
        					if($dl_pd==1&&$dl_goods==1)
        					{
        						$arr[$row['goods_id']]['promotion_type']['act_price'] ='HKD $ '.$zk_price;
        					}else
        					{
        						$arr[$row['goods_id']]['promotion_type']['act_price'] =price_format($zk_price);
        					}
        					if($min_price>$zk_price)
        					{
        						$min_price = $zk_price;
        					}
        				}
        				$arr[$row['goods_id']]['promotion_type']['act_name'] = $p_type[0]['act_name']; //参加的活动
        			}
					
					
        			 
        		}else
        		{
        			$arr[$row['goods_id']]['promotion_type'] = $p_type[0]; //参加的活动
        		}
        		 
        	}else{
        		$arr[$row['goods_id']]['promotion_type']='';
        	}
        	
        	
            if($promote_price > 0){
				
            	if($dl_pd==1&&$dl_goods==1)
            	{
            		$promote_price_rate = get_price_area($row['goods_id'],0,'promote_price',0,0,4);//代理登陆代理商品取香港价格
            		$arr[$row['goods_id']]['promote_price']    = 'HKD $ '.$promote_price_rate;
            		$arr[$row['goods_id']]['promote_price_c'] = $promote_price_rate;
            	}else 
            	{
            		$promote_price_rate = get_price_area($row['goods_id'],0,'promote_price',0,0,$_SESSION['area_rate_id']);
            		$arr[$row['goods_id']]['promote_price']    = price_format($promote_price_rate);
            		$arr[$row['goods_id']]['promote_price_c'] = $promote_price_rate;
            	}
            	if($min_price>$promote_price_rate)
            	{
            		$min_price = $promote_price_rate;
            	}
                
            }else{
                $arr[$row['goods_id']]['promote_price']    = '';
            }
        }else
        {
        	$arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        	$arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
            $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
        }
		
		
        $arr[$row['goods_id']]['min_price'] = $min_price;
        if($dl_pd==1&&$dl_goods==1)
        {
        	$arr[$row['goods_id']]['min_price_format'] = 'HKD $ '.$min_price;
        }else
        {
        	$arr[$row['goods_id']]['min_price_format'] = price_format($min_price);
        }
        $now = gmtime();
        if($dl_pd==1&&$dl_goods==1)
        {
        	$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$row['goods_id'].
        	" and p.areaid_rate =4 and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
        	
        }else 
        {
        	$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$row['goods_id'].
        	" and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
        	
        }
        
        $volume_type = $GLOBALS['db']->getRow($sql_v);
        if($volume_type){
            $arr[$row['goods_id']]['volume_type'] = 1;
            $arr[$row['goods_id']]['volume_type_name'] = '滿'.$volume_type['num'].'件';
        }
        if($dl_pd==1&&$dl_goods==1)
        {
        	$sql_g="select sum(p.price) from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods_activity')." as f where p.goods_id = ".$row['goods_id'].
        	" and p.price_type = 'group_price' and p.hd_id = f.act_id and f.act_type = 1 and f.start_time >= ".$now." and f.end_time <= ".$now." and p.areaid_rate =".$_SESSION['area_rate_id'].
        	" and p.areaid = 0 and f.areaid like '%4%'";
        }else 
        {
        	$sql_g="select sum(p.price) from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods_activity')." as f where p.goods_id = ".$row['goods_id'].
        	" and p.price_type = 'group_price' and p.hd_id = f.act_id and f.act_type = 1 and f.start_time >= ".$now." and f.end_time <= ".$now." and p.areaid_rate =".$_SESSION['area_rate_id'].
        	" and p.areaid = 0 and f.areaid like '%".$_SESSION['area_rate_id']."%'";
        }

        

        $group_type = $GLOBALS['db']->getOne($sql_g);

        if($group_type > 0){
            $arr[$row['goods_id']]['group_type'] = 1;
        }
        if($volume_type >0 || $group_type >0 || $promote_price > 0 || $row['is_shipping']>0){
            $arr[$row['goods_id']]['actp_type'] = 1;
        }
        $arr[$row['goods_id']]['is_shipping']      = $row['is_shipping'];
        $arr[$row['goods_id']]['type']             = $row['goods_type'];
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
        $gallery_list = get_goods_gallery($row['goods_id']); // 商品相册
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
        $arr[$row['goods_id']]['pictures']         = $gallery_list_one; // 商品相册
        $arr[$row['goods_id']]['pictures_t']         = $gallery_list_tow; // 商品相册
        $arr[$row['goods_id']]['properties']       = $properties['pro']; // 商品属性
        $arr[$row['goods_id']]['specification']    = $properties['spe']; // 商品规格

		
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
        
        $sql = "select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where areaid in ".$area_value." and goods_id=".$row['goods_id'];
        
        $arr[$row['goods_id']]['number']    = $GLOBALS['db']->getOne($sql);
    }

    return $arr;
}

/**
 * 获得与指定品牌相关的分类
 *
 * @access  public
 * @param   integer $brand
 * @return  array
 */
function brand_related_cat($brand)
{
    $arr[] = array('cat_id' => 0,
                 'cat_name' => $GLOBALS['_LANG']['all_category'],
                 'url'      => build_uri('brand', array('bid' => $brand), $GLOBALS['_LANG']['all_category']));
    $where = '';
    if($_SESSION['area_rate_id']>0){
    	$where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' ";
    }
    $sql = "SELECT c.cat_id, c.cat_name, COUNT(g.goods_id) AS goods_count FROM ".
            $GLOBALS['ecs']->table('category'). " AS c, ".
            $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE g.brand_id = '$brand' $where  AND g.is_delete = 0 AND c.cat_id = g.cat_id ".
            "GROUP BY g.cat_id";
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['url'] = build_uri('brand', array('cid' => $row['cat_id'], 'bid' => $brand), $row['cat_name']);
        $arr[] = $row;
    }

    return $arr;
}

?>