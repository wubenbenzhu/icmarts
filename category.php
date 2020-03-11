<?php

/**
 * ECSHOP 商品分类
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: category.php 17217 2011-01-19 06:29:08Z liubo $
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
if (isset($_REQUEST['id']))
{
    $cat_id = intval($_REQUEST['id']);
}
elseif (isset($_REQUEST['category']))
{
    $cat_id = intval($_REQUEST['category']);
}
else
{
    /* 如果分类ID为0，则返回首页 */
    ecs_header("Location: ./\n");

    exit;
}

/*----------------------------------------------------------*/
/*---------start  ajax分頁*/
/*--start首頁彈窗處理*/
if ($_REQUEST['act'] == 'page_list')
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$res    = array();
    
    $children = get_children($cat_id);
    $page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])+1  : 1;
    $size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
    $brand = isset($_REQUEST['brand']) && intval($_REQUEST['brand']) > 0 ? intval($_REQUEST['brand']) : 0;
    
    $price_max = isset($_REQUEST['price_max']) && intval($_REQUEST['price_max']) > 0 ? intval($_REQUEST['price_max']) : 0;
    $price_min = isset($_REQUEST['price_min']) && intval($_REQUEST['price_min']) > 0 ? intval($_REQUEST['price_min']) : 0;
    $sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
    $order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : $default_sort_order_method; 
    
    $ext = ''; //商品查询条件扩展

    $filter_attr_str = isset($_REQUEST['filter_attr']) ? htmlspecialchars(trim($_REQUEST['filter_attr'])) : '0';

    $filter_attr_str = trim(urldecode($filter_attr_str));
    $filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
    $filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);
    $cat = get_cat_info($cat_id);
    /* 扩展商品查询条件 */
/* 属性筛选 */
    $ext = ''; //商品查询条件扩展
    if ($cat['filter_attr'] > 0)
    {
        $cat_filter_attr = explode(',', $cat['filter_attr']);       //提取出此分类的筛选属性
       
        $all_attr_list = array();

        foreach ($cat_filter_attr AS $key => $value)
        {
            $sql = "SELECT a.attr_name FROM " . $ecs->table('attribute_search') . " AS a, " . $ecs->table('goods_attr_search') . " AS ga, " . $ecs->table('goods') . " AS g WHERE ($children OR " . get_extension_goods($children) . ") AND a.attr_id = ga.attr_id AND g.goods_id = ga.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_id='$value'";
            if($temp_name = $db->getOne($sql))
            {
                $all_attr_list[$key]['filter_attr_name'] = $temp_name;

                $sql = "SELECT a.attr_id, MIN(a.goods_attr_id ) AS goods_id, a.attr_value AS attr_value FROM " . $ecs->table('goods_attr_search') . " AS a, " . $ecs->table('goods') .
                       " AS g" .
                       " WHERE ($children OR " . get_extension_goods($children) . ') AND g.goods_id = a.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
                       " AND a.attr_id='$value' ".
                       " GROUP BY a.attr_value";

                $attr_list = $db->getAll($sql);

                $temp_arrt_url_arr = array();

                for ($i = 0; $i < count($cat_filter_attr); $i++)        //获取当前url中已选择属性的值，并保留在数组中
                {
                    $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                }

                $temp_arrt_url_arr[$key] = 0;                           //“全部”的信息生成
                $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                $all_attr_list[$key]['attr_list'][0]['attr_value'] = $_LANG['all_attribute'];
                $all_attr_list[$key]['attr_list'][0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url), $cat['cat_name']);
                $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;

                foreach ($attr_list as $k => $v)
                {
                    $temp_key = $k + 1;
                    $temp_arrt_url_arr[$key] = $v['goods_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                    $temp_arrt_url = implode('.', $temp_arrt_url_arr);

                    $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];
                    $all_attr_list[$key]['attr_list'][$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url), $cat['cat_name']);

                    if (!empty($filter_attr[$key]) AND $filter_attr[$key] == $v['goods_id'])
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
                    }
                    else
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
                    }
                }

                $all_attr_list[$key]['count'] = count($all_attr_list[$key]['attr_list']);
            }

        }
        
        $smarty->assign('filter_attr_list',  $all_attr_list);
        
        /* 扩展商品查询条件 */
        if (!empty($filter_attr))
        {
            $ext_sql = "SELECT DISTINCT(b.goods_id) FROM " . $ecs->table('goods_attr_search') . " AS a, " . $ecs->table('goods_attr_search') . " AS b " .  "WHERE ";
            $ext_group_goods = array();

            foreach ($filter_attr AS $k => $v)                      // 查出符合所有筛选属性条件的商品id */
            {
                if (is_numeric($v) && $v !=0 &&isset($cat_filter_attr[$k]))
                {
                    $sql = $ext_sql . "b.attr_value = a.attr_value AND b.attr_id = " . $cat_filter_attr[$k] ." AND a.goods_attr_id = " . $v;
                    $ext_group_goods = $db->getColCached($sql);
                    $ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
                }
            }
        }
    }           
   
    
    
    $goodslist = category_get_goods($children, $brand, $price_min, $price_max, $ext, $size, $page, $sort, $order);
    
    foreach ($goodslist as $gls=> $gl) 
    {
        $goodslist[$gls]['pictures'] = get_goods_gallery($gl['goods_id']);   // 商品相册 
    }

    if($display == 'grid')
    {
        if(count($goodslist) % 2 != 0)
        {
            $goodslist[] = array();
        }
    }

    if (!empty($goodslist))
    {
        $res['err_msg'] = 0;
        //$res['result'] = $goodslist;
        $res['result'] = appendPage($goodslist);
    } 
    else 
    {   
        $res['err_msg'] = 1;
        $res['result'] = $_REQUEST;
    }

	die($json->encode($res));
}
/*---------end ajax分頁*/
/*----------------------------------------------------------*/

$sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";

$ad_hd_ban = $db->getRow($sql);

if($ad_hd_ban)
{
	$ad_hd_ban['ad_link'] = "affiche.php?ad_id=".$ad_hd_ban['ad_id']."&amp;uri=" .urlencode($ad_hd_ban["ad_link"]);
	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
}
$smarty->assign('ad_hd_ban', $ad_hd_ban);
/* 初始化分页信息 */
$page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
$size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
$brand = isset($_REQUEST['brand']) && intval($_REQUEST['brand']) > 0 ? intval($_REQUEST['brand']) : 0;
$price_max = isset($_REQUEST['price_max']) && intval($_REQUEST['price_max']) > 0 ? intval($_REQUEST['price_max']) : 0;
$price_min = isset($_REQUEST['price_min']) && intval($_REQUEST['price_min']) > 0 ? intval($_REQUEST['price_min']) : 0;
$filter_attr_str = isset($_REQUEST['filter_attr']) ? htmlspecialchars(trim($_REQUEST['filter_attr'])) : '0';

$filter_attr_str = trim(urldecode($filter_attr_str));
$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);

$tjstring = array();

if($brand > 0)
{
    $brandsql = "SELECT brand_name FROM ".$ecs->table('brand')." WHERE brand_id='".$brand."'";
    $brand_name_s = $db->getOne($brandsql);
    $tjstring[0]['name'] = $brand_name_s;
    $tjstring[0]['url'] = "category.php?id=$cat_id&price_min=0&price_max=0&filter_attr=".$_REQUEST['filter_attr'];
}

$i = 1;
foreach ($filter_attr as $k=>$v)
{
    if($v!=0)
    {
        $attsql = " SELECT attr_value FROM ".$ecs->table('goods_attr_search')." WHERE goods_attr_id='".$v."'";
        $attname = $db->getOne($attsql);

        $attstring = str_replace($v,"0",$_REQUEST['filter_attr']);
        $tjstring[$i]['name'] = $attname;
        $tjstring[$i]['url'] = "category.php?id=$cat_id&brand=$brand&price_min=0&price_max=0&filter_attr=$attstring";
        $i++;
    }
}


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
$cache_id = sprintf('%X', crc32($cat_id . '-' . $display . '-' . $sort  .'-' . $order  .'-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' .
    $_CFG['lang'] .'-'. $brand. '-' . $price_max . '-' .$price_min . '-' . $filter_attr_str));

