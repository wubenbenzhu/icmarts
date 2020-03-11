<?php

/**
 * ECSHOP 商品详情
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');


if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
$sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";

$ad_hd_ban = $db->getRow($sql);
 
if($ad_hd_ban)
{
	$ad_hd_ban['ad_link'] = "affiche.php?ad_id=".$ad_hd_ban['ad_id']."&amp;uri=" .urlencode($ad_hd_ban["ad_link"]);
	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
}

//facebook 统计代码
$sql = " SELECT * FROM ".$ecs->table("shop_config")." WHERE code='facebook' ";
$facebook_code = $db->getRow($sql);
$smarty->assign('facebook_code', $facebook_code);

//facebook 统计代码



$smarty->assign('ad_hd_ban', $ad_hd_ban);
$smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
$smarty->assign('bonus_left',    1); // 左：現金券圖lbi顯示
$smarty->assign('bonus_img',       get_bonus_img());       // 現金券圖片
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

$goods_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;

/*------------------------------------------------------ */
//-- 改变属性、数量时重新计算商品价格
/*------------------------------------------------------ */

/*------------------------------------------------------ */
//-- 商品购买记录ajax处理
/*------------------------------------------------------ */
/*------------------------------------------------------ */
if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'price_p') //配件选择属性筛选
{
	include('includes/cls_json.php');

	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);

	//$attr_id    = isset($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array();
	//$number     = (isset($_REQUEST['number'])) ? intval($_REQUEST['number']) : 1;
	$goods_id = $_REQUEST['id'];
	$xuanze = $_REQUEST['xuanze'];
	$sattr_id    = isset($_REQUEST['mxuanze']) ? explode(',', $_REQUEST['mxuanze']) : array();
	$mes = '';
	$wuchan = 0;
	if($xuanze>0)
	{

		$messageall = get_goods_properties_two($goods_id,$xuanze,$sattr_id);
			
		foreach ($messageall['spe'] as $key=>$value)
		{
			$xs = 0;
			$mes =$mes. '<div class="option"> <select name="s_'.$goods_id.'" onChange="changeP_p'.'(\'spec_'.$key.'\',\''.$goods_id.'\',\''.''.'\',this,'.'123'.','.$key.')" id="'.$goods_id.'_s_'.$key.'" >';
			foreach ($value['values'] as $v)
			{
				if($v['xianshi'] == 1)
				{
					$mes = $mes.'<option value="'.$v['id'].'" ';
					$xs = 1;
				}

				if($v['css'] == 1)
				{
					$mes = $mes.' selected="selected"';
				}
				if($v['xianshi'] == 1)
				{
					$mes = $mes.' >'.$v['label'].'</option> ';
				}

			}
			if($xs == 0 )
			{
				$mes = $mes.'<option value="-1" >无该属性产品</option> ';
				$wuchan = 1;
			}

			$mes = $mes.' </select></div>';
		}


		$res['message_pan'] = 1;
		$res['message_t'] = $mes;
		$res['id'] = $goods_id;
		$res['wuchan'] = $wuchan; //判断不完整属性，用于前端checkbox


	}
	die($json->encode($res));
}
/*---start分类页属性部分处理*/
if($_REQUEST['act'] == 'category_price')
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	$goodsid = $_REQUEST['goodsid'];  //商品ID
	$attr = isset($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array(); //已选择属性组成的字符串
	
	$attrquan = $_REQUEST['attrquan']; //判断组合是否选择全属性
	
	$attrvalue = $_REQUEST['attrvalue']; //选择属性值
	$attrkey = $_REQUEST['attrkey'];//选择属性键值
	
	/* 对属性进行重新排序和分组 */
	$messageall = get_goods_properties_two($goodsid,$attrvalue,$attr);
	
	$co = count($messageall['spe']);
	$mes = '';
	$mrid = '';
	//var_dump($messageall['spe']);die();
	$price_total = 0;
	foreach ($messageall['spe'] as $key=>$value)
	{
		$mes = $mes.'<dl><dt class="box-w25">'.$value['name'].'</dt>';
		$mes = $mes.'<dd class="box-w75">';
		$mes = $mes.'<ul class="infotop">';
		foreach ($value['values'] as $v)
		{
			$mes = $mes.'<li ';
			if($v['xianshi'] == 1)//可选属性
			{
				$mes = $mes.' id="url_'.$v['id']."_".$goodsid.'" '."onclick="."changeP('spec_".$key."_".$goodsid."','".$v['id']."',".$goodsid.",".$key.")  ";
				if($v['css'] == 1)//默认选属性
				{
					$mes = $mes.' class="selected" >'."<input style=\"display:none\" id=\"spec_value_".$v['id']."_".$goodsid."\" type=\"radio\" name=\"spec_".$key."_".$goodsid."\"  checked='checked' />";
					$mrid = $v['id'];
				}else 
				{
					$mes = $mes.' >'."<input style=\"display:none\" id=\"spec_value_".$v['id']."_".$goodsid."\" type=\"radio\" name=\"spec_".$key."_".$goodsid."\" value=\"\""." />";
				}
			}else //不存在或者不现实属性
			{
				$mes = $mes.' class="none" style="border: 2px solid black;">'."<input style=\"display:none\" id=\"spec_value_".$v['id']."_".$goodsid."\" type=\"radio\" name=\"spec_".$key."_".$goodsid."\" value=\"\""." />";
			}
			$mes = $mes.'<span name="sp_url_'.$key."_".$goodsid.'" >'.$v['label']."</span>";
			
			$mes = $mes.'</li>';
		}
		$mes = $mes."<input type=\"hidden\" name=\"attr_".$goodsid."\" id=\"attr_".$key."_".$goodsid."\" value=\"$mrid\" />";
		$mes = $mes.'</ul></dd></dl>';
	}
	
	$p_type = get_promotion_info($goodsid);
	if(!empty($p_type)){
		if($p_type[0]['act_type']==0)
		{
			if(isset($p_type[1]))
			{
				$p_type[0] = $p_type[1];
			}	
		}
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
						$zk_price = $price_total*$v/10;
					}else
					{
						$zk_price = $price_total*$v/100;
					}
					if($dl_pd==1&&$dl_goods==1)
					{
						//$goods[$idx]['promotion_type']['act_name'] = $p_type[0]['act_name']; //参加的活动
							
						//$arr[$row['goods_id']]['promotion_type']['act_price'] ='HKD $ '.$zk_price;
					}else
					{
	
						$price_total = $zk_price;
					}
				}
					
				if(isset($v['buy'])&&$v['buy']>0&&isset($v[$_SESSION['area_rate_id']])&&$v[$_SESSION['area_rate_id']]>0)
				{
					$zk_price = 0;
				}
	
			}
				
		}
	}
	$res['area_rate_code'] = $_SESSION['area_rate_code'];
	$res['result'] = $price_total;
	$res['message'] = $mes;
	$res['goods_id'] = $goodsid;
	
	die($json->encode($res));
}
/*---end分类页属性部分处理*/
/*-------------------------------------------------------*/
/*---start组合礼包属性部分处理*/
if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'price_libao')
{
	include('includes/cls_json.php');

	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	if ($goods_id == 0)
	{
		$res['err_msg'] = $_LANG['err_change_attr'];
		$res['err_no']  = 1;
	}
	else
	{
		$xuanze = $_REQUEST['xuanze'];
		$sattr_id    = isset($_REQUEST['mxuanze']) ? explode(',', $_REQUEST['mxuanze']) : array();
		$pid = $_REQUEST['pid'];
		if($xuanze>0)  //属性筛选
		{
			$goods_key = $_REQUEST['goodskey'];
			$messageall = get_goods_properties_two($goods_id,$xuanze,$sattr_id);
				
			$mes = '';
			foreach ($messageall['spe'] as $key=>$value)
			{
				$mes=$mes.'<p>'.$value['name'].'：';
				$mes = $mes.'<select name="'.$pid.'_spec_'.$goods_id.'_'.$key.'_'.$goods_key.'" id="'.$pid.'_spec_'.$goods_id.'_'.$key.'_id_'.$goods_key.'" onchange="change('.
						$goods_id.',\''.$pid.'_spec_'.$goods_id.'_'.$key.'_id_'.$goods_key.'\','.$pid.','.$key.','.$key.','.$goods_key.')">';
				foreach ($value['values'] as $v)
				{
					if($v['xianshi'] == 1)
					{
						$mes = $mes.'<option value="'.$goods_id.'_'.$v['id'].'_'.$goods_key.'" ';
						if($v['css'] == 1)
						{
							$mes = $mes.' selected="selected" ';
						}
						$mes = $mes.'>'.$v['label'].'</option>';
					}
				}
				$mes = $mes.'</select></p>';
			}
			$res['mes'] = $mes;
		}
	}
	$res['pid'] = $pid;
	$res['goods_id'] = $goods_id;
	die($json->encode($res));
}

