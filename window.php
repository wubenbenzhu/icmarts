<?php
/*
 定时执行文件
 */
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
ini_set('max_execution_time','100');
$top10_time = '';
/* 排行统计的时间 */
switch (4)
{
    case 1: // 一年
        $t = gmtime() - 365 * 86400;
        $top10_time = " AND o.add_time >= ".$t;
        break;
    case 2: // 半年
        $t = gmtime() - 180 * 86400 ;
        $top10_time = " AND o.add_time >= ".$t ;
        break;
    case 3: // 三个月
        $t = gmtime() - 90 * 86400 ;
        $top10_time = " AND o.add_time >= ".$t ;
        break;
    case 4: // 一个月
        $t = gmtime() - 30 * 86400 ;
        $top10_time = " AND o.add_time >= ".$t ;
        break;
    default:
        $top10_time = " AND o.add_time >=  0";
}


$sql = "select goods_id, sum(og.goods_number) as gds_number from ".$GLOBALS['ecs']->table('order_info')."as o, ".$GLOBALS['ecs']->table('order_goods') .
    " as og where o.order_id = og.order_id and o.pay_status=2 and og.product_id > 0 $top10_time group by goods_id order by gds_number desc limit 0, 50";

$list = $GLOBALS['db']->getAll($sql);

//先更新为0

$sql = "UPDATE ".$GLOBALS['ecs']->table('goods')." set top_number = 0 where top_number > 0";

$GLOBALS['db']->query($sql);

foreach($list as $v){
    $sql = "UPDATE ".$GLOBALS['ecs']->table('goods')." set top_number = ".$v['gds_number']." where goods_id = ".$v['goods_id'];
    $GLOBALS['db']->query($sql);
}


//根据模板生成网页内容
$dir = 'themes/new_chaoliu/static_html';
if (!file_exists($dir)){
    mkdir ($dir,0777,true);
    //echo '创建文件夹成功';
}

$rate = array(1,4);

foreach($rate as $r) {
    $smarty->assign('activity_show_all', get_activity_show_static($r));//買几送優惠
    $time = gmtime();
    $promote_goods_list = get_promote_goods1(" limit 0,3");//促销活动
    $smarty->assign('promote_goods_list', $promote_goods_list);
    if ($promote_goods_list) {
        $smarty->assign('promote_goods_listone', $promote_goods_list[0]);
    }
    $sql = "SELECT g.goods_id,g.goods_name,g.shop_price,g.goods_thumb,g.volume_start_date,g.volume_end_date,p.id FROM " . $ecs->table('price_area') . " as p, " . $ecs->table('goods') . " as g  WHERE g.is_delete!=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and g.is_on_sale=1 and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=" . $r . " and g.area_shop_price like '%" . $r . "%' " . " group by g.goods_id  order by p.id desc limit 0,3 ";
    $volume_goods_list = $db->getAll($sql);//件数优惠
    $smarty->assign('volume_goods_list', $volume_goods_list);
    if ($volume_goods_list) {
        $volume_goods_listone = $volume_goods_list[0];
        $volume_goods_listone['volume_start_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_start_date']);
        $volume_goods_listone['volume_end_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_end_date']);

        $smarty->assign('volume_goods_listone', $volume_goods_listone);
    }


    $file = 'themes/new_chaoliu/static_html/activity_' . $r . '.html';//静态网页文件名
    $content = $smarty->make_html('static_dwt/activity.dwt');//根据index.dwt模板生成网页内容
    $filename = ROOT_PATH . $file;//静态网页路径
    file_put_contents($filename, $content);//生成文件
}
foreach($rate as $r) {
    $smarty->assign('activity_show_all', get_activity_show_static($r));//買几送優惠
    $time = gmtime();
    $promote_goods_list = get_promote_goods1(" limit 0,3");//促销活动
    $smarty->assign('promote_goods_list', $promote_goods_list);
    if ($promote_goods_list) {
        $smarty->assign('promote_goods_listone', $promote_goods_list[0]);
    }
    $sql = "SELECT g.goods_id,g.goods_name,g.shop_price,g.goods_thumb,g.volume_start_date,g.volume_end_date,p.id FROM " . $ecs->table('price_area') . " as p, " . $ecs->table('goods') . " as g  WHERE g.is_delete!=1 and g.volume_start_date <= $time and g.volume_end_date >= $time and g.is_on_sale=1 and (p.price_type='volume_price' or p.price_type='sn_volume_price') and g.goods_id=p.goods_id and p.areaid=0 and p.areaid_rate=" . $r . " and g.area_shop_price like '%" . $r . "%' " . " group by g.goods_id  order by p.id desc limit 0,3 ";
    $volume_goods_list = $db->getAll($sql);//件数优惠
    $smarty->assign('volume_goods_list', $volume_goods_list);
    if ($volume_goods_list) {
        $volume_goods_listone = $volume_goods_list[0];
        $volume_goods_listone['volume_start_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_start_date']);
        $volume_goods_listone['volume_end_date'] = local_date($GLOBALS['_CFG']['date_format'], $volume_goods_listone['volume_end_date']);

        $smarty->assign('volume_goods_listone', $volume_goods_listone);
    }
    $package_goods_list = get_package_list(1, 10); //组合活动  先取一个组合占位
    $smarty->assign('package_goods_list', $package_goods_list);

    $file1 = 'mobile/templates/static_html/activity_' . $r . '.html';//静态网页文件名
    $content1 = $smarty->make_html('../../mobile/templates/static_dwt/activity_mobile.dwt');//根据index.dwt模板生成网页内容
    $filename1 = ROOT_PATH . $file1;//静态网页路径
    file_put_contents($filename1, $content1);//生成文件
}

