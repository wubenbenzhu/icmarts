<?php

/**
 * ECSHOP 文章分类
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: article_cat.php 17217 2011-01-19 06:29:08Z liubo $
*/


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/* 清除缓存 */
clear_cache_files();

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

/* 获得指定的分类ID */
if (!empty($_GET['id']))
{
    $cat_id = intval($_GET['id']);
}
elseif (!empty($_GET['category']))
{
    $cat_id = intval($_GET['category']);
}
else
{
    ecs_header("Location: ./\n");

    exit;
}

/* 获得当前页码 */
$page   = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

/* 获得页面的缓存ID */
$cache_id = sprintf('%X', crc32($cat_id . '-' . $page . '-' . $_CFG['lang']));

if (!$smarty->is_cached('article_cat.dwt', $cache_id))
{
    /* 如果页面没有被缓存则重新获得页面的内容 */
	$smarty->assign('articles_list',  get_cat_articles(2));//同级文章列表
    assign_template('a', array($cat_id));
    $position = assign_ur_here($cat_id);
    $sql = 'SELECT * FROM ' . $ecs->table("ad") . " WHERE position_id = 5 and enabled=1 and ".gmtime()." >= start_time and ".gmtime()." <= end_time and rate_areaid like '%".$_SESSION['area_rate_id']."%' order by end_time desc  ";
    
    $ad_hd_ban = $db->getRow($sql);
    
    if($ad_hd_ban)
    {
    	$ad_hd_ban['ad_code'] = DATA_DIR . "/afficheimg/".$ad_hd_ban['ad_code'];
    }
    $smarty->assign('ad_hd_ban', $ad_hd_ban);
    $smarty->assign('page_title',           $position['title']);     // 页面标题
    $smarty->assign('ur_here',              $position['ur_here']);   // 当前位置
    $smarty->assign('brand_is_enabled_list',  get_brands_is_enabled());
    $smarty->assign('categories',           get_categories_tree(0)); // 分类树
    $art_cat = article_categories_tree($cat_id);
    $art_pare=get_article_parent_cats($cat_id);
    $smarty->assign('article_categories',   $art_cat); //文章分类树
    $smarty->assign('article_info',   $art_cat[$cat_id]);
    $c_id = intval($art_pare[count($art_pare)-1]['cat_id']);
    if($c_id == 16 || $c_id == 17|| $c_id == 23){
    	
        $ar_cat = article_categories_tree($c_id);
        $smarty->assign('art_cat_left',   $ar_cat[$c_id]); //文章左分类
    }else{
        $smarty->assign('art_cat_left',   $art_cat[$cat_id]); //文章左分类
    }

    $smarty->assign('helps',                get_shop_help());        // 网店帮助
    //$smarty->assign('top_goods',            get_top10());            // 销售排行

    //$smarty->assign('best_goods',           get_recommend_goods('best'));
    //$smarty->assign('new_goods',            get_recommend_goods('new'));
    //$smarty->assign('hot_goods',            get_recommend_goods('hot'));
    //$smarty->assign('promotion_goods',      get_promote_goods());
    //$smarty->assign('promotion_info', get_promotion_info());

    /* Meta */
    $meta = $db->getRow("SELECT keywords, cat_desc FROM " . $ecs->table('article_cat') . " WHERE cat_id = '$cat_id'");

    if ($meta === false || empty($meta))
    {
        /* 如果没有找到任何记录则返回首页 */
        ecs_header("Location: ./\n");
        exit;
    }

    $smarty->assign('keywords',    htmlspecialchars($meta['keywords']));
    $smarty->assign('description', htmlspecialchars($meta['cat_desc']));

    /* 获得文章总数 */
    $size   = 9;

    $count  = get_article_count($cat_id);
    $pages  = ($count > 0) ? ceil($count / $size) : 1;

    if ($page > $pages)
    {
        $page = $pages;
    }
    //$pager  = get_pager('article_cat.php', array('id' => $cat_id), $count, $page);
    $pager['search']['id'] = $cat_id;
    $keywords = '';
    $goon_keywords = ''; //继续传递的搜索关键词

    /* 获得文章列表 */
    if (isset($_REQUEST['keywords']))
    {
        $keywords = addslashes(htmlspecialchars(urldecode(trim($_REQUEST['keywords']))));
        $pager['search']['keywords'] = $keywords;
        $search_url = substr(strrchr($_POST['cur_url'], '/'), 1);

        $smarty->assign('search_value',    stripslashes(stripslashes($keywords)));
        $smarty->assign('search_url',       $search_url);
        $count  = get_article_count($cat_id, $keywords);
        $pages  = ($count > 0) ? ceil($count / $size) : 1;
        if ($page > $pages)
        {
            $page = $pages;
        }

        $goon_keywords = urlencode($_REQUEST['keywords']);
    }
    if($cat_id == 16|| $cat_id==23){
        $sql = 'SELECT article_id, title, author, add_time, file_url, open_type, content' .
            ' FROM ' .$GLOBALS['ecs']->table('article') .
            ' WHERE is_open = 1 AND ' . get_article_children($cat_id) .
            ' ORDER BY add_time DESC LIMIT 0 , 9';
        $res = $db->getAll($sql);
        $arr=array();
        foreach($res as $row){
            $article_id = $row['article_id'];

            preg_match_all("/src=\"\/?(.*?)\"/", $row['content'], $match);
            $con=str_replace(array("\r\n", "\r", "\n"), "",trim(str_replace('&nbsp;', '', strip_tags($row['content']))));

            $arr[$article_id]['id']          = $article_id;
            $arr[$article_id]['title']       = $row['title'];
            $arr[$article_id]['img']         = $match[1][0];
            $arr[$article_id]['main']        = sub_str($con, 60);
            $arr[$article_id]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ? sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];
            $arr[$article_id]['author']      = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $row['author'];
            $arr[$article_id]['url']         = $row['open_type'] != 1 ? build_uri('article', array('aid'=>$article_id), $row['title']) : trim($row['file_url']);
            $arr[$article_id]['add_time']    = date($GLOBALS['_CFG']['date_format'], $row['add_time']);
        }
        $smarty->assign('artciles_list',  $arr);
    }
    else{
        $smarty->assign('artciles_list',    get_cat_articles($cat_id, $page, $size ,$keywords));
    }
    $smarty->assign('cat_id',    $cat_id);
    /* 分页 */
    assign_pager('article_cat', $cat_id, $count, $size, '', '', $page, $goon_keywords);
    assign_dynamic('article_cat');
}

$smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typearticle_cat" . $cat_id . ".xml" : 'feed.php?type=article_cat' . $cat_id); // RSS URL

//$smarty->display('article_cat.dwt', $cache_id);
$smarty->display('faq.dwt', $cache_id);
?>