if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'price_libao_flow')
{
	include('includes/cls_json.php');

	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	$attr  = array();
	$vk   = 0;
	if ($goods_id == 0)
	{
		$res['err_msg'] = $_LANG['err_change_attr'];
		$res['err_no']  = 1;
	}
	else
	{
		$xuanze = $_REQUEST['xuanze'];
		$sattr_id    = isset($_REQUEST['mxuanze']) ? explode(',', $_REQUEST['mxuanze']) : array();
		$pid = $_REQUEST['pid'];
		if($xuanze>0)  //属性筛选
		{
			$goods_key = $_REQUEST['goodskey'];
			$messageall = get_goods_properties_two($goods_id,$xuanze,$sattr_id);

			$mes = '';
			foreach ($messageall['spe'] as $key=>$value)
			{
				$mes = $mes.'<select name="'.$pid.'_spec_'.$goods_id.'_'.$key.'_'.$goods_key.'" id="'.$pid.'_spec_'.$goods_id.'_'.$key.'_id_'.$goods_key.'" onchange="change('.
					$goods_id.',\''.$pid.'_spec_'.$goods_id.'_'.$key.'_id_'.$goods_key.'\','.$pid.','.$key.','.$key.','.$goods_key.')">';
				foreach ($value['values'] as $v)
				{
					if($v['xianshi'] == 1)
					{
						$mes = $mes.'<option value="'.$goods_id.'_'.$v['id'].'_'.$goods_key.'" ';
						if($v['css'] == 1)
						{
							$mes = $mes.' selected="selected" ';
							$attr[] = $v['id'];
						}
						$mes = $mes.'>'.$v['label'].'</option>';
					}
				}
				$mes = $mes.'</select>';
			}
			$res['mes'] = $mes;

			if(count($messageall['spe']) == count($attr)){
				$res['goods_attr_id'] = implode(',',$attr);
			}else{
				$res['goods_attr_id'] = '';
			}
		}
	}
	$res['pid'] = $pid;
	$res['goods_id'] = $goods_id;
	die($json->encode($res));
}
/*---end组合礼包属性部分处理*/
/*-------------------------------------------------------*/
/*--start首頁彈窗處理*/
if ($_REQUEST['act'] == 'show_goods')
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	if(isset($_REQUEST['id']))
	{
		$res['err_msg'] = '商品下架了';
	}
	$goods_id = $_REQUEST['id'];//查詢的商品
	$goods_info = get_goods_info($goods_id);
	$dl_pd = 0;
	if($_SESSION['user_id'])
	{
		$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
		$dl_pd = $GLOBALS['db']->getOne($sql);
	}
	if($dl_pd==1&&$goods_info['dl_goods']==1)
	{
		$volume_price_list = get_volume_price_list($goods_id, '1',0,4);
	}else
	{
		$volume_price_list = get_volume_price_list($goods_id, '1',0,$_SESSION['area_rate_id']);
	}
	
	
	$n = count($volume_price_list);
	if(!empty($volume_price_list)) {
		if (!empty($goods_info['promote_price_org'])) {
			$a = array(0 => $goods_info['shop_price'], 1 => $goods_info['promote_price_org'], 2 => $volume_price_list[$n - 1]['price']);
		} else {
			$a = array(0 => $goods_info['shop_price'], 1 => $volume_price_list[$n - 1]['price']);
		}
	}else{
		if (!empty($goods_info['promote_price_org'])) {
			$a = array(0 => $goods_info['shop_price'], 1 => $goods_info['promote_price_org']);
		} else {
			$a = array(0 => $goods_info['shop_price']);
		}
	}
	
	$pos = array_search(min($a), $a);
	
	if($dl_pd==1&&$goods_info['dl_goods']==1)
	{
		$goods_info['min_price'] = 'HKD $ '.$a[$pos];
		$goods_info['min_price_p'] = $a[$pos];
	}else
	{
		$goods_info['min_price'] = price_format($a[$pos]);
		$goods_info['min_price_p'] = $a[$pos];
	}
	$properties = get_goods_properties_two($goods_id);  // 获得商品的规格和属性
	
	$attr_string = '';
	foreach ($properties['spe'] as $key=>$value)
	{
		foreach ($value['values'] as $k=>$v)
		{
			if($v['css']==1&&$v['xianshi'])
			{
				$attr_string = $attr_string.$v['id'].'|';
			}
		}
	}
	$attr_string = substr($attr_string, 0, -1);
	$sql = " SELECT product_sn_code FROM ".$GLOBALS['ecs']->table('products')." WHERE goods_attr='".$attr_string."' and goods_id=".$goods_id." and  areaid=0 ";
	$product_sn_code = $GLOBALS['db']->getOne($sql);
	$result_mes = '<form action="javascript:#" method="post" name="ECS_FORMBUY" id="ECS_FORMBUY" >';
	$result_mes .= '<div class="add-product">';
	$result_mes .=' <div class="img-list">';
	$pictures = get_goods_gallery($goods_id);
	$i=0;
	foreach ($pictures as $key=>$value)
	{
		if($i==0)
		{
		  $result_mes .= '<a href="#" class="cover" style="background-image: url('.$value['img_url'].');"></a>';
		  $result_mes .='<div class="thumbnail-list">';
		  $result_mes.='<div class="thumbnail-box active" style="background-image: url('.$value['img_url'].');"></div>';
		  	
		}else 
		{
			$result_mes.='<div class="thumbnail-box" style="background-image: url('.$value['img_url'].');"></div>';
		}
		$i=$i+1;
	}
	$result_mes.=' </div></div>';
	$att1 = '';
	$att = '';
	foreach ($properties['spe'] as $key=>$value)
	{
		foreach ($value['values'] as $v)
		{
			if($v['css'] == 1)
			{
				$att1 = $v['id'].'|';
			}
		}
		$att = $att.$att1;
	}
	if($att)
	{
		$att = substr($att,0,strlen($att)-1);
	}
	$dl_pd = 0;
	if($_SESSION['user_id'])
	{
		$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
		$dl_pd = $GLOBALS['db']->getOne($sql);
	}
	$dl_goods = 0;
	$sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$goods_id;
	$dl_goods = $GLOBALS['db']->getOne($sql);
	
	$shop_price  = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区促销价格;
	$dl_pd = 0;
	if($_SESSION['user_id'])
	{
		$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
		$dl_pd = $GLOBALS['db']->getOne($sql);
	}
	$dl_goods = 0;
	$sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$goods_id;
	$dl_goods = $GLOBALS['db']->getOne($sql);
	
	
	$where = db_create_in($attr_id, 'hd_id');
	if($dl_pd==1&&$dl_goods==1)
	{
		$where = $where ." AND price_type='attr_price' "." AND goods_id='".$goods_id."'" ." and areaid=0 AND  areaid_rate=4 ";//代理登陆且是代理商品取香港价格
	}else
	{
		$where = $where ." AND price_type='attr_price' "." AND goods_id='".$goods_id."'" ." and areaid=0 AND  areaid_rate='".$_SESSION['area_rate_id']."'";
	}
	
	$sql = 'SELECT SUM(price) AS attr_price FROM ' . $GLOBALS['ecs']->table('price_area') . " WHERE $where";
	$price11 = floatval($GLOBALS['db']->getOne($sql));
	$res['result_rank_p'] = 0;
	 
	$res['result_rank_p'] = 1;
	if($dl_pd==1&&$dl_goods==1)
	{
		$sql = " SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$goods_id." AND areaid=0 AND  areaid_rate=4 ";//代理登陆且是代理商品取香港价格
	}else
	{
		$sql = " SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$goods_id." AND areaid=0 AND  areaid_rate='".$_SESSION['area_rate_id']."'";
	}
	 
	$yuanprice = $db->getOne($sql);
	//$yuanprice = $shop_price;
	//$yuanprice = $yuanprice+$price11;
	$sql = "SELECT rank_id,  r.rank_name, r.discount,r.dl_discount,r.dl_pd " .
			'FROM ' . $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
			'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
			"ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " .
			"WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";
	$user_rank_list = $db->getAll($sql);
	$rank_price_list = "";
	foreach ($user_rank_list as $key=>$value)
	{
		if($dl_pd==1&&$dl_goods==1)
		{
			$userprice =floatval(get_price_area($goods_id,0,'user_price',$value['rank_id'],0,4));//代理登陆且是代理商品取香港价格
		}else
		{
			$userprice =floatval(get_price_area($goods_id,0,'user_price',$value['rank_id'],0,$_SESSION['area_rate_id']));
		}
		 
		 
		$rank_price_list = $rank_price_list."<dd ><i>".$value['rank_name']."折後價:";
		if($value['dl_pd']==1)//判断是不是代理会员
		{
			$sql ="SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$goods_id;
			$dl_goods = $GLOBALS['db']->getOne($sql);
	
			if($dl_goods==1)
			{
				if($userprice == -1)
				{
					$price_u = ceil($price11*$value['dl_discount']/100+$yuanprice*$value['dl_discount']/100);
				}else
				{
					$price_u = ceil($price11+$userprice);
				}
	
			}else
			{
	
				if($userprice == -1)
				{
					$price_u = ceil($price11*$value['discount']/100+$yuanprice*$value['discount']/100);
					 
				}else
				{
					$price_u = ceil($price11+$userprice);
				}
	
	
			}
		}else
		{
			if($userprice == -1)
			{
				$price_u = ceil($price11*$value['discount']/100+$yuanprice*$value['discount']/100);
			}else
			{
				$price_u = ceil($price11+$userprice);
			}
			 
		}
	
		if($_SESSION['user_rank']==$value['rank_id'])
		{
			$shop_price = $price_u;
		}
		if($dl_pd==1&&$dl_goods==1)
		{
			$user_rank_list[$key]['price'] = 'HKD $ '.$price_u;
		}else
		{
			$user_rank_list[$key]['price'] = price_format($price_u);
		}
		$rank_price_list = $rank_price_list.$user_rank_list[$key]['price']."</i></dd >";
	
		$res['result_rank_mes'] = $rank_price_list;
	}
	
	
	$p_type = get_promotion_info($goods_id);
	if(!empty($p_type)){
		if($p_type[0]['act_type']==0)
		{
			if(isset($p_type[1]))
			{
				$p_type[0] = $p_type[1];
			}
			 
		}
		if($p_type[0]['act_type']!=0)
		{
			$xlh = unserialize($p_type[0]['buy']);
			$xlh_key = count($xlh);
			foreach ($xlh as $key=>$v)
			{
				if($p_type[0]['act_type']==4)
				{
					$cshop_price = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);
					$cshop_price =$cshop_price+$price11;
					$zk_price =$cshop_price*$v/10;
				}
				if($p_type[0]['act_type']==2)
				{
					$cshop_price = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);
					$cshop_price =$cshop_price+$price11;
					$zk_price =$cshop_price*$v/100;
				}
				if(isset($v['buy'])&&$v['buy']>0&&isset($v[$_SESSION['area_rate_id']])&&$v[$_SESSION['area_rate_id']]>0)
				{
					$zk_price = $v[$_SESSION['area_rate_id']]/$v['buy'];
				}
			}
			if($dl_pd==1&&$dl_goods==1)
			{
	
			}else
			{
				$mj_price=$zk_price;
			}
	
	
		}
	}
	$zkxs_price = $shop_price;
	if($mj_price>0)
	{
		if($zkxs_price>$mj_price)
		{
			$zkxs_price = $mj_price;
		}
	}
	$now = gmtime();
	
	
	
	if($dl_pd==1&&$dl_goods==1)
	{
		$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		" and p.areaid_rate =4 and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
		$sql = "select price from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		" and p.areaid_rate =4 and p.areaid = 0 and g.goods_id=p.goods_id and  g.promote_start_date <= $now and g.promote_end_date >= $now  and p.price_type = 'promote_price' ";
	}else
	{
		$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		" and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
		$sql = "select price from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		"  and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.promote_start_date <= $now and g.promote_end_date >= $now  and p.price_type = 'promote_price' ";
	}
	
	$promote_price_t = $GLOBALS['db']->getOne($sql);
	
	if(!empty($promote_price_t)&&$promote_price_t>0)
	{
		if($zkxs_price>$promote_price_t)
		{
			$zkxs_price = $promote_price_t;
		}
	}
	
	$goods_list_num = $GLOBALS['db']->getRow($sql_v);
	$shop_price_rate1 = 0;
	if ($shop_price>$goods_list_num['price']&&$goods_list_num['price']!=0) {
	
		$shop_price_rate1 =$goods_list_num['price'];
	}
	$shop_price = $price11 + $shop_price;
	$zkxs_price = $zkxs_price+$price11;
	$number = 1;
	if($dl_pd==1&&$dl_goods==1)
	{
		$res['result'] = 'HKD $ '.($shop_price * $number);
		if($shop_price_rate1>0)
		{
			$res['resultzk_price'] = 'HKD $ '.(($shop_price_rate1 * $number)+$price11);
		}else
		{
			$res['resultzk_price'] = 'HKD $ '.($shop_price * $number);
		}
		 
	}else
	{
		if($shop_price_rate1>0)
		{
			$res['resultzk_price'] = price_format(($shop_price_rate1 * $number)+$price11);
		}else
		{
			$res['resultzk_price'] = price_format($zkxs_price * $number);
		}
		 
		 
		$res['result'] = price_format($shop_price * $number);
	}
	
	$res['result_price'] = ceil($shop_price * $number);
	
	
	$result_mes.='<div class="add-product-info">';
	$result_mes.='<div class="title">'.$goods_info['goods_name'].'</div>';
	$result_mes.='<div class="price"><span class="num" id="goods_price">'.$res['result'].'</span> <span class="original-price">'.$goods_info['market_price'].'</span></div><br />';
	$result_mes.='<div class="product-type" id="suxing">';
	$p_string = '';
	$yc_string = '';
	
	foreach ($properties['spe'] as $key=>$value)
	{
		$yc_string .=' <input type="hidden" name="jiushuxing_'.$key.'"  id="jiushuxing_'.$key.'" value="">  <span id="value_'.$key.'" style="display:none"></span>';
		$result_mes.='<p class="type-list">'.$value['name'].'： ';
		foreach ($value['values'] as $v)
		{
			if($v['price']!=0.00&&$v['price']!='0.00'&&$v['price']!='0.000'&&$v['price']&&$v['price']!='0.0'&&$v['price']!='0')
			{
			
				$peiprice = $v['format_price'];
			}else
			{
				$peiprice = 0;
			}
			if($v['xianshi'] == 1)
			{
				$result_mes = $result_mes.'<a onclick="changeP(\'spec_'.$key.'\',\''.$v['id'].'\',\''.$v['label'].'\',this,'.$v['id'].','.$key.',\''.$peiprice.'\')"'.' name="sp_url_'.$key.'" id="url_'.$v['id'].'" >';
				$result_mes.='<span ';
				if($v['css'] == 1)
				{
					$result_mes.='class="active">';
				}else 
				{
					$result_mes.=' >';
				}
			}else 
			{
				$result_mes.='<a>';
				$result_mes.='<span ';
				$result_mes.='class="disable">';
			}
			$result_mes.=$v['label'].'</span></a>';
			$result_mes = $result_mes.'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"';
			if($v['css'] == 1)
			{
				
				$result_mes = $result_mes.'checked ';
			}
			$result_mes = $result_mes.'> ';
		}
		$result_mes.='</p>';
	}
	$result_mes.=' </div>';
	$result_mes .=$yc_string;
	$result_mes .='<input type="hidden" id="area_rate_code_t" value="'.$_SESSION['area_rate_code'].'"/>'.'<span id="ECS_GOODS_AMOUNT" style="display:none">'.$goods_info['shop_price_formated'].'</span><span id="area_number" style="display:none"></span>';
	$result_mes.='<div class="text-right">';
	$result_mes.='<div class="pull-left num-box">';
	$result_mes.='<span class="plus" ><i class="iconfont icon-plus" onClick="onjia()"></i></span>';
	$result_mes.='<span><input type="number" name="number" value="1" min="1" id="g_num"/></span>';
	$result_mes.=' <span class="minus" ><i class="iconfont icon-minus" onClick="onjian()"></i></span>';
	$result_mes.='</div><button class="btn btn-md" id="jrgc" onclick="addToCart('.$goods_id.')">加入購物車</button></div></div></div></form>';
	
	$res['result'] = $result_mes;
	die($json->encode($res));
}
/*--end首頁彈窗處理*/
/*-------------------------------------------------------*/
/*-------------------------------------------------------*/
/*---start 首页属性切换*/
if ($_REQUEST['act'] == 'page_list')
{
	include('includes/cls_json.php');
	
	$json   = new JSON;
	$res    = array('err_msg' => '', 'result' => '', 'qty' => 1);
	$attr_id    = isset($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array();
	
	$number     = (isset($_REQUEST['number'])) ? intval($_REQUEST['number']) : 1;
	$xuanze = $_REQUEST['xuanze'];
	$sattr_id    = isset($_REQUEST['mxuanze']) ? explode(',', $_REQUEST['mxuanze']) : array();
	
	$messageall = get_goods_properties_two($goods_id,$xuanze,$sattr_id);
	$result_mes = '';
	$yc_string = '';
	$pan = 0;
	$count_pan = 0;
	$att='';
	$att1 = '';
	foreach ($messageall['spe'] as $key=>$value)
	{
		$yc_string .=' <input type="hidden" name="jiushuxing_'.$key.'"  id="jiushuxing_'.$key.'" value="">  <span id="value_'.$key.'" style="display:none"></span>';
		$result_mes.='<p class="type-list">'.$value['name'].'： ';
		foreach ($value['values'] as $v)
		{
			if($v['price']!=0.00&&$v['price']!='0.00'&&$v['price']!='0.000'&&$v['price']&&$v['price']!='0.0'&&$v['price']!='0')
			{
			
				$peiprice = $v['format_price'];
			}else
			{
				$peiprice = 0;
			}
			if($v['xianshi'] == 1)
			{
				$result_mes = $result_mes.'<a onclick="changeP(\'spec_'.$key.'\',\''.$v['id'].'\',\''.$v['label'].'\',this,'.$v['id'].','.$key.',\''.$peiprice.'\')"'.' name="sp_url_'.$key.'" id="url_'.$v['id'].'" >';
				$result_mes.='<span ';
				if($v['css'] == 1)
				{
					$count_pan = $count_pan+1;
					$result_mes.='class="active">';
				}else 
				{
					$result_mes.=' >';
				}
			}else 
			{
				$result_mes.='<a>';
				$result_mes.='<span ';
				$result_mes.='class="disable">';
			}
			$result_mes.=$v['label'].'</span></a>';
			$result_mes = $result_mes.'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"';
			if($v['css'] == 1)
			{
				$att1 = $v['id'].'|';
				$result_mes = $result_mes.'checked ';
			}
			$result_mes = $result_mes.'> ';
		}
		$result_mes.='</p>';
		$att = $att.$att1;
	}
	if($att)
	{
		$att = substr($att,0,strlen($att)-1);
	}
	if(count($messageall['spe'])==$count_pan)
	{
		$pan = 1;
	}
	$res['pan'] = $pan;
	$res['message_t'] = $result_mes;
	$shop_price  = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区促销价格;
	$dl_pd = 0;
	if($_SESSION['user_id'])
	{
		$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
		$dl_pd = $GLOBALS['db']->getOne($sql);
	}
	$dl_goods = 0;
	$sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$goods_id;
	$dl_goods = $GLOBALS['db']->getOne($sql);
	
	
	$where = db_create_in($attr_id, 'hd_id');
	if($dl_pd==1&&$dl_goods==1)
	{
		$where = $where ." AND price_type='attr_price' "." AND goods_id='".$goods_id."'" ." and areaid=0 AND  areaid_rate=4 ";//代理登陆且是代理商品取香港价格
	}else
	{
		$where = $where ." AND price_type='attr_price' "." AND goods_id='".$goods_id."'" ." and areaid=0 AND  areaid_rate='".$_SESSION['area_rate_id']."'";
	}
	
	$sql = 'SELECT SUM(price) AS attr_price FROM ' . $GLOBALS['ecs']->table('price_area') . " WHERE $where";
	$price11 = floatval($GLOBALS['db']->getOne($sql));
	$res['result_rank_p'] = 0;
	 
	$res['result_rank_p'] = 1;
	if($dl_pd==1&&$dl_goods==1)
	{
		$sql = " SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$goods_id." AND areaid=0 AND  areaid_rate=4 ";//代理登陆且是代理商品取香港价格
	}else
	{
		$sql = " SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$goods_id." AND areaid=0 AND  areaid_rate='".$_SESSION['area_rate_id']."'";
	}
	 
	$yuanprice = $db->getOne($sql);
	//$yuanprice = $shop_price;
	//$yuanprice = $yuanprice+$price11;
	$sql = "SELECT rank_id,  r.rank_name, r.discount,r.dl_discount,r.dl_pd " .
			'FROM ' . $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
			'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
			"ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " .
			"WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";
	$user_rank_list = $db->getAll($sql);
	$rank_price_list = "";
	foreach ($user_rank_list as $key=>$value)
	{
		if($dl_pd==1&&$dl_goods==1)
		{
			$userprice =floatval(get_price_area($goods_id,0,'user_price',$value['rank_id'],0,4));//代理登陆且是代理商品取香港价格
		}else
		{
			$userprice =floatval(get_price_area($goods_id,0,'user_price',$value['rank_id'],0,$_SESSION['area_rate_id']));
		}
		 
		 
		$rank_price_list = $rank_price_list."<dd ><i>".$value['rank_name']."折後價:";
		if($value['dl_pd']==1)//判断是不是代理会员
		{
			$sql ="SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$goods_id;
			$dl_goods = $GLOBALS['db']->getOne($sql);
	
			if($dl_goods==1)
			{
				if($userprice == -1)
				{
					$price_u = ceil($price11*$value['dl_discount']/100+$yuanprice*$value['dl_discount']/100);
				}else
				{
					$price_u = ceil($price11+$userprice);
				}
	
			}else
			{
	
				if($userprice == -1)
				{
					$price_u = ceil($price11*$value['discount']/100+$yuanprice*$value['discount']/100);
					 
				}else
				{
					$price_u = ceil($price11+$userprice);
				}
	
	
			}
		}else
		{
			if($userprice == -1)
			{
				$price_u = ceil($price11*$value['discount']/100+$yuanprice*$value['discount']/100);
			}else
			{
				$price_u = ceil($price11+$userprice);
			}
			 
		}
	
		if($_SESSION['user_rank']==$value['rank_id'])
		{
			$shop_price = $price_u;
		}
		if($dl_pd==1&&$dl_goods==1)
		{
			$user_rank_list[$key]['price'] = 'HKD $ '.$price_u;
		}else
		{
			$user_rank_list[$key]['price'] = price_format($price_u);
		}
		$rank_price_list = $rank_price_list.$user_rank_list[$key]['price']."</i></dd >";
	
		$res['result_rank_mes'] = $rank_price_list;
	}
	
	
	$p_type = get_promotion_info($goods_id);
	if(!empty($p_type)){
		if($p_type[0]['act_type']==0)
		{
			if(isset($p_type[1]))
			{
				$p_type[0] = $p_type[1];
			}
			 
		}
		if($p_type[0]['act_type']!=0)
		{
			$xlh = unserialize($p_type[0]['buy']);
			$xlh_key = count($xlh);
			foreach ($xlh as $key=>$v)
			{
				if($p_type[0]['act_type']==4)
				{
					$cshop_price = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);
					$cshop_price =$cshop_price+$price11;
					$zk_price =$cshop_price*$v/10;
				}
				if($p_type[0]['act_type']==2)
				{
					$cshop_price = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);
					$cshop_price =$cshop_price+$price11;
					$zk_price =$cshop_price*$v/100;
				}
				if(isset($v['buy'])&&$v['buy']>0&&isset($v[$_SESSION['area_rate_id']])&&$v[$_SESSION['area_rate_id']]>0)
				{
					$zk_price = $v[$_SESSION['area_rate_id']]/$v['buy'];
				}
			}
			if($dl_pd==1&&$dl_goods==1)
			{
	
			}else
			{
				$mj_price=$zk_price;
			}
	
	
		}
	}
	$zkxs_price = $shop_price;
	if($mj_price>0)
	{
		if($zkxs_price>$mj_price)
		{
			$zkxs_price = $mj_price;
		}
	}
	$now = gmtime();
	
	
	
	if($dl_pd==1&&$dl_goods==1)
	{
		$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		" and p.areaid_rate =4 and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
		$sql = "select price from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		" and p.areaid_rate =4 and p.areaid = 0 and g.goods_id=p.goods_id and  g.promote_start_date <= $now and g.promote_end_date >= $now  and p.price_type = 'promote_price' ";
	}else
	{
		$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		" and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
		$sql = "select price from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
		"  and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.promote_start_date <= $now and g.promote_end_date >= $now  and p.price_type = 'promote_price' ";
	}
	
	$promote_price_t = $GLOBALS['db']->getOne($sql);
	
	if(!empty($promote_price_t)&&$promote_price_t>0)
	{
		if($zkxs_price>$promote_price_t)
		{
			$zkxs_price = $promote_price_t;
		}
	}
	
	$goods_list_num = $GLOBALS['db']->getRow($sql_v);
	$shop_price_rate1 = 0;
	if ($shop_price>$goods_list_num['price']&&$goods_list_num['price']!=0) {
	
		$shop_price_rate1 =$goods_list_num['price'];
	}
	$shop_price = $price11 + $shop_price;
	$zkxs_price = $zkxs_price+$price11;
	 
	if($dl_pd==1&&$dl_goods==1)
	{
		$res['result'] = 'HKD $ '.($shop_price * $number);
		if($shop_price_rate1>0)
		{
			$res['resultzk_price'] = 'HKD $ '.(($shop_price_rate1 * $number)+$price11);
		}else
		{
			$res['resultzk_price'] = 'HKD $ '.($shop_price * $number);
		}
		 
	}else
	{
		if($shop_price_rate1>0)
		{
			$res['resultzk_price'] = price_format(($shop_price_rate1 * $number)+$price11);
		}else
		{
			$res['resultzk_price'] = price_format($zkxs_price * $number);
		}
		 
		 
		$res['result'] = price_format($shop_price * $number);
	}
	
	$res['result_price'] = ceil($shop_price * $number);
	//最终价格
	die($json->encode($res));
}
/*---end 首页属性切换*/
/*-------------------------------------------------------*/
/*-------------------------------------------------------*/
/*---start 商品详情页属性切换*/
if($_REQUEST['act'] == 'price')
{
	include('includes/cls_json.php');

    $json   = new JSON;
    $res    = array('err_msg' => '', 'result' => '', 'qty' => 1);

    $attr_id    = isset($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array();
    
    $number     = (isset($_REQUEST['number'])) ? intval($_REQUEST['number']) : 1;
    $xuanze = $_REQUEST['xuanze'];
    $sattr_id    = isset($_REQUEST['mxuanze']) ? explode(',', $_REQUEST['mxuanze']) : array();
    $rank_xss = (isset($_REQUEST['rank_xss'])) ? intval($_REQUEST['rank_xss']) : 0;
    $att='';
    $att1 = '';
    if ($goods_id == 0)
    {
        $res['err_msg'] = $_LANG['err_change_attr'];
        $res['err_no']  = 1;
    }
    else
    {
    	
    	$mes = '';
    	 
    	if(1==1)  //属性筛选
    	{
    		
    		$messageall = get_goods_properties_two($goods_id,$xuanze,$sattr_id);
    		$peiprice_new = 0;
    		$p_string = '';
    		$p_strings = '';
    		foreach ($messageall['spe'] as $key=>$value)
    		{
    			$mes =$mes. '<div class="item"><div class="name">'.$value['name'].'</div>';
    			foreach ($value['values'] as $v)
    			{
    				$mes = $mes.' <div class="type ';
    				if($v['xianshi'] == 1)
    				{
    					if($v['css'] == 1)
    					{
    						$mes = $mes.'active "';
    					}else 
    					{
    						$mes = $mes.'"';
    					}
    					$mes = $mes.'onclick="changeP(\'spec_'.$key.'\',\''.$v['id'].'\',\''.$v['label'].'\',this,'.$v['id'].','.$key.',\''.$peiprice.'\')"';
    				}else 
    				{
    					$mes = $mes.'disable "';
    				}
    				$mes = $mes.'>'.$v['label'].'</div>';
    				$mes = $mes.'<input style="display:none" id="spec_value_'.$v['id'].'" type="radio" name="spec_'.$key.'" value="'.$v['id'].'"';
				if($v['css'] == 1)
				{
					$att1 = $v['id'].'|';
					$p_string = $p_string.'|'.$v['id'];
					$p_strings = $p_strings.','.$v['id'];
					$mes = $mes.'checked ';
				}
				$mes = $mes.'> ';
    			}
    			$att = $att.$att1;
    			$mes = $mes.'</div>';
    		}
    	if($att)
    	{
    		$att = substr($att,0,strlen($att)-1);
    	}
    		
    		$res['message_pan'] = 1;
    		$res['message_t'] = $mes;
    		$p_string = substr($p_string, 1);
    		$p_strings = substr($p_strings, 1);
    		$sql="select product_sn,product_sn_code,areaid from ".$GLOBALS['ecs']->table('products')." where goods_id =".$goods_id." and areaid =".$_SESSION['yuming']." and 	goods_attr ='".$p_string."'";
    		 
    		$products_list_p = $GLOBALS['db']->getRow($sql);
    		 
    		 
    		$products_sn = $products_list_p['product_sn'];
    		$product_sn_code = $products_list_p['product_sn_code'];
    		 
    	}
        if ($number == 0)
        {
            $res['qty'] = $number = 1;
        }
        else
        {
            $res['qty'] = $number;
        }
       // $products_sn = 0;
        $areaid_p = $_SESSION['yuming'];
        //该地区各店铺库存
        $sql = "select a.areaname, p.product_number as p_number,p.kc_number as kc_number,p.warn_number as warn_number ,r.area_name as rate_name from ".$GLOBALS['ecs']->table('products')." as p, ".$GLOBALS['ecs']->table('area_exchange_rate')." as r  , ".$GLOBALS['ecs']->table('area').
            " as a where a.state=1 and p.areaid=a.areaid and  a.areaid!=0 and r.enabled=1 and  r.area_exchange_rate_id=a.AreaExchangeRateId and p.product_sn = '".$products_sn."' and p.goods_id = ".$goods_id
        	."  group by a.areaname order by rate_name ";
		
        $rate_num = $GLOBALS['db']->getAll($sql);

		$sql = "select js_yd from ".$GLOBALS['ecs']->table('goods')." where goods_id=".$goods_id;
		$js_yd = $GLOBALS['db']->getOne($sql);

		$sql = "select sum(og.goods_number) as goods_number from ".$GLOBALS['ecs']->table('order_info')." as oi,"
			.$GLOBALS['ecs']->table('area')." as a,".$GLOBALS['ecs']->table('area_exchange_rate')." as ae, ".$GLOBALS['ecs']->table('order_goods').
			" as og where og.goods_attr_id='".$p_strings."' and  oi.order_id=og.order_id and a.AreaExchangeRateId=ae.area_exchange_rate_id and oi.areaid=a.areaid  and oi.order_status=1 and oi.shipping_status=0 and og.goods_id=".$goods_id." and ae.area_exchange_rate_id=".$_SESSION['area_rate_id'];
		$order_number = $GLOBALS['db']->getOne($sql);
        
        $str = '門市庫存：';
        $xs_kc = '';
        $dq_pd = '';
        $i=0;
        $p_kc = 0;
        $warn_number = 0;
        $kc_number = 0;

		//查仓库库存
		$sql = "select value from ".$GLOBALS['ecs']->table('shop_config')." where code='webpos_storehouse'";
		$ves = $GLOBALS['db']->getOne($sql);


		$sql = "select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where product_status=1 and goods_id=$goods_id and areaid in ($ves) and product_sn = '".$products_sn."'";
		$rate_c_number = $GLOBALS['db']->getOne($sql);
		$date_s = date('m月d日',strtotime('+4 day'));
        foreach($rate_num as $value){
			$value['p_number'] = $value['p_number']-$order_number;

        	if($dq_pd!=$value['rate_name'])
        	{
        		if($i==1)
        		{
        			$xs_kc = $xs_kc.'</div></div>';
        		}
        		$xs_kc = $xs_kc.'<div class="area"><div class="item">'.$value['rate_name']."</div>".'<div class="item">';
        	}
        	$i=1;
			
				if ($value['p_number']>0/* && empty($js_yd)*/) {
					
					$xs_kc = $xs_kc . '<span class="store">' . $value['areaname'] . "(有庫存)</span>";
					$str = $str . '&nbsp&nbsp' . $value['areaname'] . '(有庫存)';
					
				} else {
					if(!empty($rate_c_number)){
						$xs_kc = $xs_kc.'<span class="store">'.$value['areaname']."<span >(下單後立即出貨)</span></span>";
					
					}else 
					{
						$xs_kc = $xs_kc . '<span class="store">' . $value['areaname'] . "<span class='red'>(下單後預計".$date_s."後出貨)</span></span>";
					}
				}
			
			if ($value['p_number'] && empty($js_yd)) {
				$p_kc = 1;
			}
            if($value['warn_number'])
            {
            	$warn_number = 1;
            }else 
            {
            	
            }
            if($value['kc_number'])
            {
            	$kc_number = 1;
            }
           
            $dq_pd = $value['rate_name'];
        }
        $xs_kc = $xs_kc.'</div></div>';
        $res['p_kc'] = $p_kc;
        $res['warn_number'] = $warn_number;
        $res['kc_xs'] = $xs_kc;
        $res['kc_number'] = $kc_number;
        $res['rate_number'] = $str;
		$res['js_yd'] = $js_yd;
        
        $shop_price  = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区促销价格;
        $dl_pd = 0;
        if($_SESSION['user_id'])
        {
        	$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
        	$dl_pd = $GLOBALS['db']->getOne($sql);
        }
        $dl_goods = 0;
        $sql = " SELECT dl_goods FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$goods_id;
        $dl_goods = $GLOBALS['db']->getOne($sql);
        
        
        $where = db_create_in($attr_id, 'hd_id');
        if($dl_pd==1&&$dl_goods==1)
        {
        	$where = $where ." AND price_type='attr_price' "." AND goods_id='".$goods_id."'" ." and areaid=0 AND  areaid_rate=4 ";//代理登陆且是代理商品取香港价格
        }else
        {
        	$where = $where ." AND price_type='attr_price' "." AND goods_id='".$goods_id."'" ." and areaid=0 AND  areaid_rate='".$_SESSION['area_rate_id']."'";
        }
        
        $sql = 'SELECT SUM(price) AS attr_price FROM ' . $GLOBALS['ecs']->table('price_area') . " WHERE $where";
        $price11 = floatval($GLOBALS['db']->getOne($sql));
        $res['result_rank_p'] = 0;
       
        	$res['result_rank_p'] = 1;
        	if($dl_pd==1&&$dl_goods==1)
        	{
        		$sql = " SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$goods_id." AND areaid=0 AND  areaid_rate=4 ";//代理登陆且是代理商品取香港价格
        	}else
        	{
        		$sql = " SELECT price FROM ".$ecs->table('price_area')." WHERE price_type='shop_price' and goods_id=".$goods_id." AND areaid=0 AND  areaid_rate='".$_SESSION['area_rate_id']."'";
        	}
        	
        	$yuanprice = $db->getOne($sql);
        	//$yuanprice = $shop_price;
        	//$yuanprice = $yuanprice+$price11;
        	$sql = "SELECT rank_id,  r.rank_name, r.discount,r.dl_discount,r.dl_pd " .
            'FROM ' . $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " .
            "WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";
    		$user_rank_list = $db->getAll($sql);
    		$rank_price_list = '   ';
		    $res['result_rank_mes'] = '';
    		foreach ($user_rank_list as $key=>$value)
    		{
    			if($dl_pd==1&&$dl_goods==1)
    			{
    				$userprice =floatval(get_price_area($goods_id,0,'user_price',$value['rank_id'],0,4));//代理登陆且是代理商品取香港价格
    			}else 
    			{
    				$userprice =floatval(get_price_area($goods_id,0,'user_price',$value['rank_id'],0,$_SESSION['area_rate_id']));
    			}
    			
    			if($value['rank_id'] == 1) {
					$rank_price_list = $rank_price_list . $value['rank_name'] . "折後：";
					if ($value['dl_pd'] == 1)//判断是不是代理会员
					{
						$sql = "SELECT dl_goods FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id=" . $goods_id;
						$dl_goods = $GLOBALS['db']->getOne($sql);

						if ($dl_goods == 1) {
							if ($userprice == -1) {
								$price_u = ceil($price11 * $value['dl_discount'] / 100 + $yuanprice * $value['dl_discount'] / 100);
							} else {
								$price_u = ceil($price11 + $userprice);
							}

						} else {

							if ($userprice == -1) {
								$price_u = ceil($price11 * $value['discount'] / 100 + $yuanprice * $value['discount'] / 100);

							} else {
								$price_u = ceil($price11 + $userprice);
							}


						}
					} else {
						if ($userprice == -1) {
							$price_u = ceil($price11 * $value['discount'] / 100 + $yuanprice * $value['discount'] / 100);
						} else {
							$price_u = ceil($price11 + $userprice);
						}

					}

					if ($_SESSION[user_rank] == $value['rank_id']) {
						$shop_price = $price_u;
					}
					if ($dl_pd == 1 && $dl_goods == 1) {
						$user_rank_list[$key]['price'] = 'HKD $ ' . $price_u;
					} else {
						$user_rank_list[$key]['price'] = price_format($price_u);
					}
					$rank_price_list = $rank_price_list . $user_rank_list[$key]['price'].'  |';

					$res['result_rank_mes'] = $rank_price_list;
				}
        }

		if ($dl_pd == 1 && $dl_goods == 1) {
			$price_p = 'HKD $ ' . ceil($yuanprice+$price11);
		} else {
			$price_p = price_format(ceil($yuanprice+$price11));
		}

		if($rank_xss==1)
		{
			$res['result_rank_mes'] = '  原價:'.$price_p;
		}else 
		{
			$res['result_rank_mes'] = $res['result_rank_mes'].'  原價:'.$price_p;
		}
		    
        
        
        $resss = get_goods_price_small($goods_id);
		
		if(empty($resss)){
			
		}else{
				$mj_price = $resss;
		}
        
        $zkxs_price = $shop_price;
        if($mj_price>0)
        {
        	if($zkxs_price>$mj_price)
        	{
        		$zkxs_price = $mj_price;
        	}
        }
    $now = gmtime();
    
    
    
    	if($dl_pd==1&&$dl_goods==1)
        {
        	$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
        	" and p.areaid_rate =4 and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
        	$sql = "select price from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
        	" and p.areaid_rate =4 and p.areaid = 0 and g.goods_id=p.goods_id and  g.promote_start_date <= $now and g.promote_end_date >= $now  and p.price_type = 'promote_price' ";
        }else 
        {
        	$sql_v="select price,num from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
        	" and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.volume_start_date <= $now and g.volume_end_date >= $now  and (p.price_type = 'volume_price' or p.price_type = 'sn_volume_price') order by price";
        	$sql = "select price from ".$GLOBALS['ecs']->table('price_area')." as p, ".$GLOBALS['ecs']->table('goods')." as g  where p.goods_id =".$goods_id.
        	"  and p.areaid_rate =".$_SESSION['area_rate_id']." and p.areaid = 0 and g.goods_id=p.goods_id and  g.promote_start_date <= $now and g.promote_end_date >= $now  and p.price_type = 'promote_price' ";
        }
        
        $promote_price_t = $GLOBALS['db']->getOne($sql);
        
        if(!empty($promote_price_t)&&$promote_price_t>0)
        {
        	if($zkxs_price>$promote_price_t)
        	{
        		$zkxs_price = $promote_price_t;
        	}
        }
        
        $goods_list_num = $GLOBALS['db']->getRow($sql_v);
        $shop_price_rate1 = 0;
        if ($shop_price>$goods_list_num['price']&&$goods_list_num['price']!=0) {
        	 
        	$shop_price_rate1 =$goods_list_num['price'];
        }
        $shop_price = $price11 + $shop_price;
       $zkxs_price = $zkxs_price+$price11;
       
        if($dl_pd==1&&$dl_goods==1)
        {
        	$res['result'] = 'HKD $ '.($shop_price * $number);
        	if($shop_price_rate1>0)
        	{
        		$res['resultzk_price'] = 'HKD $ '.(($shop_price_rate1 * $number)+$price11);
        	}else 
        	{
        		$res['resultzk_price'] = 'HKD $ '.($shop_price * $number);
        	}
        	
        }else 
        {
        	if($shop_price_rate1>0)
        	{
        		$res['resultzk_price'] = price_format(($shop_price_rate1 * $number)+$price11);
        	}else 
        	{
        		$res['resultzk_price'] = price_format($zkxs_price * $number);
        	}
        	
        	
        	$res['result'] = price_format($shop_price * $number);
        }
        
        $res['result_price'] = ceil($shop_price * $number);
    }
    if($att){
      //  $att=implode( '|', $attr_id);
        $sql="select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where goods_id =".$goods_id." and areaid =".$_SESSION['yuming']." and 	goods_attr ='".$att."'";
        $res['goods_number'] = $GLOBALS['db']->getOne($sql);
        $sql="select product_sn from ".$GLOBALS['ecs']->table('products')." where goods_id =".$goods_id." and areaid =".$_SESSION['yuming']." and 	goods_attr ='".$att."'";
        $goods_product_sn = $GLOBALS['db']->getOne($sql);
        $shop_price_new = $db->getOne("SELECT shop_price FROM ".$ecs->table('goods')." WHERE goods_id='".$goods_id."'");
        $volume_price_list = get_volume_price_list($goods_id, 1,$goods_product_sn,$_SESSION['yuming']);
        if(!empty($volume_price_list)){
        	$res['goods_product_sn'] = '<td>同款優惠</td><td>';
        	foreach ($volume_price_list as $key=>$value)
        	{
        		$g_price = price_format($shop_price_new-$value['price']);
        		$res['goods_product_sn'] = $res['goods_product_sn'] ."滿<strong>".$value['number']."</strong>件，每件减少 <strong>".$g_price."</strong>/件<br />";
        	}
        	$res['goods_product_sn'] = $res['goods_product_sn']."</td>";
        	$res['goods_product_sn_p']  = 1;
        	
        }else 
        {
        	$res['goods_product_sn_p']  = 0;
        }
    }else{
        $sql="select sum(product_number) from ".$GLOBALS['ecs']->table('products')." where goods_id =".$goods_id." and areaid =".$_SESSION['yuming'];
        $res['goods_number'] = $GLOBALS['db']->getOne($sql);
    }
    
     
    if(empty($product_sn_code))
    {
    	$sql="select product_sn,product_sn_code,areaid from ".$GLOBALS['ecs']->table('products')." where goods_id =".$goods_id." and areaid =".$_SESSION['yuming'];
    	$products_list_p = $GLOBALS['db']->getRow($sql);
    	$products_sn = $products_list_p['product_sn'];
    	$product_sn_code = $products_list_p['product_sn_code'];
    }
    $res['message_sn'] = $products_sn;
    $res['message_code'] = $product_sn_code;
   
	
    die($json->encode($res));
}
/*---end 商品详情页属性切换*/
/*-------------------------------------------------------*/
/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

$cache_id = $goods_id . '-' . $_SESSION['user_rank'].'-'.$_SESSION['yuming'].'-'.$_CFG['lang'];
$cache_id = sprintf('%X', crc32($cache_id));
if (!$smarty->is_cached('goods.dwt', $cache_id))
{
    assign_template();

	/* 获得商品的信息 */
	$goods = get_goods_info($goods_id);
	if ($goods === false)
	{
		/* 如果没有找到任何记录则跳回到首页 */
		ecs_header("Location: ./\n");
		exit;
	}
	else{
		if ($goods['brand_id'] > 0)
		{
			$goods['goods_brand_url'] = build_uri('brand', array('bid'=>$goods['brand_id']), $goods['goods_brand']);
		}
		$goods['goods_style_name'] = add_style($goods['goods_name'], $goods['goods_name_style']);
		$shop_price   = $goods['shop_price'];

		$smarty->assign('promotion',       get_promotion_info($goods_id));//促销信息
		$now = gmtime();
		$sql = "SELECT a.act_name FROM " . $GLOBALS['ecs']->table('goods_activity'). " as a,". $GLOBALS['ecs']->table('package_goods')." as p WHERE p.goods_id=".$goods_id." and p.package_id=a.act_id and a.`start_time` <= '$now' AND a.`end_time` >= '$now' AND a.`act_type` = '4' AND a.is_online=1  and a.areaid like '%".$_SESSION['area_rate_id']."%' ORDER BY a.`end_time`";
		$package_promotion = $GLOBALS['db']->getOne($sql);

		$smarty->assign('package_promotion',       $package_promotion);//促销信息
		$smarty->assign('promotion_info', get_promotion_info());

		$dl_pd = 0;
		if($_SESSION['user_id'])
		{
			$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
			$dl_pd = $GLOBALS['db']->getOne($sql);
		}

		if($dl_pd==1&&$goods['dl_goods']==1)
		{
			$volume_price_list = get_volume_price_list($goods['goods_id'], '1',0,4);
		}else
		{
			$volume_price_list = get_volume_price_list($goods['goods_id'], '1',0,$_SESSION['area_rate_id']);
		}
		$smarty->assign('volume_price_list',$volume_price_list);    // 商品优惠价格区间
		
		$resss = get_goods_price_small($goods_id);
		$list = get_promotion_info($goods_id);
		$act_price = array();
		$act_key = array();
		if($dl_pd==1&&$goods['dl_goods']==1)
		{
			$shop_price_rate = get_price_area($goods_id,0,'shop_price',0,0,4);//取地区促销价格
		}else
		{
			$shop_price_rate = get_price_area($goods_id,0,'shop_price',0,0,$_SESSION['area_rate_id']);//取地区促销价格
		}
		
		
		
		$smarty->assign('huodong_list',$list);
		$rank_prices = get_user_rank_prices($goods_id, $shop_price);
		$rank_xs = 0;
		if(empty($resss)){
			$goods['min_price']  = 0;
		}else{
			if($dl_pd==1&&$goods['dl_goods'])
			{
				$goods['min_price'] = 'HKD $ '.$resss;
			}else
			{
				$goods['min_price'] = price_format($resss);
			}
			
			foreach ($rank_prices as $key=>$value)
			{
				
				if($value['price1']>$resss)
				{
					$rank_xs = 1;
				}
			}
			
		}
		$smarty->assign('rank_xs',$rank_xs);
		

		$properties = get_goods_properties_two($goods_id);  // 获得商品的规格和属性

        $properties1 = get_goods_properties_search($goods_id);  // 获得商品的规格和属性
//var_dump($properties['spe']);

        
        $time = gmtime();
        $promote_goods_list = get_promote_goods1(" limit 0,2");//促销活动
        $smarty->assign('promote_goods_list',$promote_goods_list);
        $sql = "SELECT g.goods_id,g.goods_name,g.shop_price,g.goods_thumb FROM ".$ecs->table('goods')." as g  WHERE g.is_delete!=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and g.is_on_sale=1 
        "." and g.area_shop_price like '%".$_SESSION['area_rate_id']."%' "." group by g.goods_id  order by g.goods_id desc limit 0,2 ";
        $volume_goods_list = $db->getAll($sql);//件数优惠
        $smarty->assign('volume_goods_list',$volume_goods_list);
        
        
        $package_goods_list = get_package_list(1,1); //组合活动  先取一个组合占位
        if($package_goods_list)
        {
        	$package_goods_lists = $package_goods_list[0];
        }
        
        $smarty->assign('package_goods_list',$package_goods_lists);
        
        $smarty->assign('dqurl',   '&text=我想查詢產品：'.$goods['goods_name']);
        
        $smarty->assign('properties1',          $properties1);

		$smarty->assign('properties',          $properties['pro']);                              // 商品属性
		$smarty->assign('specification',       $properties['spe']);                              // 商品规格
		$smarty->assign('pictures',            get_goods_gallery($goods_id));                    // 商品相册
		$smarty->assign('rank_prices',         $rank_prices);    // 会员等级价格
		$smarty->assign('fittings',            get_goods_fittings_one($goods_id));                   // 配件
		$fitting = get_goods_fittings_one($goods_id);
		$fitting_value = array();
		$f_goods_price=0;
		$f_shop_price=0;
		$f_goods_id=array();
		foreach($fitting as $v){
			$f_goods_price = $f_goods_price + $v['fittings_price_value'];
			$f_shop_price = $f_shop_price + $v['shop_price_value'];
			$f_goods_id[]=$v['goods_id'];
		}
		$fitting_value['goods_price'] = $f_goods_price;
		$fitting_value['shop_price'] = $f_shop_price;
		$fitting_value['number'] = count($fitting);
		$fitting_value['goods_id'] = implode(',',$f_goods_id);
		$smarty->assign('f_goods',              $fitting_value);

		
		$smarty->assign('goods',              $goods);
        $smarty->assign('goods_id',              $goods_id);

		$smarty->assign('keywords',           htmlspecialchars($goods['keywords']));
		$smarty->assign('description',        htmlspecialchars($goods['goods_brief']));

		$position = assign_ur_here($goods['cat_id'], $goods['goods_name']);

		/* current position */
		$smarty->assign('page_title',          $position['title']);                    // 页面标题

        $smarty->assign('id',           $goods_id);
        $smarty->assign('type',         0);
        $smarty->assign('comment_count', $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('comment'). " WHERE id_value = $goods_id AND comment_type = 0 AND status = 1 AND parent_id = 0"));

        $smarty->assign('bonus_img',       get_bonus_img());       // 現金券圖牿
       // $smarty->assign('activity_show',get_activity_show1());//買几送優惠
    }

	assign_dynamic('goods');
}