foreach($rate as $r) {

$new_goods_list = array_chunk(get_recommend_goods_w('new','',24,$r), 8);

$smarty->assign('new_goods',      $new_goods_list[0]);     // 最新商哿
if(isset($new_goods_list[1]))
$smarty->assign('new_goodss',      $new_goods_list[1]);     // 最新商哿
if(isset($new_goods_list[2]))
$smarty->assign('new_goodsss',      $new_goods_list[2]);     // 最新商哿
$file3 = 'themes/new_chaoliu/static_html/new_' . $r . '.html';//静态网页文件名
$content3 = $smarty->make_html('static_dwt/new.dwt');//根据index.dwt模板生成网页内容
$filename3 = ROOT_PATH . $file3;//静态网页路径
file_put_contents($filename3, $content3);//生成文件
}

foreach($rate as $r) {

	$new_goods_list = array_chunk(get_recommend_goods_w('new','',24,$r), 8);

	$smarty->assign('new_goods',      $new_goods_list[0]);     // 最新商哿
	if(isset($new_goods_list[1]))
		$smarty->assign('new_goodss',      $new_goods_list[1]);     // 最新商哿
	if(isset($new_goods_list[2]))
		$smarty->assign('new_goodsss',      $new_goods_list[2]);     // 最新商哿
	$file2 = 'mobile/templates/static_html/new_' . $r . '.html';//静态网页文件名
    $content2 = $smarty->make_html('../../mobile/templates/static_dwt/new1.dwt');//根据index.dwt模板生成网页内容
    $filename2 = ROOT_PATH . $file2;//静态网页路径
    file_put_contents($filename2, $content2);//生成文件
}
//$file = 'themes/new_chaoliu/static_html/menu_act_'.$_SESSION['area_rate_id'].'.html';//静态网页文件名
//$content = $smarty->make_html('static_dwt/menu_act.dwt');//根据index.dwt模板生成网页内容
//$filename = ROOT_PATH . $file;//静态网页路径
//file_put_contents($filename, $content);//生成文件
//*******************************************************************************************************************************
foreach($rate as $r) {
	$sql = "select * from ".$GLOBALS['ecs']->table('category')." WHERE is_show=1 and parent_id=0 and icmarts_index=1 order by sort_order  asc";
	$parent_cat = $GLOBALS['db']->getAll($sql);
	foreach ($parent_cat as $key=>$value)
	{
		$parent_cat[$key]['goods_list'] = assign_cat_goods_hot($value['cat_id'],4,'web',$r);
		if(empty($parent_cat[$key]['goods_list'] ))
		{
			unset($parent_cat[$key]);
		}
	
	
	}
	$smarty->assign('parent_cat',        $parent_cat);
	$file4 = 'mobile/templates/static_html/best_' . $r . '.html';//静态网页文件名
	$content4 = $smarty->make_html('../../mobile/templates/static_dwt/best1.dwt');//根据index.dwt模板生成网页内容
	$filename4 = ROOT_PATH . $file4;//静态网页路径
	file_put_contents($filename4, $content4);//生成文件
}
//*******************************************************************************************************************************

