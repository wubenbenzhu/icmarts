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
assign_template();
    $sql = 'SELECT ad_name, ad_link,ad_code,heights,is_type FROM ' . $ecs->table("ad") . " WHERE position_id = 2 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";
    $wlad = $db->getAll($sql);

  //  $smarty->assign('wlad', $wlad); //facebook视频
    $wlad_type0 = array();
    $wlad_type1 = array();

    foreach ($wlad as $k => $v) {

    	if($v['is_type'] == 1)
    	{
            $wlad_type0[] = $v;
    	}
    	else
    	{
    		$wlad_type1[] = $v;
    	}
    }

    $smarty->assign('wlad_type0', $wlad_type0);
    $smarty->assign('wlad_type1', $wlad_type1);
    
$smarty->display('video.dwt');




?>
