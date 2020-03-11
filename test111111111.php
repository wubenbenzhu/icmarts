<?php

/**
 * ECSHOP 首页文件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: index.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if(empty($_SESSION['send_code']))
    $_SESSION['send_code'] = random(6,1);
$smarty->assign('mobile', $_SESSION['mobile']);






$smarty->assign('mobile', $_SESSION['mobile']);
$smarty->assign('send_code', $_SESSION['send_code']);
/*------------------------------------------------------ */
//-- Shopex系统地址转换
/*------------------------------------------------------ */
if (!empty($_GET['gOo']))
{
    if (!empty($_GET['gcat']))
    {
        /* 商品分类。*/
        $Loaction = 'category.php?id=' . $_GET['gcat'];
    }
    elseif (!empty($_GET['acat']))
    {
        /* 文章分类。*/
        $Loaction = 'article_cat.php?id=' . $_GET['acat'];
    }
    elseif (!empty($_GET['goodsid']))
    {
        /* 商品详情。*/
        $Loaction = 'goods.php?id=' . $_GET['goodsid'];
    }
    elseif (!empty($_GET['articleid']))
    {
        /* 文章详情。*/
        $Loaction = 'article.php?id=' . $_GET['articleid'];
    }

    if (!empty($Loaction))
    {
        ecs_header("Location: $Loaction\n");

        exit;
    }
}