foreach($rate as $r) {
//*******************************************************************************************************************************
$sql = "select * from ".$GLOBALS['ecs']->table('category')." WHERE is_show=1 and parent_id=0 and icmarts_index=1 order by sort_order  asc";
$parent_cat = $GLOBALS['db']->getAll($sql);
foreach ($parent_cat as $key=>$value)
{
	$parent_cat[$key]['goods_list'] = assign_cat_goods_hot($value['cat_id'],8,'web',$r);
	if(empty($parent_cat[$key]['goods_list'] ))
	{
		unset($parent_cat[$key]);
	}
	 
	 
}
$smarty->assign('parent_cat',        $parent_cat);

$file3 = 'themes/new_chaoliu/static_html/best_' . $r . '.html';//静态网页文件名
$content3 = $smarty->make_html('static_dwt/best.dwt');//根据index.dwt模板生成网页内容
$filename3 = ROOT_PATH . $file3;//静态网页路径
file_put_contents($filename3, $content3);//生成文件
//*******************************************************************************************************************************
}

function get_activity_show_static($rate)
{
    $list = array();
    $sql ="SELECT * FROM ". $GLOBALS['ecs']->table('favourable_activity') . " where is_online=0 and  "
        .gmtime()." >= start_time and ".gmtime()." <= end_time   and areaid like '%".$rate."%' ORDER BY sort_order ASC,act_id DESC ";

    $list = $GLOBALS['db']->getAll($sql);
    foreach ($list as $key=>$value)
    {
        $list[$key]['start_time'] = local_date('Y/m/d', $value['start_time']);
        $list[$key]['end_time'] = local_date('Y/m/d', $value['end_time']);
        if(empty($value['favourable_logo'])||$value['favourable_logo']=='')
        {
            if($value['act_type']==0)//赠品活动
            {

            }
            $goods_act = '';
            if(!empty($value['act_range_ext'])){
              
                
                $sql = "select goods_id from ".$GLOBALS['ecs']->table('goods')." where is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 and area_shop_price like '%".$rate."%' and goods_thumb<>'' and goods_id in (".$value['act_range_ext'].")   ORDER BY RAND() LIMIT 3";
                $ag = $GLOBALS['db']->getAll($sql);
                $aa = array();
                foreach($ag as $v){
                	$aa[]=$v['goods_id'];
                }
                $goods_act = implode(',',$aa);
            }else{
                $sql = "select goods_id from ".$GLOBALS['ecs']->table('goods')." where is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 and area_shop_price like '%".$rate."%'  and goods_thumb<>'' ORDER BY RAND() LIMIT 3";
                $ag = $GLOBALS['db']->getAll($sql);
                $aa = array();
                foreach($ag as $v){
                    $aa[]=$v['goods_id'];
                }
                $goods_act = implode(',',$aa);
            }
            if(!empty($goods_act)) {
                $sql = " SELECT goods_thumb FROM ".$GLOBALS['ecs']->table('goods')." where goods_id in(".$goods_act.")";
                $list[$key]['goods_list'] = $GLOBALS['db']->getAll($sql);
            }
        }
    }

    return $list;
}