if (!$smarty->is_cached('category.dwt', $cache_id))
{
    /* 如果页面没有被缓存则重新获取页面的内容 */

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
			$act_l[$v['act_id']] = $v;
			$act_l[$v['act_id']]['is_active']=0;
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
	
	
    $online_sale = isset($_REQUEST['online'])  ? intval($_REQUEST['online'])  : 0;

    $smarty->assign('online_sale',        $online_sale);

    $smarty->assign('tjstring',  $tjstring); //筛选条件字符串页面展示
    $children = get_children($cat_id);
   
    $smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
    
    $cat = get_cat_info($cat_id);   // 获得分类的相关信息

    if (!empty($cat))
    {
        $smarty->assign('keywords',    htmlspecialchars($cat['keywords']));
        $smarty->assign('description', htmlspecialchars($cat['cat_desc']));
        $smarty->assign('cat_style',   htmlspecialchars($cat['style']));
    }
    else
    {
        /* 如果分类不存在则返回首页 */
        ecs_header("Location: ./\n");

        exit;
    }

    /* 赋值固定内容 */
    if ($brand > 0)
    {
        $sql = "SELECT brand_name FROM " .$GLOBALS['ecs']->table('brand'). " WHERE brand_id = '$brand'";
        $brand_name = $db->getOne($sql);
    }
    else
    {
        $brand_name = '';
    }

    /* 获取价格分级 */
    if ($cat['grade'] == 0  && $cat['parent_id'] != 0)
    {
        $cat['grade'] = get_parent_grade($cat_id); //如果当前分类级别为空，取最近的上级分类
    }

    if ($cat['grade'] > 1)
    {
        /* 需要价格分级 */

        /*
            算法思路：
                1、当分级大于1时，进行价格分级
                2、取出该类下商品价格的最大值、最小值
                3、根据商品价格的最大值来计算商品价格的分级数量级：
                        价格范围(不含最大值)    分级数量级
                        0-0.1                   0.001
                        0.1-1                   0.01
                        1-10                    0.1
                        10-100                  1
                        100-1000                10
                        1000-10000              100
                4、计算价格跨度：
                        取整((最大值-最小值) / (价格分级数) / 数量级) * 数量级
                5、根据价格跨度计算价格范围区间
                6、查询数据库

            可能存在问题：
                1、
                由于价格跨度是由最大值、最小值计算出来的
                然后再通过价格跨度来确定显示时的价格范围区间
                所以可能会存在价格分级数量不正确的问题
                该问题没有证明
                2、
                当价格=最大值时，分级会多出来，已被证明存在
        */

        $sql = "SELECT min(g.shop_price) AS min, max(g.shop_price) as max ".
               " FROM " . $ecs->table('goods'). " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  ';
               //获得当前分类下商品价格的最大值、最小值

        $row = $db->getRow($sql);

        // 取得价格分级最小单位级数，比如，千元商品最小以100为级数
        $price_grade = 0.0001;
        for($i=-2; $i<= log10($row['max']); $i++)
        {
            $price_grade *= 10;
        }

        //跨度
        $dx = ceil(($row['max'] - $row['min']) / ($cat['grade']) / $price_grade) * $price_grade;
        if($dx == 0)
        {
            $dx = $price_grade;
        }

        for($i = 1; $row['min'] > $dx * $i; $i ++);

        for($j = 1; $row['min'] > $dx * ($i-1) + $price_grade * $j; $j++);
        $row['min'] = $dx * ($i-1) + $price_grade * ($j - 1);

        for(; $row['max'] >= $dx * $i; $i ++);
        $row['max'] = $dx * ($i) + $price_grade * ($j - 1);

        $sql = "SELECT (FLOOR((g.shop_price - $row[min]) / $dx)) AS sn, COUNT(*) AS goods_num  ".
               " FROM " . $ecs->table('goods') . " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
               " GROUP BY sn ";

        $price_grade = $db->getAll($sql);

        foreach ($price_grade as $key=>$val)
        {
            $temp_key = $key + 1;
            $price_grade[$temp_key]['goods_num'] = $val['goods_num'];
            $price_grade[$temp_key]['start'] = $row['min'] + round($dx * $val['sn']);
            $price_grade[$temp_key]['end'] = $row['min'] + round($dx * ($val['sn'] + 1));
            $price_grade[$temp_key]['price_range'] = $price_grade[$temp_key]['start'] . '&nbsp;-&nbsp;' . $price_grade[$temp_key]['end'];
            $price_grade[$temp_key]['formated_start'] = price_format($price_grade[$temp_key]['start']);
            $price_grade[$temp_key]['formated_end'] = price_format($price_grade[$temp_key]['end']);
            $price_grade[$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_grade[$temp_key]['start'], 'price_max'=> $price_grade[$temp_key]['end'], 'filter_attr'=>$filter_attr_str), $cat['cat_name']);

            /* 判断价格区间是否被选中 */
            if (isset($_REQUEST['price_min']) && $price_grade[$temp_key]['start'] == $price_min && $price_grade[$temp_key]['end'] == $price_max)
            {
                $price_grade[$temp_key]['selected'] = 1;
            }
            else
            {
                $price_grade[$temp_key]['selected'] = 0;
            }
        }

        $price_grade[0]['start'] = 0;
        $price_grade[0]['end'] = 0;
        $price_grade[0]['price_range'] = $_LANG['all_attribute'];
        $price_grade[0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>0, 'price_max'=> 0, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);
        $price_grade[0]['selected'] = empty($price_max) ? 1 : 0;

        $smarty->assign('price_grade',     $price_grade);

    }


    /* 品牌筛选 */

    $sql = "SELECT b.brand_id, b.brand_name, COUNT(*) AS goods_num ".
            "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
                $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN ". $GLOBALS['ecs']->table('goods_cat') . " AS gc ON g.goods_id = gc.goods_id " .
            "WHERE  ($children OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge(array($cat_id), array_keys(cat_list($cat_id, 0, false))))) . ") AND b.is_show = 1 " .
            " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0   and b.brand_id = g.brand_id ".
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";

    
    
    $brands = $GLOBALS['db']->getAll($sql);
    
    foreach ($brands AS $key => $val)
    {
        $temp_key = $key + 1;
        $brands[$temp_key]['brand_name'] = $val['brand_name'];
        $brands[$temp_key]['url'] = build_uri('category', array('cid' => $cat_id, 'bid' => $val['brand_id'], 'price_min'=>$price_min, 'price_max'=> $price_max, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);

        /* 判断品牌是否被选中 */
        if ($brand == $brands[$key]['brand_id'])
        {
            $brands[$temp_key]['selected'] = 1;
        }
        else
        {
            $brands[$temp_key]['selected'] = 0;
        }
    }

    $brands[0]['brand_name'] = $_LANG['all_attribute'];
    $brands[0]['url'] = build_uri('category', array('cid' => $cat_id, 'bid' => 0, 'price_min'=>$price_min, 'price_max'=> $price_max, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);
    $brands[0]['selected'] = empty($brand) ? 1 : 0;

    $smarty->assign('brands', $brands);
    $smarty->assign('brandsCount', count($brands));


    /* 属性筛选 */
    $ext = ''; //商品查询条件扩展
    if ($cat['filter_attr'] > 0)
    {
        $cat_filter_attr = explode(',', $cat['filter_attr']);       //提取出此分类的筛选属性
       
        $all_attr_list = array();

        foreach ($cat_filter_attr AS $key => $value)
        {
            $sql = "SELECT a.attr_name FROM " . $ecs->table('attribute_search') . " AS a, " . $ecs->table('goods_attr_search') . " AS ga, " . $ecs->table('goods') . " AS g WHERE ($children OR " . get_extension_goods($children) . ") AND a.attr_id = ga.attr_id AND g.goods_id = ga.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_id='$value'";
            if($temp_name = $db->getOne($sql))
            {
                $all_attr_list[$key]['filter_attr_name'] = $temp_name;

                $sql = "SELECT a.attr_id, MIN(a.goods_attr_id ) AS goods_id, a.attr_value AS attr_value FROM " . $ecs->table('goods_attr_search') . " AS a, " . $ecs->table('goods') .
                       " AS g" .
                       " WHERE ($children OR " . get_extension_goods($children) . ') AND g.goods_id = a.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
                       " AND a.attr_id='$value' ".
                       " GROUP BY a.attr_value";

                $attr_list = $db->getAll($sql);

                $temp_arrt_url_arr = array();

                for ($i = 0; $i < count($cat_filter_attr); $i++)        //获取当前url中已选择属性的值，并保留在数组中
                {
                    $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                }

                $temp_arrt_url_arr[$key] = 0;                           //“全部”的信息生成
                $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                $all_attr_list[$key]['attr_list'][0]['attr_value'] = $_LANG['all_attribute'];
                $all_attr_list[$key]['attr_list'][0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url), $cat['cat_name']);
                $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;

                foreach ($attr_list as $k => $v)
                {
                    $temp_key = $k + 1;
                    $temp_arrt_url_arr[$key] = $v['goods_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                    $temp_arrt_url = implode('.', $temp_arrt_url_arr);

                    $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];
                    $all_attr_list[$key]['attr_list'][$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url), $cat['cat_name']);

                    if (!empty($filter_attr[$key]) AND $filter_attr[$key] == $v['goods_id'])
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
                    }
                    else
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
                    }
                }

                $all_attr_list[$key]['count'] = count($all_attr_list[$key]['attr_list']);
            }

        }
       
        $smarty->assign('filter_attr_list',  $all_attr_list);
        
        /* 扩展商品查询条件 */
        if (!empty($filter_attr))
        {
            $ext_sql = "SELECT DISTINCT(b.goods_id) FROM " . $ecs->table('goods_attr_search') . " AS a, " . $ecs->table('goods_attr_search') . " AS b " .  "WHERE ";
            $ext_group_goods = array();

            foreach ($filter_attr AS $k => $v)                      // 查出符合所有筛选属性条件的商品id */
            {
                if (is_numeric($v) && $v !=0 &&isset($cat_filter_attr[$k]))
                {
                    $sql = $ext_sql . "b.attr_value = a.attr_value AND b.attr_id = " . $cat_filter_attr[$k] ." AND a.goods_attr_id = " . $v;
                    $ext_group_goods = $db->getColCached($sql);
                    $ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
                }
            }
        }
    }
   
    //ajax 分页数据
    $smarty->assign('page',        $page);    
    $smarty->assign('size',        $size);  
    $smarty->assign('brand',       $brand); 
    $smarty->assign('price_max',   $price_max); 
    $smarty->assign('price_min',   $price_min); 
    $smarty->assign('sort',        $sort); 
    $smarty->assign('order',       $order); 
    $smarty->assign('filter_attr', $filter_attr); 

    assign_template('c', array($cat_id));

    $position = assign_ur_here($cat_id, $brand_name,1);

    $smarty->assign('page_title',       $position['title']);    // 页面标题