/* 记录浏览历史 */
if (!empty($_COOKIE['ECS']['history']))
{
    $history = explode(',', $_COOKIE['ECS']['history']);

    array_unshift($history, $goods_id);
    $history = array_unique($history);

    while (count($history) > $_CFG['history_number'])
    {
        array_pop($history);
    }

    setcookie('ECS[history]', implode(',', $history), gmtime() + 3600 * 24 * 30);
}
else
{
    setcookie('ECS[history]', $goods_id, gmtime() + 3600 * 24 * 30);
}


/* 更新点击次数 */
$db->query('UPDATE ' . $ecs->table('goods') . " SET click_count = click_count + 1 WHERE goods_id = '$_REQUEST[id]'");

$smarty->display('goods.dwt',      $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得指定商品的关联商品
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_linked_goods1($goods_id)
{
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            'FROM ' . $GLOBALS['ecs']->table('link_goods') . ' lg ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = lg.link_goods_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE lg.goods_id = '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
            "LIMIT " . $GLOBALS['_CFG']['related_goods_number'];
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$row['goods_id']]['goods_id']     = $row['goods_id'];
        $arr[$row['goods_id']]['goods_name']   = $row['goods_name'];
        $arr[$row['goods_id']]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
        $arr[$row['goods_id']]['url']          = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

        if ($row['promote_price'] > 0)
        {
            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
        }
        else
        {
            $arr[$row['goods_id']]['promote_price'] = 0;
        }
        $row['shop_price'] = get_price_area($row['goods_id'],0,'shop_price',0,0,$_SESSION['area_rate_id']);
		$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
		if($promote_price > 0)
		{
			//$_SESSION['area_rate_id']

			if($_SESSION['area_rate_id'] > 0)
			{
				$promote_price = floatval(get_price_area($row['goods_id'],0,'promote_price',0,0,$_SESSION['area_rate_id']));//取地区促销价格
			}
		}

		$volume_price_list = get_volume_price_list($row['goods_id'], '1',0,$_SESSION['area_rate_id']);

		$n = count($volume_price_list);
		if(!empty($volume_price_list)) {
			if (!empty($promote_price)) {
				$a = array(0 => $row['shop_price'], 1 => $promote_price, 2 => $volume_price_list[$n - 1]['price']);
			} else {
				$a = array(0 => $row['shop_price'], 1 => $volume_price_list[$n - 1]['price']);
			}
		}else{
			if (!empty($promote_price)) {
				$a = array(0 => $row['shop_price'], 1 => $promote_price);
			} else {
				$a = array(0 => $row['shop_price']);
			}
		}
		$pos = array_search(min($a), $a);
		$arr[$row['goods_id']]['min_price'] = price_format($a[$pos]);
    }

    return $arr;
}