function get_recommend_goods_w($type = '', $cats = '',$number=0,$rate=0)
{
	if (!in_array($type, array('best', 'new', 'hot')))
	{
		return array();
	}
	$where = '';
	
	if($rate>0){
		$where .= " and g.area_shop_price like '%".$rate."%' "; //显示该地区价格
	}
	
	//取不同推荐对应的商品
	$type_goods = array();
	if (empty($type_goods[$type]))
	{
			
		//初始化数据
		$type_goods['best'] = array();
		$type_goods['new'] = array();
		$type_goods['hot'] = array();
		$data = false;

		if ($data === false)
		{
			
			$sql = 'SELECT g.goods_id, g.is_best, g.is_new, g.is_hot, g.is_promote, b.brand_name,g.sort_order ' .
					' FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
					' LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
					' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND (g.is_best = 1 OR g.is_new =1 OR g.is_hot = 1)'.$where.
					' ORDER BY g.sort_order, g.last_update DESC';

			$goods_res = $GLOBALS['db']->getAll($sql);

			//定义推荐,最新，热门，促销商品
			$goods_data['best'] = array();
			$goods_data['new'] = array();
			$goods_data['hot'] = array();
			$goods_data['brand'] = array();
			if (!empty($goods_res))
			{
				foreach($goods_res as $data)
				{
					if ($data['is_best'] == 1)
					{
						$goods_data['best'][] = array('goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']);
					}
					if ($data['is_new'] == 1)
					{
						$goods_data['new'][] = array('goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']);
					}
					if ($data['is_hot'] == 1)
					{
						$goods_data['hot'][] = array('goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']);
					}
					if ($data['brand_name'] != '')
					{
						$goods_data['brand'][$data['goods_id']] = $data['brand_name'];
					}
				}
			}
			write_static_cache('recommend_goods', $goods_data);
		}
		else
		{
			$goods_data = $data;
		}

		$time = gmtime();
		$order_type = $GLOBALS['_CFG']['recommend_order'];

		//按推荐数量及排序取每一项推荐显示的商品 order_type可以根据后台设定进行各种条件显示
		static $type_array = array();
		$type2lib = array('best'=>'recommend_best', 'new'=>'recommend_new', 'hot'=>'recommend_hot');
		if (empty($type_array))
		{
			foreach($type2lib as $key => $data)
			{
				if (!empty($goods_data[$key]))
				{

					if(empty($number)){
						$num = get_library_number($data);
					}else{
						$num = $number;
					}

					//$num = get_library_number($data);


					$data_count = count($goods_data[$key]);

					$num = $data_count > $num  ? $num : $data_count;
					if ($order_type == 0)
					{
						//usort($goods_data[$key], 'goods_sort');
						$rand_key = array_slice($goods_data[$key], 0, $num);
						foreach($rand_key as $key_data)
						{
							$type_array[$key][] = $key_data['goods_id'];
						}
					}
					else
					{
						$rand_key = array_rand($goods_data[$key], $num);
						if ($num == 1)
						{
							$type_array[$key][] = $goods_data[$key][$rand_key]['goods_id'];
						}
						else
						{
							foreach($rand_key as $key_data)
							{
								$type_array[$key][] = $goods_data[$key][$key_data]['goods_id'];
							}
						}
					}
				}
				else
				{
					$type_array[$key] = array();
				}
			}
		}

		//取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
		$sql = 'SELECT g.goods_id,g.dl_goods, g.goods_name,g.goods_name_en,g.volume_start_date,g.volume_end_date, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
				"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
				"promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, RAND() AS rnd " .
				'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
				"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
				"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ";
		$type_merge = array_merge($type_array['new'], $type_array['best'], $type_array['hot']);
		$type_merge = array_unique($type_merge);
		$sql .= ' WHERE g.goods_id ' . db_create_in($type_merge);
		/*if($rate>0){
		 $sql .= " and g.area_shop_price like '%".$rate."%'";
		}*/
		$sql .= ' ORDER BY g.sort_order, g.last_update DESC';

		$result = $GLOBALS['db']->getAll($sql);
		foreach ($result AS $idx => $row)
		{
			$dl_pd = 0;
			if($_SESSION['user_id'])
			{
				$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
				$dl_pd = $GLOBALS['db']->getOne($sql);
			}
			$dl_goods = $row['dl_goods'];
			if ($row['promote_price'] > 0)
			{
				$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
				if($promote_price > 0)
				{
					if($rate > 0)
					{
						if($dl_pd==1&&$dl_goods==1)
						{
							$promote_price_rate = get_price_area($row['goods_id'],0,'promote_price',0,0,4);//代理登陆代理商品取香港价格

							$goods[$idx]['promote_price'] = $promote_price_rate > 0 ? 'HKD $ '.$promote_price_rate : '';
						}else
						{
							$promote_price_rate = get_price_area($row['goods_id'],0,'promote_price',0,0,$rate);//取地区促销价格

							$goods[$idx]['promote_price'] = $promote_price_rate > 0 ? price_format1($promote_price_rate,true,$rate) : '';
						}
					}else
					{
						$goods[$idx]['promote_price'] = $promote_price > 0 ? price_format1($promote_price,true,$rate) : '';
					}
				}
			}
			else
			{
				$goods[$idx]['promote_price'] = '';
			}
			$goods[$idx]['market_price'] = price_format1(get_price_area($row['goods_id'],0,'market_price',0,0,$rate),true,$rate);

			$p_price = get_goods_price_small($row['goods_id']);

			if($dl_pd==1&&$dl_goods==1)
			{
				$goods[$idx]['rate_price_format'] = 'HKD $ ';
			}else {
				$goods[$idx]['rate_price_format'] = $_SESSION['area_rate_code']."  $ ";
			}
			if(empty($p_price)||$p_price==0){
				$goods[$idx]['min_price'] = price_format1(get_price_area($row['goods_id'],0,'shop_price',0,0,$rate),true,$rate);
			}else{
				$goods[$idx]['min_price'] = price_format1($p_price,true,$rate);
			}

			$goods[$idx]['id']           = $row['goods_id'];
			$goods[$idx]['name']         = $row['goods_name'];
			if(isset( $row['goods_name_en'])&&$row['goods_name_en']!='')
			{
				$goods[$idx]['name_en']         = $row['goods_name_en'];
			}else
			{
				$goods[$idx]['name_en'] = $row['goods_name'];
			}
			$goods[$idx]['brief']        = $row['goods_brief'];
			$goods[$idx]['brand_name']   = isset($goods_data['brand'][$row['goods_id']]) ? $goods_data['brand'][$row['goods_id']] : '';
			$goods[$idx]['goods_style_name']   = add_style($row['goods_name'],$row['goods_name_style']);
			$goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
			sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
			$goods[$idx]['short_style_name']   = add_style($goods[$idx]['short_name'],$row['goods_name_style']);
			if($rate > 0)
			{
					
					
				if($dl_pd==1&&$dl_goods==1)
				{
					$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,4);//取地区促销价格
				}else
				{
					$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,$rate);//取地区促销价格
				}
				$goods[$idx]['shop_price']   = price_format1($shop_price_rate,true,$rate);
				$time = gmtime();

				if($row['volume_start_date']<=$time&&$row['volume_end_date']>=$time)
				{
					$pan_s = 1;
				}else
				{
					$pan_s = 0;
				}
				if($pan_s)
				{
					if($dl_pd==1&&$dl_goods==1)
					{
						$sql = "SELECT price,num FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$row['goods_id']." and areaid=0 and areaid_rate=4 order by price " ;
							
					}else
					{
						$sql = "SELECT price,num FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$row['goods_id']." and areaid=0 and areaid_rate=".$rate." order by price " ;
							
					}
					$goods_list_num = $GLOBALS['db']->getRow($sql);


					if ($shop_price_rate>$goods_list_num['price']&&$goods_list_num['price']!=0&&$pan_s>0) {

						$shop_price_rate1 =$goods_list_num['price'];
					}

					if($dl_pd==1&&$dl_goods==1)
					{
						$goods[$idx]['shop_price']   = 'HKD $ '.$shop_price_rate;
						if($pan_s>0)
						{
							$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num['num'].'件';
							$goods[$idx]['shop_price_mes_price'] = 'HKD $'.$shop_price_rate1;
						}
						//
					}else
					{
						$goods[$idx]['shop_price']   = price_format1($shop_price_rate,true,$rate);
						
						if($pan_s>0)
						{
							//$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num['num'].'件，立刻優惠至'.price_format1($shop_price_rate).'件';
							$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num['num'].'件';
							$goods[$idx]['shop_price_mes_price'] = price_format1($shop_price_rate1,true,$rate);
						}
						//$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num.'件，立刻優惠至'.price_format1($shop_price_rate).'件';
					}
				}
				

				if($pan_s>0)
				{
					$p_type = '';
				}else
				{
					$p_type = get_promotion_info($row['goods_id']);

				}
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
								$zk_price = $shop_price_rate*$v/10;

							}
							if($p_type[0]['act_type']==2)
							{

								$zk_price = $shop_price_rate*$v/100;
							}

							if(isset($v['buy'])&&$v['buy']>0&&isset($v[$rate])&&$v[$rate]>0)
							{
								$zk_price = $v[$rate]/$v['buy'];


							}
							if($p_type[0]['act_type']==3)
							{
								$zk_price= ($shop_price_rate*$key)/($key+$v);
									
							}
						}
						if($dl_pd==1&&$dl_goods==1)
						{
							//$goods[$idx]['promotion_type']['act_name'] = $p_type[0]['act_name']; //参加的活动
							if($zk_price>0)
							{
								$goods[$idx]['promotion_type']['act_price'] ='HKD $ '.$zk_price;
							}
						}else
						{
							$goods[$idx]['promotion_type']['act_name'] = $p_type[0]['act_name']; //参加的活动
							if($zk_price>0)
							{
								$goods[$idx]['promotion_type']['act_price'] =price_format1($zk_price,true,$rate);
							}

						}
					}
					else
					{
						$goods[$idx]['promotion_type'] = $p_type[0]; //参加的活动
					}

				}else{
					$goods[$idx]['promotion_type']='';
				}

			}else
			{
				$goods[$idx]['shop_price']   = price_format1($row['shop_price'],true,$rate);
			}
			$goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
			$goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
			if (in_array($row['goods_id'], $type_array['best']))
			{
				$type_goods['best'][] = $goods[$idx];
			}
			if (in_array($row['goods_id'], $type_array['new']))
			{
				$type_goods['new'][] = $goods[$idx];
			}
			if (in_array($row['goods_id'], $type_array['hot']))
			{
				$type_goods['hot'][] = $goods[$idx];
			}
		}
	}
	
	return $type_goods[$type];
}