//判断是否有ajax请求
$act = !empty($_GET['act']) ? $_GET['act'] : '';
if ($act == 'cat_rec')
{
    $rec_array = array(1 => 'best', 2 => 'new', 3 => 'hot');
    $rec_type = !empty($_REQUEST['rec_type']) ? intval($_REQUEST['rec_type']) : '1';
    $cat_id = !empty($_REQUEST['cid']) ? intval($_REQUEST['cid']) : '0';
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result   = array('error' => 0, 'content' => '', 'type' => $rec_type, 'cat_id' => $cat_id);

    $children = get_children($cat_id);
    $smarty->assign($rec_array[$rec_type] . '_goods',      get_category_recommend_goods($rec_array[$rec_type], $children));    // 推荐商品
    $smarty->assign('cat_rec_sign', 1);
    $result['content'] = $smarty->fetch('library/recommend_' . $rec_array[$rec_type] . '.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内容
/*------------------------------------------------------ */
/* 缓存编号 */
$cache_id = sprintf('%X', crc32($_SESSION['user_rank'] . '-' . $_CFG['lang']));
if (!$smarty->is_cached('index.dwt', $cache_id))
{
    assign_template();

    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题

    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));

    $smarty->assign('top_goods',       get_top_goods());           // 销售排衿
    
    $smarty->assign('bonus_img',       get_bonus_img());       // 現金券圖牿


    $smarty->assign('index_js',        index_js());       // 首页广告数据  long
    $time = gmtime();
    
    $new_goods_list = array_chunk(get_recommend_goods('new'), 8);
    $smarty->assign('new_goods',      $new_goods_list[0]);     // 最新商哿
    $smarty->assign('new_goodss',      $new_goods_list[1]);     // 最新商哿
    $smarty->assign('new_goodsss',      $new_goods_list[2]);     // 最新商哿
    //$smarty->assign('activity_show',get_activity_show1());//買几送優惠
    /*$promote_goods_list = get_promote_goods1(" limit 0,3");//促销活动
    $smarty->assign('promote_goods_list',$promote_goods_list);
    if($promote_goods_list)
    {
    	$smarty->assign('promote_goods_listone',$promote_goods_list[0]);
    }
    $sql = "SELECT g.goods_id,g.goods_name,g.shop_price,g.goods_thumb,g.volume_start_date,g.volume_end_date,p.id FROM ".$ecs->table('price_area')." as p, ".$ecs->table('goods')." as g  WHERE g.is_delete!=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and g.is_on_sale=1 and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=".$_SESSION['area_rate_id']." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' "." group by g.goods_id  order by p.id desc limit 0,3 ";
    $volume_goods_list = $db->getAll($sql);//件数优惠
    $smarty->assign('volume_goods_list',$volume_goods_list);
    if($volume_goods_list)
    {
    	$volume_goods_listone = $volume_goods_list[0];
    	$volume_goods_listone['volume_start_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_start_date'] );
    	$volume_goods_listone['volume_end_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_end_date'] );
    	
    	$smarty->assign('volume_goods_listone',$volume_goods_listone);
    }
    */
    $package_goods_list = get_package_list(1,10); //组合活动  先取一个组合占位
    $smarty->assign('package_goods_list',$package_goods_list);
    
    $smarty->assign('act_list',        get_act_ads($_SESSION['area_rate_id']));       // 首页活动广告图列衿 long
    
    $sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".$time." >= start_time and ".$time." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by sort_order asc  ";

    $ad_hd = $db->getRow($sql);
    if($ad_hd)
    {
        $ad_hd['ad_link'] = "affiche.php?ad_id=".$ad_hd['ad_id']."&amp;uri=" .urlencode($ad_hd["ad_link"]);
        $ad_hd['ad_code'] = 'https://www.icmarts.com/data' . "/afficheimg/".$ad_hd['ad_code'];
    }
    $smarty->assign('ad_hd', $ad_hd);  //banner大图



    $sql = 'SELECT ad_id,ad_link,ad_code FROM ' . $ecs->table("ad") . " WHERE position_id = 9 and enabled=1 and ".$time." >= start_time and ".$time." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by sort_order asc limit 0,10  ";

    $ad_hdf = $db->getAll($sql);
    foreach($ad_hdf as $k=>$v) {
        $ad_hdf[$k]['ad_link'] = "affiche.php?ad_id=" . $v['ad_id'] . "&amp;uri=" . urlencode($v["ad_link"]);
        $ad_hdf[$k]['ad_code'] = 'https://www.icmarts.com/data' . "/afficheimg/" . $v['ad_code'];
    }
    $smarty->assign('ad_hdf', $ad_hdf);  //A4
    

    $sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 1 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by sort_order asc  ";

    $ad_hds = $db->getAll($sql);
    foreach($ad_hds as $k=>$v){
        $ad_hds[$k]['ad_link'] = "affiche.php?ad_id=".$v['ad_id']."&amp;uri=" .urlencode($v["ad_link"]);
        $ad_hds[$k]['ad_code'] = 'https://www.icmarts.com/data' . "/afficheimg/".$v['ad_code'];
    }
    $smarty->assign('ad_hds', $ad_hds);  //第一轮播大图

    $sql = 'SELECT ad_id,ad_link,ad_code,heights FROM ' . $ecs->table("ad") . " WHERE position_id = 4 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by sort_order asc  ";	
    $ad_hd2 = $db->getAll($sql);

    foreach($ad_hd2 as $k=>$value){
        $ad_hd2[$k]['ad_link'] = "affiche.php?ad_id=".$value['ad_id']."&amp;uri=" .urlencode($value["ad_link"]);
        $ad_hd2[$k]['ad_code'] = 'https://www.icmarts.com/data' . "/afficheimg/".$value['ad_code'];
    }

    $smarty->assign('ad_hd2', $ad_hd2);  //第二个轮播广告
    
    
    $sql = 'SELECT ad_id,ad_link,ad_code,heights FROM ' . $ecs->table("ad") . " WHERE position_id = 8 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by sort_order asc  ";
    $ad_hdnew = $db->getAll($sql);
    
    foreach($ad_hdnew as $k=>$value){
    	$ad_hdnew[$k]['ad_link'] = "affiche.php?ad_id=".$value['ad_id']."&amp;uri=" .urlencode($value["ad_link"]);
    	$ad_hdnew[$k]['ad_code'] = 'https://www.icmarts.com/data' . "/afficheimg/".$value['ad_code'];
    }
    
    $smarty->assign('ad_hdnew', $ad_hdnew);  //產品分類上方

    $sql = 'SELECT ad_name, ad_link,ad_code,heights FROM ' . $ecs->table("ad") . " WHERE position_id = 2 and is_type=0 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id'].
        "%' order by sort_order asc LIMIT 2";
    $wlad1 = $db->getAll($sql);

    $sql = 'SELECT ad_name, ad_link,ad_code,heights FROM ' . $ecs->table("ad") . " WHERE position_id = 2 and is_type=1 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id'].
        "%' order by sort_order asc LIMIT 3";
    $wlad2 = $db->getAll($sql);
   
    
    $wlad['length'] = $wlad1;
    $wlad2_list = array();
    
    if(isset($wlad2[1]))
    {
    	$wlad2_list[0] = $wlad2[2];
    }
    if(isset($wlad2[2]))
    {
    	$wlad2_list[1] = $wlad2[0];
    }
    if(isset($wlad2[0]))
    {
    	$wlad2_list[2] = $wlad2[1];
    }
   
    
    
    $wlad['sort'] = $wlad2_list;
  
    
    if(count($wlad1)==0&&count($wlad2)==0)
    {
    	$smarty->assign('wlad1', 0); //首页视频
    }
    else 
    {
    	$smarty->assign('wlad1', 1); //首页视频
    }
   
    	
    
    $smarty->assign('wlad', $wlad); //首页视频

    //facebook文章
    $fack_art = get_cat_articles(26);

    $ic_art = get_cat_articles(27);

    $smarty->assign('artciles_list',  $fack_art);
    $smarty->assign('icart_list',  $ic_art);
//var_dump($arr);

    /* 页面中的动态内宿*/
    assign_dynamic('index');
}
/*$file = 'themes/new_chaoliu/library/index.lbi';//静态网页文件名
$content = $smarty->make_html('index.dwt');//根据index.dwt模板生成网页内容
$filename = ROOT_PATH . $file;//静态网页路径
file_put_contents($filename, $content);//生成文件
echo $filename;die();*/
$smarty->display('index.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */
function index_get_cat_id_goods_best_list($cat_id = '', $num = '') 
{ 
$sql = 'Select g.goods_id, g.cat_id,c.parent_id, g.goods_name, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' . 
"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ". 
"promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, " . 
"g.is_best, g.is_new, g.is_hot, g.is_promote " . 
'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . 
'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS c ON c.cat_id = g.cat_id ' . 
"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ". 
"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ". 
"Where g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ". 
$sql .= " AND (c.parent_id =" . $cat_id. " OR g.cat_id = " . $cat_id ." OR g.cat_id ". db_create_in(array_unique(array_merge(array($cat_id), array_keys(cat_list($cat_id, 0, false))))) .")"; 
$sql .= " LIMIT $num"; 
$res = $GLOBALS['db']->getAll($sql); 
$goods = array(); 
foreach ($res AS $idx => $row) 
{ 
$goods[$idx]['id'] = $row['article_id']; 
$goods[$idx]['id'] = $row['goods_id']; 
$goods[$idx]['name'] = $row['goods_name']; 
$goods[$idx]['brief'] = $row['goods_brief']; 
$goods[$idx]['brand_name'] = $row['brand_name']; 
$goods[$idx]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']); 
$goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? 
sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name']; 
$goods[$idx]['short_style_name'] = add_style($goods[$idx]['short_name'],$row['goods_name_style']); 
$goods[$idx]['market_price'] = price_format($row['market_price']); 
$goods[$idx]['shop_price'] = price_format($row['shop_price']); 
$goods[$idx]['thumb'] = empty($row['goods_thumb']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb']; 
$goods[$idx]['goods_img'] = empty($row['goods_img']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_img']; 
$goods[$idx]['url'] = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']); 
} 
return $goods; 
}
/**
 * 调用发货单查询
 *
 * @access  private
 * @return  array
 */
function index_get_invoice_query()
{
    $sql = 'SELECT o.order_sn, o.invoice_no, s.shipping_code FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o' .
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('shipping') . ' AS s ON s.shipping_id = o.shipping_id' .
            " WHERE invoice_no > '' AND shipping_status = " . SS_SHIPPED .
            ' ORDER BY shipping_time DESC LIMIT 10';
    $all = $GLOBALS['db']->getAll($sql);

    foreach ($all AS $key => $row)
    {
        $plugin = ROOT_PATH . 'includes/modules/shipping/' . $row['shipping_code'] . '.php';

        if (file_exists($plugin))
        {
            include_once($plugin);

            $shipping = new $row['shipping_code'];
            $all[$key]['invoice_no'] = $shipping->query((string)$row['invoice_no']);
        }
    }

    clearstatcache();

    return $all;
}

/**
 * 获得最新的文章列表。
 *
 * @access  private
 * @return  array
 */
function index_get_new_articles()
{
    $sql = 'SELECT a.article_id, a.title, ac.cat_name, a.add_time, a.file_url, a.open_type, ac.cat_id, ac.cat_name ' .
            ' FROM ' . $GLOBALS['ecs']->table('article') . ' AS a, ' .
                $GLOBALS['ecs']->table('article_cat') . ' AS ac' .
            ' WHERE a.is_open = 1 AND a.cat_id = ac.cat_id AND ac.cat_type = 1' .
            ' ORDER BY a.article_type DESC, a.add_time DESC LIMIT ' . $GLOBALS['_CFG']['article_number'];
    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    foreach ($res AS $idx => $row)
    {
        $arr[$idx]['id']          = $row['article_id'];
        $arr[$idx]['title']       = $row['title'];
        $arr[$idx]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
                                        sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];
        $arr[$idx]['cat_name']    = $row['cat_name'];
        $arr[$idx]['add_time']    = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);
        $arr[$idx]['url']         = $row['open_type'] != 1 ?
                                        build_uri('article', array('aid' => $row['article_id']), $row['title']) : trim($row['file_url']);
        $arr[$idx]['cat_url']     = build_uri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);
    }

    return $arr;
}

