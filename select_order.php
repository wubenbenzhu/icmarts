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

$type = $_REQUEST['type'];//类型：继续付款，查询订单，查看VIP
if(empty($_SESSION['send_code']))
    $_SESSION['send_code'] = random(6,1);
$smarty->assign('mobile', $_SESSION['mobile']);
$smarty->assign('send_code', $_SESSION['send_code']);
$smarty->assign('type', $type);
$smarty->display('select_order.dwt');




?>