function price_format1($price, $change_price = true , $rate)
{
	clear_all_files();//清除缓存
	if(empty($_SESSION['currency_code']))
	{
		$_SESSION['currency_code'] = $GLOBALS['db']->GetOne("SELECT code FROM " .$GLOBALS['ecs']->table('currencies') . " WHERE default_currency = 1");//用于后台订单列表
	}

	$currency = $GLOBALS['db']->GetRow("SELECT * FROM " .$GLOBALS['ecs']->table('currencies') . " WHERE code ='".$_SESSION['currency_code'] ."'" );

	//$price = $price*$currency['rate'];// 原价格*当前选择的汇率

	if ($change_price && defined('ECS_ADMIN') === false && $change_price<>1&&1<>1)//price_format1($goods_price, false);false为执行价格显示的规则
	{
		switch ($GLOBALS['_CFG']['price_format'])
		{
			case 0:
				$price = number_format($price, 2, '.', '');
				break;
			case 1: // 保留不为 0 的尾数
				$price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

				if (substr($price, -1) == '.')
				{
					$price = substr($price, 0, -1);
				}
				break;
			case 2: // 不四舍五入，保留1位
				$price = substr(number_format($price, 2, '.', ''), 0, -1);
				break;
			case 3: // 直接取整
				$price = intval($price);
				break;
			case 4: // 四舍五入，保留 1 位
				$price = number_format($price, 1, '.', '');
				break;
			case 5: // 先四舍五入，不保留小数
				$price = round($price);
				break;
		}
	}
	else
	{
		if(empty($price))
		{
			$price = 0;
		}
		//$price = number_format($price, 2, '.', '');
		$price = ceil($price);
	}

	//return sprintf($GLOBALS['_CFG']['currency_format'], $price);
	if($rate==4)
	{
		return 'HKD'."  $". $price .$currency['symbol_right'];
	}else 
	{
		return 'MOP'."  $". $price .$currency['symbol_right'];
	}
}