/**
 * 获得最新的团购活动
 *
 * @access  private
 * @return  array
 */
function index_get_group_buy()
{
    $time = gmtime();
    $limit = get_library_number('group_buy', 'index');

    $group_buy_list = array();
    if ($limit > 0)
    {
        $sql = 'SELECT gb.act_id AS group_buy_id, gb.goods_id, gb.ext_info, gb.goods_name, g.goods_thumb, g.goods_img ' .
                'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' AS gb, ' .
                    $GLOBALS['ecs']->table('goods') . ' AS g ' .
                "WHERE gb.act_type = '" . GAT_GROUP_BUY . "' " .
                "AND g.goods_id = gb.goods_id " .
                "AND gb.start_time <= '" . $time . "' " .
                "AND gb.end_time >= '" . $time . "' " .
                "AND g.is_delete = 0 " .
                "ORDER BY gb.act_id DESC " .
                "LIMIT $limit" ;
        $res = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            /* 如果缩略图为空，使用默认图片 */
            $row['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $row['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);

            /* 根据价格阶梯，计算最低价 */
            $ext_info = unserialize($row['ext_info']);
            $price_ladder = $ext_info['price_ladder'];
            if (!is_array($price_ladder) || empty($price_ladder))
            {
                $row['last_price'] = price_format(0);
            }
            else
            {
                foreach ($price_ladder AS $amount_price)
                {
                    $price_ladder[$amount_price['amount']] = $amount_price['price'];
                }
            }
            ksort($price_ladder);
            $row['last_price'] = price_format(end($price_ladder));
            $row['url'] = build_uri('group_buy', array('gbid' => $row['group_buy_id']));
            $row['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                           sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $row['short_style_name']   = add_style($row['short_name'],'');
            $group_buy_list[] = $row;
        }
    }

    return $group_buy_list;
}