preg_match_all('/<a href="category.php(.*?)">/is',$position['ur_here'],$ttt);
if($ttt[1])
{
	foreach ($ttt[1] as $k=>$v)
	{
		
		$c_string = substr($v, 4);
		$smarty->assign('ur_here'.$k,          $c_string);  // 当前位置
		//echo substr($v, 4);
	}
//	var_dump($ttt);
}

    $smarty->assign('ur_here',          $position['ur_here']);  // 当前位置
    $smarty->assign('cat',       $cat); // 分类树
    $smarty->assign('c_name',       $cat['cat_name']); // 分类树
	//$smarty->assign('get_child_tree',       get_child_tree($cat_id)); // 分类树
    
    //$smarty->assign('categories',       get_categories_tree($cat_id)); // 分类树
    
    $smarty->assign('helps',            get_shop_help());              // 网店帮助
   // $smarty->assign('top_goods',        get_top10());                  // 销售排行
    $smarty->assign('show_marketprice', $_CFG['show_marketprice']);
    
    $smarty->assign('category',         $cat_id);
    $smarty->assign('brand_id',         $brand);
    $smarty->assign('price_max',        $price_max);
    $smarty->assign('price_min',        $price_min);
    $smarty->assign('filter_attr',      $filter_attr_str);
    $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-c$cat_id.xml" : 'feed.php?cat=' . $cat_id); // RSS URL

    if ($brand > 0)
    {
        $arr['all'] = array('brand_id'  => 0,
                        'brand_name'    => $GLOBALS['_LANG']['all_goods'],
                        'brand_logo'    => '',
                        'goods_num'     => '',
                        'url'           => build_uri('category', array('cid'=>$cat_id), $cat['cat_name'])
                    );
    }
    else
    {
        $arr = array();
    }

    $brand_list = array_merge($arr, get_brands($cat_id, 'category'));

    $smarty->assign('data_dir',    DATA_DIR);
    $smarty->assign('brand_list',      $brand_list);
    $smarty->assign('promotion_info', get_promotion_info());


    /* 调查 
    $vote = get_vote();
    if (!empty($vote))
    {
        $smarty->assign('vote_id',     $vote['id']);
        $smarty->assign('vote',        $vote['content']);
    }*/

    /*$smarty->assign('best_goods',      get_category_recommend_goods('best', $children, $brand, $price_min, $price_max, $ext));
    $smarty->assign('promotion_goods', get_category_recommend_goods('promote', $children, $brand, $price_min, $price_max, $ext));
    $smarty->assign('hot_goods',       get_category_recommend_goods('hot', $children, $brand, $price_min, $price_max, $ext));*/

    $count = get_cagtegory_goods_count($children, $brand, $price_min, $price_max, $ext,$online_sale);
   
    $max_page = ($count> 0) ? ceil($count / $size) : 1;
    if ($page > $max_page)
    {
        $page = $max_page;
    }
   $smarty->assign('max_page',       $max_page);
    
    $goodslist = category_get_goods($children, $brand, $price_min, $price_max, $ext, $size, $page, $sort, $order,$online_sale);
    
    foreach ($goodslist as $gls=> $gl) 
    {
        $goodslist[$gls]['pictures'] = get_goods_gallery($gl['goods_id']);   // 商品相册 
    }

    if($display == 'grid')
    {
        if(count($goodslist) % 2 != 0)
        {
            $goodslist[] = array();
        }
    }
    $smarty->assign('goods_list',       $goodslist);

    if ($_SESSION['user_id'] >0){
        $db->query('UPDATE ' . $ecs->table('users') . " SET user_search ='$_SERVER[REQUEST_URI]' WHERE user_id = '$_SESSION[user_id]'");
    }
    
    $smarty->assign('category',         $cat_id);
    $smarty->assign('script_name', 'category');

    assign_pager('category',            $cat_id, $count, $size, $sort, $order, $page, '', $brand, $price_min, $price_max, $display, $filter_attr_str); // 分页
    assign_dynamic('category'); // 动态内容
}