/**
 * 获得指定分类下的商品精品
 *
 * @access  public
 * @param   integer     $cat_id     分类ID
 * @param   integer     $num        数量
 * @param   string      $from       来自web/wap的调用
 * @param   string      $order_rule 指定商品排序规则
 * @param   array       $brand_id
 * @return  array
 */
function assign_cat_goods_hot($cat_id=1, $num = 0, $from = 'web' ,$rate=4, $order_rule = '',$brand_id='')
{
	$children = get_children1($cat_id);
	
	$sql = 'SELECT g.goods_id, g.brand_id,g.goods_name_style,g.volume_start_date,g.volume_end_date,g.dl_goods, g.goods_name, g.market_price,g.is_shipping, g.shop_price AS org_price, ' .
			"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
			'g.promote_price, promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
			"FROM " . $GLOBALS['ecs']->table('goods') . ' AS g '.
			"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
			'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND '.' g.is_best=1 AND '.
			'g.is_delete = 0  AND (' . $children . 'OR ' . get_extension_goods($children) . ') ';

	/*if($brand_id > 0){
	 $sql .= 'AND g.brand_id = "'. $brand_id .'"';
	}*/

	$order_rule = empty($order_rule) ? 'ORDER BY g.sort_order, g.goods_id DESC' : $order_rule;
	$sql .= $order_rule;
	if ($num > 0)
	{
		$sql .= ' LIMIT ' . $num;
	}
	
	$res = $GLOBALS['db']->getAll($sql);

	$goods = array();
	$brand_id_list = array();
	foreach ($res AS $idx => $row)
	{
		$dl_pd = 0;
		if($_SESSION['user_id'])
		{
			$sql = "SELECT r.dl_pd FROM  ".$GLOBALS['ecs']->table('user_rank')." AS r , ".$GLOBALS['ecs']->table('users')." as u WHERE u.user_rank=r.rank_id and u.user_id=".$_SESSION['user_id'];
			$dl_pd = $GLOBALS['db']->getOne($sql);
		}
		$dl_goods = $row['dl_goods'];
		if ($row['promote_price'] > 0)
		{
			$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
			if($promote_price > 0)
			{
				if($rate > 0)
				{
					if($dl_pd==1&&$dl_goods==1)
					{
						$promote_price_rate = get_price_area($row['goods_id'],0,'promote_price',0,0,4);//代理登陆代理商品取香港价格
						 
						$goods[$idx]['promote_price'] = $promote_price_rate > 0 ? 'HKD $ '.$promote_price_rate : '';
					}else
					{
						$promote_price_rate = get_price_area($row['goods_id'],0,'promote_price',0,0,$rate);//取地区促销价格
						 
						$goods[$idx]['promote_price'] = $promote_price_rate > 0 ? price_format1($promote_price_rate,true,$rate) : '';
					}
				}else
				{
					$goods[$idx]['promote_price'] = $promote_price > 0 ? price_format1($promote_price,true,$rate) : '';
				}
			}
		}
		else
		{
			$goods[$idx]['promote_price'] = '';
		}
		$goods[$idx]['market_price'] = price_format1(get_price_area($row['goods_id'],0,'market_price',0,0,$rate),true,$rate);

		$p_price = get_goods_price_small($row['goods_id']);
		 
		if($dl_pd==1&&$dl_goods==1)
		{
			$goods[$idx]['rate_price_format'] = 'HKD $ ';
		}else {
			$goods[$idx]['rate_price_format'] = $_SESSION['area_rate_code']."  $ ";
		}
		if(empty($p_price)||$p_price==0){
			$goods[$idx]['min_price'] = price_format1(get_price_area($row['goods_id'],0,'shop_price',0,0,$rate),true,$rate);
		}else{
			$goods[$idx]['min_price'] = price_format1($p_price,true,$rate);
		}

		$goods[$idx]['id']           = $row['goods_id'];
		$goods[$idx]['name']         = $row['goods_name'];
		if(isset( $row['goods_name_en'])&&$row['goods_name_en']!='')
		{
			$goods[$idx]['name_en']         = $row['goods_name_en'];
		}else
		{
			$goods[$idx]['name_en'] = $row['goods_name'];
		}
		$goods[$idx]['brief']        = $row['goods_brief'];
		$goods[$idx]['brand_name']   = isset($goods_data['brand'][$row['goods_id']]) ? $goods_data['brand'][$row['goods_id']] : '';
		$goods[$idx]['goods_style_name']   = add_style($row['goods_name'],$row['goods_name_style']);
		$goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
		sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
		$goods[$idx]['short_style_name']   = add_style($goods[$idx]['short_name'],$row['goods_name_style']);
		if($rate > 0)
		{
			 
			 
			if($dl_pd==1&&$dl_goods==1)
			{
				$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,4);//取地区促销价格
			}else
			{
				$shop_price_rate = get_price_area($row['goods_id'],0,'shop_price',0,0,$rate);//取地区促销价格
			}
			$goods[$idx]['shop_price']   = price_format1($shop_price_rate,true,$rate);
			$time = gmtime();

			if($row['volume_start_date']<=$time&&$row['volume_end_date']>=$time)
			{
				$pan_s = 1;
			}else
			{
				$pan_s = 0;
			}
			if($pan_s)
			{
				if($dl_pd==1&&$dl_goods==1)
				{
					$sql = "SELECT price,num FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$row['goods_id']." and areaid=0 and areaid_rate=4 order by price " ;
					 
				}else
				{
					$sql = "SELECT price,num FROM ".$GLOBALS['ecs']->table('price_area')."  WHERE  (price_type='volume_price' or price_type='sn_volume_price') and goods_id=".$row['goods_id']." and areaid=0 and areaid_rate=".$rate." order by price " ;
					 
				}
				$goods_list_num = $GLOBALS['db']->getRow($sql);
				 
				 
				if ($shop_price_rate>$goods_list_num['price']&&$goods_list_num['price']!=0&&$pan_s>0) {

					$shop_price_rate1 =$goods_list_num['price'];
				}
				 
				if($dl_pd==1&&$dl_goods==1)
				{
					$goods[$idx]['shop_price']   = 'HKD $ '.$shop_price_rate;
					if($pan_s>0)
					{
						$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num['num'].'件';
						$goods[$idx]['shop_price_mes_price'] = 'HKD $'.$shop_price_rate1;
					}
					//
				}else
				{
					$goods[$idx]['shop_price']   = price_format1($shop_price_rate,true,$rate);
					if($pan_s>0)
					{
						//$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num['num'].'件，立刻優惠至'.price_format1($shop_price_rate,true,$rate).'件';
						$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num['num'].'件';
						$goods[$idx]['shop_price_mes_price'] = price_format1($shop_price_rate1,true,$rate);
					}
					//$goods[$idx]['shop_price_mes']   = '滿'.$goods_list_num.'件，立刻優惠至'.price_format1($shop_price_rate,true,$rate).'件';
				}
			}


			if($pan_s>0)
			{
				$p_type = '';
			}else
			{
				$p_type = get_promotion_info($row['goods_id']);
				 
			}
			if(!empty($p_type)){
				if($p_type[0]['act_type']==0)
				{
					if(isset($p_type[1]))
					{
						$p_type[0] = $p_type[1];
					}
					 
				}
				$zk_price = 0;
				if($p_type[0]['act_type']!=0)
				{
					$xlh = unserialize($p_type[0]['buy']);
					$xlh_key = count($xlh);

					foreach ($xlh as $key=>$v)
					{
						if($p_type[0]['act_type']==4)
						{
							$zk_price = $shop_price_rate*$v/10;

						}
						if($p_type[0]['act_type']==2)
						{

							$zk_price = $shop_price_rate*$v/100;
						}

						if(isset($v['buy'])&&$v['buy']>0&&isset($v[$rate])&&$v[$rate]>0)
						{
							$zk_price = $v[$rate]/$v['buy'];


						}
						if($p_type[0]['act_type']==3)
						{
							$zk_price= ($shop_price_rate*$key)/($key+$v);
							 
						}
						if($p_type[0]['act_type']==1)
						{
							$zk_price= $shop_price_rate;
						
						}
					}
					if($dl_pd==1&&$dl_goods==1)
					{
						//$goods[$idx]['promotion_type']['act_name'] = $p_type[0]['act_name']; //参加的活动
						if($zk_price>0)
						{
							$goods[$idx]['promotion_type']['act_price'] ='HKD $ '.$zk_price;
						}
					}else
					{
						$goods[$idx]['promotion_type']['act_name'] = $p_type[0]['act_name']; //参加的活动
						if($zk_price>0)
						{
							$goods[$idx]['promotion_type']['act_price'] =price_format1($zk_price,true,$rate);
						}

					}
				}
				else
				{
					$goods[$idx]['promotion_type'] = $p_type[0]; //参加的活动
				}

			}else{
				$goods[$idx]['promotion_type']='';
			}

		}else
		{
			$goods[$idx]['shop_price']   = price_format1($row['shop_price'],true,$rate);
		}
		$goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

	}



	return $goods;
}
$fp = @fopen("data/window_log.txt", "a+");

$data = date("Y-m-d H:i:s",time());
fwrite($fp , $data. " top排行定时更新！");fclose($fp);
echo '更新完毕！';
