<?php

/**
 * ECSHOP 列出所有分类及品牌
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: catalog.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

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
    $cat_id = 0;
}

if (!$smarty->is_cached('catalog.dwt'))
{
    $online_sale = isset($_REQUEST['online'])  ? intval($_REQUEST['online'])  : 0;
    $smarty->assign('online_sale',        $online_sale);

    $sql="select parent_id from ".$GLOBALS['ecs']->table('category')." where cat_id = ".$cat_id;
    $parent_id= $GLOBALS['db']->getOne($sql);

    /* 取出所有分类 */
    $cat_list = cat_list($cat_id, 0, false);

    if(!empty($online_sale)){
        $children_cat = get_children($cat_id);
     
        $cat_list1 = $cat_list;
        foreach($cat_list1 as $k=>$v){
            $sql = "select count(*) from ".$GLOBALS['ecs']->table('goods')." as g where (g.is_on_sale=0 or g.is_delete=1) and $children_cat and g.online_sale like '%".$_SESSION['area_rate_id']."%'";
            $g_num = $GLOBALS['db']->getOne($sql);
            if($_SESSION['area_rate_id'] == 1){
                if(empty($v['am_online_sale_num'])){
                    unset($cat_list[$k]);
                }elseif($cat_list1[$cat_id]['am_online_sale_num'] <= $g_num &&$cat_id!=0){
                    unset($cat_list[$k]);
                }
            }else{
                if(empty($v['hk_xianxia_sale_num'])){
                    unset($cat_list[$k]);
                }elseif($cat_list1[$cat_id]['hk_xianxia_sale_num'] <= $g_num &&$cat_id!=0){
                    unset($cat_list[$k]);
                }
            }
        }
    }

    $catList = array();

    foreach ($cat_list AS $key=>$val)
    {
        if ($val['is_show'] == 0)
        {
            unset($cat_list[$key]);
        }

        if ($val['parent_id'] == $cat_id && $val['is_show'] == 1)
        {
            $catList[$key] = $val;
        }
    }

    if (!empty($catList))  
    {
        foreach ($catList as $k => $v) 
        {    

            $catList[$k]['children_cat_id'] = '';

            foreach ($cat_list as $key2 => $val2) 
            {
                if ($val2['parent_id'] == $v['cat_id'])
                { 
                    if ($catList[$k]['children_cat_id'] == '') 
                    {
                        $catList[$k]['children_cat_id'] = $val2['cat_id'];
                    } 
                    else 
                    {
                        $catList[$k]['children_cat_id'] .= ',' . $val2['cat_id'];
                    }  
 
                    //拼接下一级url
                    $sql="select cat_name from ".$GLOBALS['ecs']->table('category')." where cat_id = ".$val2['cat_id'];
                    $cat_name= $GLOBALS['db']->getOne($sql);
                    $catList[$k]['children_cat_list'][$key2]['cat_name'] = $cat_name;     

                    $catList[$k]['children_cat_list'][$key2]['cat_url'] = 'category.php?id=' . $val2['cat_id'];                            
                }     
            }
            
            //最后节点
            if ($catList[$k]['children_cat_id'] == '')
            {
                $catList[$k]['children_cat_id'] = $v['cat_id'];
            }
            
            //不等于0的url为category开头
            if($parent_id == 0)
            {
                $catList[$k]['url'] = 'catalog.php?id=' . $v['cat_id']; 
            }
        }
    }

    if (!empty($catList))  
    {	
        foreach ($catList as $k3 => $v3) {
            $sql = "SELECT cat_id FROM ".$ecs->table('category')." WHERE parent_id in (" . $v3['children_cat_id'].")";
            $list_parent = $db->getAll($sql);
            $string_cat = $v3['children_cat_id'].','.$v3['cat_id'];
            $string_t = '';
            foreach ($list_parent as $key=>$value)
            {
            	$string_t = $string_t.','.$value['cat_id'];
            }
            $string_cat = $string_cat.$string_t;
            $goods = array();
            $cat_test = get_child_tree_nav($v3['children_cat_id']);

            $sql = "SELECT * FROM " . $ecs->table('goods'). " where cat_id in (" . $string_cat.") AND is_on_sale = 1 and is_delete = 0 ";

            if(!empty($online_sale)){
                $sql .= "AND online_sale like '%".$_SESSION['area_rate_id']."%'";
            }else{
                $sql .= " and  is_new=1";
            }
            if($_SESSION['area_rate_id']>0){
            	$sql .= " and area_shop_price like '%".$_SESSION['area_rate_id']."%' "; //显示该地区价格
            }
            $sql .= " ORDER BY RAND() LIMIT 6";
			
            
            $result = $GLOBALS['db']->getAll($sql);
              
            $dl_pd = 0;

            if($_SESSION['user_id'])
            {
                $sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
                $dl_pd = $GLOBALS['db']->getOne($sql);
            }

            foreach ($result AS $idx => $row)
            {
                $dl_goods = $row['dl_goods'];
                $goods[$idx]['promotion'] = get_promotion_info($row['goods_id']);

                if($_SESSION['area_rate_id'] > 0)
                {
                    if($dl_pd==1&&$row['dl_goods']==1)
                    {
                        $shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,4);//取地区价格
                        $row['shop_price']   = $shop_price_rate;
                        $row['shop_price_formated']   = 'HKD $ '.$shop_price_rate;
                        $market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,4);
                        $row['market_price'] = 'HKD $ '.$market_price_rate;
                    }else 
                    {
                        $shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
                        $row['shop_price']   = $shop_price_rate;
                        $row['shop_price_formated']   = price_format($shop_price_rate);
                        $market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,$_SESSION['area_rate_id']);
                        $row['market_price'] = price_format($market_price_rate);
                    }   
                }else
                {
                    $row['market_price']        = price_format($row['market_price']);
                    $row['shop_price_formated'] = price_format($row['shop_price']);
                }

                $goods[$idx]['shop_price_formated'] = $row['shop_price_formated'];


                /*if($dl_pd==1&&$goods['dl_goods']==1)
                {
                    $goods[$idx]['min_price'] = 'HKD $ '.$a[$pos];
                }else
                {
                    $goods[$idx]['min_price'] = price_format($a[$pos]);
                }*/
                $resss = get_goods_price_small($row['goods_id']);
                if(empty($resss)){
                    $goods[$idx]['min_price'] = 0;
                }else{
                	if($dl_pd==1&&$row['dl_goods']==1)
                	{
                		$goods[$idx]['min_price'] = 'HKD $ '.$resss;
                	}else 
                	{
                		$goods[$idx]['min_price'] = price_format($resss);
                	}
                }

                $goods[$idx]['rank_prices'] = get_user_rank_prices($row['goods_id'], $row['shop_price']);    // 会员等级价格
                
                $goods[$idx]['id']           = $row['goods_id'];
                $goods[$idx]['name']         = $row['goods_name'];
                $goods[$idx]['brief']        = $row['goods_brief'];
                $goods[$idx]['brand_name']   = isset($goods_data['brand'][$row['goods_id']]) ? $goods_data['brand'][$row['goods_id']] : '';
                         
                $goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
                $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
            }
            
            
           

            $goods_hot = array();
            //线上专卖热销可能设置没那么多。不如直接热销，新品，精品
            $sql = "SELECT * FROM " . $ecs->table('goods'). " where cat_id in (" . $string_cat.") AND is_on_sale = 1 and goods_img<>'' and   (is_hot=1 or is_best=1 ) and is_delete = 0 ";
            if(!empty($online_sale)){
                $sql .= "AND online_sale like '%".$_SESSION['area_rate_id']."%'";
            }
            if($_SESSION['area_rate_id']>0){
            	$sql .= " and area_shop_price like '%".$_SESSION['area_rate_id']."%' "; //显示该地区价格
            }
            $sql .= " ORDER BY  rand() LIMIT 3";

            $result1 = $GLOBALS['db']->getAll($sql);
            
            foreach ($result1 AS $idx => $row)
            {
                $dl_goods = $row['dl_goods'];

                $goods_hot[$idx]['promotion'] = get_promotion_info($row['goods_id']);

                if($_SESSION['area_rate_id'] > 0)
                {
                    if($dl_pd==1&&$row['dl_goods']==1)
                    {
                        $shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,4);//取地区价格
                        $row['shop_price']   = $shop_price_rate;
                        $row['shop_price_formated']   = 'HKD $ '.$shop_price_rate;
                        $market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,4);
                        $row['market_price'] = 'HKD $ '.$market_price_rate;
                    }else 
                    {
                        $shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区价格
                        $row['shop_price']   = $shop_price_rate;
                        $row['shop_price_formated']   = price_format($shop_price_rate);
                        $market_price_rate = get_price_area($row['goods_id'],0,'market_price',0,0,$_SESSION['area_rate_id']);
                        $row['market_price'] = price_format($market_price_rate);
                    }   
                }else
                {
                    $row['market_price']        = price_format($row['market_price']);
                    $row['shop_price_formated'] = price_format($row['shop_price']);
                }

                $goods_hot[$idx]['shop_price_formated'] = $row['shop_price_formated'];



                $resssa = get_goods_price_small($row['goods_id']);
                if(empty($resssa)){
                    $goods_hot[$idx]['min_price'] = 0;
                }else{
                	if($dl_pd==1&&$row['dl_goods']==1)
                    {
                    	$goods_hot[$idx]['min_price'] = 'HKD $ '.$resssa;
                    }else 
                    {
                    	$goods_hot[$idx]['min_price'] = price_format($resssa);
                    }
                    
                }

                $goods_hot[$idx]['rank_prices'] = get_user_rank_prices($row['goods_id'], $row['shop_price']);    // 会员等级价格

            	$goods_hot[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_img'], true);
                if(strstr ( $goods_hot[$idx]['thumb'], 'http://' ))
                {
                    
                }else 
                {
                    $goods_hot[$idx]['thumb'] = './'.$goods_hot[$idx]['thumb'];
                }
                $goods_hot[$idx]['goods_name'] = $row['goods_name'];
            	$goods_hot[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
            	$goods_hot[$idx]['goods_id'] = $row['goods_id'];
            }
           
            if (!empty($goods)||!empty($goods_hot))
            {
                $catList[$k3]['goodLists'] = $goods;
                $catList[$k3]['goodListshot'] = $goods_hot;
                //var_dump($goods_hot);
            }
            else
            {
                unset($catList[$k3]);
            }
           //var_dump($catList);
        }
    }

    if (empty($catList))
    {
        if(empty($online_sale)){
            $ur = "./category.php?id=$cat_id";
        }else{
            $ur = "./category.php?id=$cat_id&online=1";
        }

        ecs_header("Location: $ur");

        exit;
    }
    else
    {
        $smarty->assign('catList',   $catList); 
    }

    $sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";
    
    $ad_hd_ban = $db->getRow($sql);

   //$s =  get_recommend_goods();
    
    if($ad_hd_ban)
    {
    	$ad_hd_ban['ad_link'] = "affiche.php?ad_id=".$ad_hd_ban['ad_id']."&amp;uri=" .urlencode($ad_hd_ban["ad_link"]);
    	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
    }

    $smarty->assign('ad_hd_ban', $ad_hd_ban);//广告
    assign_template();
    assign_dynamic('catalog');
    $position = assign_ur_here(0, $_LANG['catalog']);
    $smarty->assign('page_title', $position['title']);   // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']); // 当前位置

    $smarty->assign('helps',      get_shop_help()); // 网店帮助
    //$smarty->assign('cat_list',   $cat_list);       // 分类列表
    $smarty->assign('brand_list', get_brands());    // 所以品牌赋值
    $smarty->assign('promotion_info', get_promotion_info());
}

$smarty->display('catalog.dwt');

/**
 * 计算指定分类的商品数量
 *
 * @access public
 * @param   integer     $cat_id
 *
 * @return void
 */
function calculate_goods_num($cat_list, $cat_id)
{
    $goods_num = 0;

    foreach ($cat_list AS $cat)
    {
        if ($cat['parent_id'] == $cat_id && !empty($cat['goods_num']))
        {
            $goods_num += $cat['goods_num'];
        }
    }

    return $goods_num;
}

?>