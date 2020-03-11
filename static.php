<?php

/**
 * ECSHOP 生成靜態頁面
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

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
    $package_goods_list = get_package_list(1, 1); //组合活动  先取一个组合占位
    if ($package_goods_list) {
        $package_goods_lists = $package_goods_list[0];
    }
    $smarty->assign('package_goods_list', $package_goods_lists);

    $file1 = 'mobile/templates/static_html/activity_' . $r . '.html';//静态网页文件名
    $content1 = $smarty->make_html('../../mobile/templates/static_dwt/activity.dwt');//根据index.dwt模板生成网页内容
    $filename1 = ROOT_PATH . $file1;//静态网页路径
    file_put_contents($filename1, $content1);//生成文件
}
//$file = 'themes/new_chaoliu/static_html/menu_act_'.$_SESSION['area_rate_id'].'.html';//静态网页文件名
//$content = $smarty->make_html('static_dwt/menu_act.dwt');//根据index.dwt模板生成网页内容
//$filename = ROOT_PATH . $file;//静态网页路径
//file_put_contents($filename, $content);//生成文件


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
                $ac = explode(',',$value['act_range_ext']);
                if(count($ac) > 3){
                    $al=array_rand($ac,3);
                    $goods_act = implode(',',array($ac[$al[0]],$ac[$al[1]],$ac[$al[2]]));
                }else{
                    $goods_act = $value['act_range_ext'];
                }
            }else{
                $sql = "select goods_id from ".$GLOBALS['ecs']->table('goods')." where is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 and area_shop_price like '%".$rate."%' AND online_sale like '%".$rate."%' ORDER BY RAND() LIMIT 3";
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



?>