$smarty->display('category.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得分类的信息
 *
 * @param   integer $cat_id
 *
 * @return  void
 */
function get_cat_info($cat_id)
{
    return $GLOBALS['db']->getRow('SELECT cat_name, keywords, cat_desc, style, grade, filter_attr, parent_id, cat_logo,banner_img FROM ' . $GLOBALS['ecs']->table('category') .
        " WHERE cat_id = '$cat_id'");
}

/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_get_goods($children, $brand, $min, $max, $ext, $size, $page, $sort, $order,$online_sale=0)
{
    $display = $GLOBALS['display'];
    $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
        "g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';

    if ($brand > 0)
    {
        $where .=  "AND g.brand_id=$brand ";
    }
    if($online_sale == 1){
        $where .=  "AND g.online_sale like '%".$_SESSION['area_rate_id']."%'";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }
    if($_SESSION['area_rate_id']>0){
    	$where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%'"; //显示该地区价格
    }
    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id,g.dl_goods,g.online_sale_z, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.is_shipping, g.shop_price AS org_price, ' .
        "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
        'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
        'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
        "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
        "WHERE $where $ext ORDER BY $sort $order";
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
    	
    	$dl_goods = $row['dl_goods'];
        
            

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
        $arr[$row['goods_id']]['online_sale_z']             = $row['online_sale_z'];
        $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']);
        
        $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        
        if($_SESSION['area_rate_id'] > 0)
        {
        	//$sql = "SELECT price FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$row['goods_id']." and areaid=0 and areaid_rate=".$_SESSION['area_rate_id']." order by price " ;
        	
        	if($dl_pd==1&&$dl_goods==1)
        	{
        		$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,4);//代理登陆代理商品取香港价格
        		$market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,4);//代理登陆代理商品取香港价格
        		$arr[$row['goods_id']]['shop_price_c'] = $shop_price_rate;
        		$arr[$row['goods_id']]['shop_price']   = 'HKD $ '.$shop_price_rate;
        		$arr[$row['goods_id']]['market_price'] = 'HKD $ '.$market_price_rate;
        	}else 
        	{
        		$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
        		$market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,$_SESSION['area_rate_id']);
        		$arr[$row['goods_id']]['shop_price_c'] = $shop_price_rate;
        		$arr[$row['goods_id']]['shop_price']   = price_format($shop_price_rate);
        		$arr[$row['goods_id']]['market_price'] = price_format($market_price_rate);
        	}
        }
        $min_price = get_goods_price_small($row['goods_id']);
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
 * 获得分类下的商品总数
 *
 * @access  public
 * @param   string     $cat_id
 * @return  integer
 */