/**
 * 获得指定商品的关联文章
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  void
 */
function get_linked_articles($goods_id)
{
    $sql = 'SELECT a.article_id, a.title, a.file_url, a.open_type, a.add_time ' .
            'FROM ' . $GLOBALS['ecs']->table('goods_article') . ' AS g, ' .
                $GLOBALS['ecs']->table('article') . ' AS a ' .
            "WHERE g.article_id = a.article_id AND g.goods_id = '$goods_id' AND a.is_open = 1 " .
            'ORDER BY a.add_time DESC';
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['url']         = $row['open_type'] != 1 ?
            build_uri('article', array('aid'=>$row['article_id']), $row['title']) : trim($row['file_url']);
        $row['add_time']    = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);
        $row['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
            sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];

        $arr[] = $row;
    }

    return $arr;
}



/**
 * 获得购买过该商品的人还买过的商品
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_also_bought($goods_id)
{
    $sql = 'SELECT COUNT(b.goods_id ) AS num, g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            'FROM ' . $GLOBALS['ecs']->table('order_goods') . ' AS a ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('order_goods') . ' AS b ON b.order_id = a.order_id ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = b.goods_id ' .
            "WHERE a.goods_id = '$goods_id' AND b.goods_id <> '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .
            'GROUP BY b.goods_id ' .
            'ORDER BY num DESC ' .
            'LIMIT ' . $GLOBALS['_CFG']['bought_goods'];
    $res = $GLOBALS['db']->query($sql);

    $key = 0;
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$key]['goods_id']    = $row['goods_id'];
        $arr[$key]['goods_name']  = $row['goods_name'];
        $arr[$key]['short_name']  = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$key]['goods_img']   = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$key]['shop_price']  = price_format($row['shop_price']);
        $arr[$key]['url']         = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

        if ($row['promote_price'] > 0)
        {
            $arr[$key]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$key]['formated_promote_price'] = price_format($arr[$key]['promote_price']);
        }
        else
        {
            $arr[$key]['promote_price'] = 0;
        }

        $key++;
    }

    return $arr;
}

/**
 * 获得指定商品的销售排名
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  integer
 */
