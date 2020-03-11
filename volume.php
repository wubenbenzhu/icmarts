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
    $sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 11 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";
    
    $ad_hd_bans = $db->getRow($sql);
    
    if($ad_hd_bans)
    {
    	$ad_hd_bans['ad_link'] = "affiche.php?ad_id=".$ad_hd_bans['ad_id']."&amp;uri=" .urlencode($ad_hd_bans["ad_link"]);
    	$ad_hd_bans['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_bans['ad_code'];
    }
    $smarty->assign('ad_hd_bans', $ad_hd_bans);
    	$smarty->assign('bonus_left',    1); // 左：現金券圖lbi顯示
    	//$smarty->assign('search_type',   1); // 左：搜索lbi顯示
    	$smarty->assign('cat_left',      1); // 左：分類lbi顯示
    	$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
    	$smarty->assign('bonus_img',       get_bonus_img());       // 現金券圖片
		$time = gmtime();
    	$sql = "SELECT g.goods_id FROM ".$ecs->table('price_area')." as p, ".$ecs->table('goods')." as g  WHERE  g.is_delete!=1 and g.is_on_sale=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=".$_SESSION['area_rate_id']." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' group by g.goods_id order by p.id desc ";    	    	
    	$goods_list_numt = $db->getAll($sql);
    	$count = count($goods_list_numt);
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
    	$sql = "SELECT g.goods_id,p.price,p.num,p.price_type,p.product_sn FROM ".$ecs->table('price_area')." as p, ".$ecs->table('goods')." as g  WHERE g.is_delete!=1  and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=".$_SESSION['area_rate_id']." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%'   ";    	
    	$goods_list_num = $db->getAll($sql);
    	$sql = "SELECT g.goods_id,g.goods_name,g.online_sale_z,g.shop_price,g.goods_thumb FROM ".$ecs->table('goods')." as g  WHERE g.is_delete!=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and g.is_on_sale=1 
    	"." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' "." group by g.goods_id  order by g.goods_id desc limit $sizebig,$size ";    
    	$goods_list = $db->getAll($sql);
    	foreach ($goods_list as $k=>$v)
    	{
    		$dl_pd = 0;
    		if($_SESSION['user_id'])
    		{
    			$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
    			$dl_pd = $GLOBALS['db']->getOne($sql);
    		}
    		$dl_goods = 0;
    		$sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$v['goods_id'];
    		$dl_goods = $GLOBALS['db']->getOne($sql);
    		if($dl_pd==1&&$dl_goods==1)
    		{
    			$sql = "SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$v['goods_id']." AND areaid=0 AND areaid_rate=4 ";   			
    		}else 
    		{
    			$sql = "SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$v['goods_id']." AND areaid=0 AND areaid_rate=".$_SESSION['area_rate_id'];   			
    		}
    		$shop_price =$db->getOne($sql);
    		if($dl_pd==1&&$dl_goods==1)
    		{
    			$goods_list[$k]['shop_price'] = 'HKD $'.$shop_price;
    		}else 
    		{
    			$goods_list[$k]['shop_price'] = price_format($shop_price);
    		}
    		$goods_list[$k]['online_sale_z'] = $v['online_sale_z'];
    		$volume_list = array();
    		$i=0;
    		foreach ($goods_list_num as $key=>$value)
    		{
    			if($v['goods_id'] == $value['goods_id'])
    			{
    				if($value['price_type']=="sn_volume_price")
    				{
    					$sqlsn = "select goods_attr from ".$ecs->table('products')." WHERE product_sn='".$value['product_sn']."' AND areaid=0";
    					$goods_atr = $db->getOne($sqlsn);
    					$goods_atr = str_replace('|',',', $goods_atr);
    					$sqlattr = "select attr_value FROM ".$ecs->table('goods_attr')." WHERE goods_attr_id in(".$goods_atr.")";
    					$good_atrname = $db->getAll($sqlattr);
    					$goods_attrname= '';
    					foreach ($good_atrname as $ak=>$av)
    					{
    						$goods_attrname = $goods_attrname.$av['attr_value']."|";
    					}
    					$goods_attrname = substr($goods_attrname,0,strlen($goods_attrname)-1);
    					$volume_list[$i]['attr'] = $goods_attrname;
    				}
    				$volume_list[$i]['num'] = $value['num'];
    				if($dl_pd==1&&$dl_goods==1)
    				{
    					$volume_list[$i]['price'] = 'HKD $'.$value['price'];
    				}else 
    				{
    					$volume_list[$i]['price'] = price_format($value['price']);
    				}
    				
    				$volume_list[$i]['ze'] = ceil($value['price']/$shop_price)*10;
    				$i++;
    			}	
    		}
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
    		$gallery_list = get_goods_gallery($v['goods_id']); // 商品相册
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
    		$goods_list[$k]['pictures']         = $gallery_list_one; // 商品相册
    		$goods_list[$k]['pictures_t']         = $gallery_list_tow; // 商品相册
    		$properties = get_goods_properties($v['goods_id']);  // 获得商品的规格和属性
    		$goods_list[$k]['properties']       = $properties['pro']; // 商品属性
    		$goods_list[$k]['specification']    = $properties['spe']; // 商品规格
    		$sql = "select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where areaid in ".$area_value;
    		$goods_list[$k]['number']    = $GLOBALS['db']->getOne($sql);
    		$goods_list[$k]['volume_list'] = $volume_list;
    	 }
    	 
    	 $pager = get_pager('volume.php', array('act' => 'list'), $count, $page, $size);
    	 $smarty->assign('pager', $pager);
         $smarty->assign('page', $page);
    	 if(count($goods_list)%2==1)
    	 {
    	 	$pan = 1;
    	 }else
    	 {
    	 	$pan = 0;
    	 }
    	 $smarty->assign('pan', $pan);
    	$smarty->assign('goods_list',$goods_list); 
    	
        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('categories', get_categories_tree()); // 分类树
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
       // $smarty->assign('top_goods',  get_top10());           // 销售排行
        $smarty->assign('promotion_info', get_promotion_info());
        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typegroup_buy.xml" : 'feed.php?type=group_buy'); // RSS URL

        assign_dynamic('volume');
    }

    /* 显示模板 */
    $smarty->display('volume.dwt', $cache_id);
}
elseif ($_REQUEST['act'] == 'page_list') 
{
    include('includes/cls_json.php');
    
    $json   = new JSON;
    $jsonRes    = array();

    $time = gmtime();
    $sql = "SELECT g.goods_id FROM ".$ecs->table('price_area')." as p, ".$ecs->table('goods')." as g  WHERE  g.is_delete!=1 and g.is_on_sale=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=".$_SESSION['area_rate_id']." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' group by g.goods_id order by p.id desc ";              
    $goods_list_numt = $db->getAll($sql);
    $count = count($goods_list_numt);
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
    $sql = "SELECT g.goods_id,p.price,p.num,p.price_type,p.product_sn FROM ".$ecs->table('price_area')." as p, ".$ecs->table('goods')." as g  WHERE g.is_delete!=1  and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=".$_SESSION['area_rate_id']." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%'   ";      
    $goods_list_num = $db->getAll($sql);
    $sql = "SELECT g.goods_id,g.goods_name,g.shop_price,g.online_sale_z,g.goods_thumb FROM ".$ecs->table('goods')
    ." as g  WHERE g.is_delete!=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and g.is_on_sale=1 
    "." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' "." group by g.goods_id  order by g.goods_id desc limit $sizebig,$size ";    
    $goods_list = $db->getAll($sql);
    foreach ($goods_list as $k=>$v)
    {
        $dl_pd = 0;
        if($_SESSION['user_id'])
        {
            $sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
            $dl_pd = $GLOBALS['db']->getOne($sql);
        }
        $dl_goods = 0;
        $sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$v['goods_id'];
        $dl_goods = $GLOBALS['db']->getOne($sql);
        if($dl_pd==1&&$dl_goods==1)
        {
            $sql = "SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$v['goods_id']." AND areaid=0 AND areaid_rate=4 ";             
        }else 
        {
            $sql = "SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$v['goods_id']." AND areaid=0 AND areaid_rate=".$_SESSION['area_rate_id'];             
        }
        $shop_price =$db->getOne($sql);
        if($dl_pd==1&&$dl_goods==1)
        {
            $goods_list[$k]['shop_price'] = 'HKD $'.$shop_price;
        }else 
        {
            $goods_list[$k]['shop_price'] = price_format($shop_price);
        }
        $goods_list[$k]['online_sale_z'] = $v['online_sale_z'];
        $volume_list = array();
        $i=0;
        foreach ($goods_list_num as $key=>$value)
        {
            if($v['goods_id'] == $value['goods_id'])
            {
                if($value['price_type']=="sn_volume_price")
                {
                    $sqlsn = "select goods_attr from ".$ecs->table('products')." WHERE product_sn='".$value['product_sn']."' AND areaid=0";
                    $goods_atr = $db->getOne($sqlsn);
                    $goods_atr = str_replace('|',',', $goods_atr);
                    $sqlattr = "select attr_value FROM ".$ecs->table('goods_attr')." WHERE goods_attr_id in(".$goods_atr.")";
                    $good_atrname = $db->getAll($sqlattr);
                    $goods_attrname= '';
                    foreach ($good_atrname as $ak=>$av)
                    {
                        $goods_attrname = $goods_attrname.$av['attr_value']."|";
                    }
                    $goods_attrname = substr($goods_attrname,0,strlen($goods_attrname)-1);
                    $volume_list[$i]['attr'] = $goods_attrname;
                }
                $volume_list[$i]['num'] = $value['num'];
                if($dl_pd==1&&$dl_goods==1)
                {
                    $volume_list[$i]['price'] = 'HKD $'.$value['price'];
                }else 
                {
                    $volume_list[$i]['price'] = price_format($value['price']);
                }
                
                $volume_list[$i]['ze'] = ceil($value['price']/$shop_price)*10;
                $i++;
            }   
        }
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
        $gallery_list = get_goods_gallery($v['goods_id']); // 商品相册
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
        $goods_list[$k]['pictures']         = $gallery_list_one; // 商品相册
        $goods_list[$k]['pictures_t']         = $gallery_list_tow; // 商品相册
        $properties = get_goods_properties($v['goods_id']);  // 获得商品的规格和属性
        $goods_list[$k]['properties']       = $properties['pro']; // 商品属性
        $goods_list[$k]['specification']    = $properties['spe']; // 商品规格
        $sql = "select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where areaid in ".$area_value;
        $goods_list[$k]['number']    = $GLOBALS['db']->getOne($sql);
        $goods_list[$k]['volume_list'] = $volume_list;
    }

    if (!empty($goods_list))
    {
        $jsonRes['err_msg'] = 0;
        $jsonRes['result'] = appendPageVol($goods_list);
    } 
    else 
    { 
        $jsonRes['err_msg'] = 1;
    }

    die($json->encode($jsonRes)); 
        
}


function appendPageVol($res)
{
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
        $result .= '<img src="'.$spec['goods_thumb'].'" alt="">';
        $result .= '</a>';
        $result .= '</div>';
        $result .= '<div class="items-info">';
        $result .= '<p><a href="goods.php?id='.$spec['goods_id'].'" class="name">'.$spec['goods_name'].'</a></p>';
        $result .= '<p class="activity-del">'.$spec['shop_price'].'</p>';
        $result .= '<p>優惠規則 </p>';

        foreach ($spec['volume_list'] as $volume_key => $volume) {
            $result .= '<div class="normal-text">買';
            if($volume['attr']){
               $result .= '同款'.$volume['attr'];
            }
            $result .= $volume['num'];
            $result .= '件，單價：<span class="main-color">'.$volume['price'].'</span></div>';
        }
                          
        $result .= ' <div class="btn-box">';
        $result .= '<a href="goods.php?id='.$spec['goods_id'].'" class="btn">立即搶購</a>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
    }

    return $result;
}

?>