/**
 * 取得拍卖活动列表
 * @return  array
 */
function index_get_auction()
{
    $now = gmtime();
    $limit = get_library_number('auction', 'index');
    $sql = "SELECT a.act_id, a.goods_id, a.goods_name, a.ext_info, g.goods_thumb ".
            "FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS a," .
                      $GLOBALS['ecs']->table('goods') . " AS g" .
            " WHERE a.goods_id = g.goods_id" .
            " AND a.act_type = '" . GAT_AUCTION . "'" .
            " AND a.is_finished = 0" .
            " AND a.start_time <= '$now'" .
            " AND a.end_time >= '$now'" .
            " AND g.is_delete = 0" .
            " ORDER BY a.start_time DESC" .
            " LIMIT $limit";
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $ext_info = unserialize($row['ext_info']);
        $arr = array_merge($row, $ext_info);
        $arr['formated_start_price'] = price_format($arr['start_price']);
        $arr['formated_end_price'] = price_format($arr['end_price']);
        $arr['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr['url'] = build_uri('auction', array('auid' => $arr['act_id']));
        $arr['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                           sub_str($arr['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $arr['goods_name'];
        $arr['short_style_name']   = add_style($arr['short_name'],'');
        $list[] = $arr;
    }

    return $list;
}

/**
 * 获得所有的友情链接
 *
 * @access  private
 * @return  array
 */
function index_get_links()
{
    $sql = 'SELECT link_logo, link_name, link_url FROM ' . $GLOBALS['ecs']->table('friend_link') . ' ORDER BY show_order';
    $res = $GLOBALS['db']->getAll($sql);

    $links['img'] = $links['txt'] = array();

    foreach ($res AS $row)
    {
        if (!empty($row['link_logo']))
        {
            $links['img'][] = array('name' => $row['link_name'],
                                    'url'  => $row['link_url'],
                                    'logo' => $row['link_logo']);
        }
        else
        {
            $links['txt'][] = array('name' => $row['link_name'],
                                    'url'  => $row['link_url']);
        }
    }

    return $links;
}


//long start
/*获得首页广告数据*/
function index_js(){
	$xmlfile = ROOT_PATH . DATA_DIR . '/flash_data.xml';
	
	$xmlparser = xml_parser_create();
	
	// 打开文件并读取数据
	$fp = fopen($xmlfile, 'r');
	$xmldata = fread($fp, 4096);
	
	xml_parse_into_struct($xmlparser,$xmldata,$values);
	
	xml_parser_free($xmlparser);
	
	$re=array();
	for($i=0;$i<count($values);$i++){
		if($values[$i][attributes]){
			$values[$i][attributes]['LINK'] = str_replace('*', '&', $values[$i][attributes]['LINK']);
		$re[]= $values[$i][attributes];
		}
	}
	
	$res=array_sort($re,'SORT');
	var_dump($res);die;
	return $res;
}

/*二维数组排序
 * @ arr  数组
 * @ keys 按那个下标排序
 * */
function array_sort($arr,$keys,$type='asc'){
	$keysvalue = $new_array = array();
	foreach ($arr as $k=>$v){
		$keysvalue[$k] = $v[$keys];
	}
	if($type == 'asc'){
		asort($keysvalue);
	}else{
		arsort($keysvalue);
	}
	reset($keysvalue);
	foreach ($keysvalue as $k=>$v){
		$new_array[$k] = $arr[$k];
	}
	return $new_array;
}
//long end

/**
 * 取得活动广告图列表
 * @return  array
 */
function get_act_ads($rate_id){

    $list=array();
    $time=gmtime();

    $sql="select p.*,pt.end_time,pt.price_title as name from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('price_thumb').
        " as pt where p.hd_id = pt.id and p.areaid_rate = ".$rate_id." and p.areaid = 0 and p.price_type = 'price_cu_thumb' and pt.enabled = 1 and pt.end_time > ".
        $time." and pt.thumb_type = 1 and p.price_thumb <>'' order by pt.end_time desc, p.id desc ";
    
    $res=$GLOBALS['db']->getAll($sql);
  
foreach ($res as $k=>$v)
{
	
	if(empty($v['price_thumb_link'])||$v['price_thumb_link']=='')
	{
		$v['price_thumb_link'] = 'promote.php';
	}else
	{
	
	}
	$list[]= $v;
}
    $sql="select p.*,pt.end_time,pt.price_title as name from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('price_thumb').
        " as pt where p.hd_id = pt.id and p.areaid_rate = ".$rate_id." and p.areaid = 0 and p.price_type = 'price_shu_thumb' and pt.enabled = 1 and pt.end_time > ".
        $time." and pt.thumb_type = 2 and p.price_thumb <>'' order by pt.end_time desc, p.id desc ";
    $res=$GLOBALS['db']->getAll($sql);
foreach ($res as $k=>$v)
{
	if(empty($v['price_thumb_link'])||$v['price_thumb_link']=='')
	{
		$v['price_thumb_link'] = 'volume.php';
	}else
	{
	
	}
	$list[]= $v;
}
   /* $sql="select p.*,g.end_time,g.act_name as name from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods_activity').
        " as g where p.hd_id = g.act_id and p.areaid_rate = ".$rate_id." and p.areaid = 0 and p.price_type = 'package_price' and g.end_time > ".
        $time." and g.act_type = 4 and p.price_thumb <>'' order by g.end_time desc, p.id desc ";
    $res=$GLOBALS['db']->getAll($sql);
foreach ($res as $k=>$v)
{
	$v['price_thumb_link'] = 'package.php';
	$list[]= $v;
}

 $sql="select p.*,g.end_time,g.act_id,g.act_type,g.act_name as name  ,g.favourable_logo as logo from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('favourable_activity').
        " as g where p.hd_id = g.act_id and p.areaid_rate = ".$rate_id." and p.areaid = 0 and p.price_type = 'favourable_price' and g.end_time > ".
        $time." and g.favourable_logo <>'' order by g.end_time desc, p.id desc ";
 
    $res=$GLOBALS['db']->getAll($sql);
foreach ($res as $k=>$v)
{
	$v['price_thumb'] = $v['logo'];
    if($v['act_type'] == 3 || $v['act_type'] == 4|| $v['act_type'] == 2){
        $v['price_thumb_link'] = 'activity_son.php?step=show_goods&id='.$v['act_id'];
    }else{
        //$v['price_thumb_link'] = 'activity.php';
		$v['price_thumb_link'] = 'activity_son.php?step=show_goods&id='.$v['act_id'];
    }
	$list[]= $v;
}
*/
    return $list;
}
?>