function get_goods_rank($goods_id)
{
    /* 统计时间段 */
    $period = intval($GLOBALS['_CFG']['top10_time']);
    if ($period == 1) // 一年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
    }
    elseif ($period == 2) // 半年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
    }
    elseif ($period == 3) // 三个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
    }
    elseif ($period == 4) // 一个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
    }
    else
    {
        $ext = '';
    }

    /* 查询该商品销量 */
    $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
        'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
            $GLOBALS['ecs']->table('order_goods') . ' AS g ' .
        "WHERE o.order_id = g.order_id " .
        "AND o.order_status = '" . OS_CONFIRMED . "' " .
        "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
        " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) .
        " AND g.goods_id = '$goods_id'" . $ext;
    $sales_count = $GLOBALS['db']->getOne($sql);

    if ($sales_count > 0)
    {
        /* 只有在商品销售量大于0时才去计算该商品的排行 */
        $sql = 'SELECT DISTINCT SUM(goods_number) AS num ' .
                'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
                    $GLOBALS['ecs']->table('order_goods') . ' AS g ' .
                "WHERE o.order_id = g.order_id " .
                "AND o.order_status = '" . OS_CONFIRMED . "' " .
                "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
                " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . $ext .
                " GROUP BY g.goods_id HAVING num > $sales_count";
        $res = $GLOBALS['db']->query($sql);

        $rank = $GLOBALS['db']->num_rows($res) + 1;

        if ($rank > 10)
        {
            $rank = 0;
        }
    }
    else
    {
        $rank = 0;
    }

    return $rank;
}