function get_cagtegory_goods_count($children, $brand = 0, $min = 0, $max = 0, $ext='',$online_sale=0)
{
    $where  = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';
    if($_SESSION['area_rate_id']>0){
    	$where .= " and g.area_shop_price like '%".$_SESSION['area_rate_id']."%'"; //显示该地区价格
    }
    if ($brand > 0)
    {
        $where .=  " AND g.brand_id = $brand ";
    }

    if($online_sale == 1){
        $where .=  "AND g.online_sale like '%".$_SESSION['area_rate_id']."%'";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    /* 返回商品总数 */
    return $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods') . " AS g WHERE $where $ext");
}

/**
 * 取得最近的上级分类的grade值
 *
 * @access  public
 * @param   int     $cat_id    //当前的cat_id
 *
 * @return int
 */
function get_parent_grade($cat_id)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('cat_parent_grade');
        if ($data === false)
        {
            $sql = "SELECT parent_id, cat_id, grade ".
                   " FROM " . $GLOBALS['ecs']->table('category');
            $res = $GLOBALS['db']->getAll($sql);
            write_static_cache('cat_parent_grade', $res);
        }
        else
        {
            $res = $data;
        }
    }

    if (!$res)
    {
        return 0;
    }

    $parent_arr = array();
    $grade_arr = array();

    foreach ($res as $val)
    {
        $parent_arr[$val['cat_id']] = $val['parent_id'];
        $grade_arr[$val['cat_id']] = $val['grade'];
    }

    while ($parent_arr[$cat_id] >0 && $grade_arr[$cat_id] == 0)
    {
        $cat_id = $parent_arr[$cat_id];
    }

    return $grade_arr[$cat_id];

}