/**
 * 获得商品选定的属性的附加总价格
 *
 * @param   integer     $goods_id
 * @param   array       $attr
 *
 * @return  void
 */
function get_attr_amount($goods_id, $attr)
{
    $sql = "SELECT SUM(attr_price) FROM " . $GLOBALS['ecs']->table('goods_attr') .
        " WHERE goods_id='$goods_id' AND " . db_create_in($attr, 'goods_attr_id');

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 取得跟商品关联的礼包列表
 *
 * @param   string  $goods_id    商品编号
 *
 * @return  礼包列表
 */
function get_package_goods_list($goods_id)
{
    $now = gmtime();
    $sql = "SELECT pg.goods_id, ga.act_id, ga.act_name, ga.act_desc, ga.goods_name, ga.start_time,
                   ga.end_time, ga.is_finished, ga.ext_info
            FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS ga, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
            WHERE pg.package_id = ga.act_id
            AND ga.start_time <= '" . $now . "'
            AND ga.end_time >= '" . $now . "'
            AND pg.goods_id = " . $goods_id . "
            GROUP BY ga.act_id
            ORDER BY ga.act_id ";
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $tempkey => $value)
    {
        $subtotal = 0;
        $row = unserialize($value['ext_info']);
        unset($value['ext_info']);
        if ($row)
        {
            foreach ($row as $key=>$val)
            {
                $res[$tempkey][$key] = $val;
            }
        }

        $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, p.goods_attr, g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price
                FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg
                    LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g
                        ON g.goods_id = pg.goods_id
                    LEFT JOIN ". $GLOBALS['ecs']->table('products') . " AS p
                        ON p.product_id = pg.product_id
                    LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp
                        ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'
                WHERE pg.package_id = " . $value['act_id']. "
                ORDER BY pg.package_id, pg.goods_id";

        $goods_res = $GLOBALS['db']->getAll($sql);

        foreach($goods_res as $key => $val)
        {
            $goods_id_array[] = $val['goods_id'];
            $goods_res[$key]['goods_thumb']  = get_image_path($val['goods_id'], $val['goods_thumb'], true);
            $goods_res[$key]['market_price'] = price_format($val['market_price']);
            $goods_res[$key]['rank_price']   = price_format($val['rank_price']);
            $subtotal += $val['rank_price'] * $val['goods_number'];
        }

        /* 取商品属性 */
        $sql = "SELECT ga.goods_attr_id, ga.attr_value
                FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a
                WHERE a.attr_id = ga.attr_id
                AND a.attr_type = 1
                AND " . db_create_in($goods_id_array, 'goods_id');
        $result_goods_attr = $GLOBALS['db']->getAll($sql);

        $_goods_attr = array();
        foreach ($result_goods_attr as $value)
        {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }

        /* 处理货品 */
        $format = '[%s]';
        foreach($goods_res as $key => $val)
        {
            if ($val['goods_attr'] != '')
            {
                $goods_attr_array = explode('|', $val['goods_attr']);

                $goods_attr = array();
                foreach ($goods_attr_array as $_attr)
                {
                    $goods_attr[] = $_goods_attr[$_attr];
                }

                $goods_res[$key]['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));
            }
        }

        $res[$tempkey]['goods_list']    = $goods_res;
        $res[$tempkey]['subtotal']      = price_format($subtotal);
        $res[$tempkey]['saving']        = price_format(($subtotal - $res[$tempkey]['package_price']));
        $res[$tempkey]['package_price'] = price_format($res[$tempkey]['package_price']);
    }

    return $res;
}

?>