//追加分页
function appendPage($res){

    $result = '';
    foreach ($res as $k => $v) {
        $result .= '<div class="box-w33">';
        $result .= '<div class="item';
        if($v['online_sale_z'] == 1)
        {
        	$result .='	online ';
        }
        $result .='	">';
        $result .= '<div class="pic-box">';
        $result .= '<a href="'.$v['url'].'" class="pic">';
        $result .= '<img src="'.$v['pictures'][0]['thumb_url'].'" alt="">';
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
        $result .= '<div class="more-box">';
        $result .= '<div class="ctx-box">';
        $result .= '<div class="row title">';
        $result .= '<a href="'.$v['url'].'">'.$v['goods_name'].'</a>';
        $result .= '</div>';
        $result .= '<div class="row act">';
        $result .= '<a href="'.$v['url'].'">'.$v['goods_brief'].'</a>';
        $result .= '</div>';
        $result .= '<div class="price">';
        if($v['min_price'])
        {
            $result .= '<del>'.$v['shop_price'].'</del>';
            $result .= '<input type="hidden" id="beigoodsprice_'.$v['goods_id'].'" value="'.$v['min_price'].'">';
            $result .= '<b class="price-highlight" id="onebeigoodsprice_'.$v['goods_id'].'">'.$v['min_price_format'].'起</b>';
        }
        else 
        {  
        	$result .= '<del> '.$v['market_price'].' </del>';
           
            
                $result .= '<input type="hidden" id="beigoodsprice_'.$v['goods_id'].'" value="'.$v['shop_price_c']  .'">';
            
            $result .= '<b class="price-highlight" id="onebeigoodsprice_'.$v['goods_id'].'">';
            $result .=  $v['shop_price'];
            $result .= '</b>';
        }
                
        $result .= '</div>';
        $result .= '</div>';
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
        $result .= '<span class="btn"'; 
        $result .= $v['specification'] ? 'onclick="addtocart('.$v['goods_id'].',0)"' : 'onclick="addtocart('.$v['goods_id'].',1)"';
        $result .= '>加入購物車</span>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
    }

    return $result;
}


?>
