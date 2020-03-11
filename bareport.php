<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 15-3-27
 * Time: 下午4:01
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if (!isset($_REQUEST['act']))
{
    $_REQUEST['act'] = "list";
}

if ($_REQUEST['act'] == 'list')
{
    assign_template();

    $position = assign_ur_here();

    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
    $smarty->assign('bonus_left',    1); // 左：現金券圖lbi顯示
    //$smarty->assign('search_type',   1); // 左：搜索lbi顯示
    $smarty->assign('cat_left',      1); // 左：分類lbi顯示
    $smarty->assign('categories',      get_categories_tree()); // 分类树
    $smarty->assign('articles_index',  get_cat_articles(14));       // 新手必看文章列表
    $smarty->assign('bonus_img',       get_bonus_img());       // 現金券圖片
    $cat_str = get_article_children(15);
    
    $sql = 'SELECT article_id, title, content, author, add_time, file_url, open_type' .
    		' FROM ' .$GLOBALS['ecs']->table('article') .
    		' WHERE is_open = 1 AND ' . $cat_str .
    		' ORDER BY article_type DESC, article_id DESC';
    $res = $GLOBALS['db']->getAll($sql);
    $count = count($res);
    $size = 0;
    $sizebig = 0;
    if($count>0)
    {
    	/* 取得每页记录数 */
    	$size = isset($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 1;
    	$size = 2;
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
    $sql = $sql." limit $sizebig,$size ";
    $pager = get_pager('bareport.php', array('act' => 'list'), $count, $page, $size);
     
    $res = $GLOBALS['db']->getAll($sql);
    $arr = array();
    if ($res)
    {
    	foreach ($res as $key=>$value)
    	{
    		$article_id = $value['article_id'];
    		$sql = "SELECT a.goods_id,g.goods_img FROM ".$GLOBALS['ecs']->table('goods_article')." as a,".$GLOBALS['ecs']->table('goods')." as g WHERE g.goods_id=a.goods_id and a.article_id=".$article_id;
    		$goods = $GLOBALS['db']->getRow($sql);
    		$arr[$article_id]['img_one'] = $goods['goods_img'];
    		$arr[$article_id]['img'] =get_goods_gallery($goods['goods_id']);
    		$sql = " SELECT count(id) FROM ".$GLOBALS['ecs']->table('comment_article')." as c,".$GLOBALS['ecs']->table('users')." as u WHERE c.user_id=u.user_id and   c.article_id=".$article_id;
    		$comment_list = $GLOBALS['db']->getOne($sql);
    		$arr[$article_id]['count_huifu']          = $comment_list;
    		$arr[$article_id]['id']          = $article_id;
    		$arr[$article_id]['title']       = $value['title'];
    		$arr[$article_id]['content']     = $value['content'];
    		$arr[$article_id]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ? sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $value['title'];
    		$arr[$article_id]['author']      = empty($value['author']) || $value['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $value['author'];
    		$arr[$article_id]['url']         = $value['open_type'] != 1 ? build_uri('article', array('aid'=>$article_id), $value['title']) : trim($value['file_url']);
    		$arr[$article_id]['add_time']    = date($GLOBALS['_CFG']['date_format'], $value['add_time']);
    	}
    }
    $t = $arr;
    $smarty->assign('cfg', $_CFG);
    $smarty->assign('bart_list',       $t);
    $smarty->assign('pager', $pager);
    $smarty->display('bareportlist.dwt');
}

/**
 * 獲取試穿報告列表
 *
 * @access  public
 * @param   integer     $cat_id 試穿報告文章分類ID
 * @param   integer     $page
 * @param   integer     $size
 *
 * @return  array
 */
function bareport_list($cat_id, $page = 1, $size = 20)
{
    //取出所有子類ID
    $cat_str = get_article_children($cat_id);

    $sql = 'SELECT article_id, title, content, author, add_time, file_url, open_type' .
        ' FROM ' .$GLOBALS['ecs']->table('article') .
        ' WHERE is_open = 1 AND ' . $cat_str .
        ' ORDER BY article_type DESC, article_id DESC';
    $res = $GLOBALS['db']->getAll($sql);
    $count = count($res);
    $size = 0;
    $sizebig = 0;
    if($count>0)
    {
    	/* 取得每页记录数 */
    	$size = isset($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 1;
    	$size = 2;
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
    $sql = $sql." limit $sizebig,$size ";
    $pager = get_pager('volume.php', array('act' => 'list'), $count, $page, $size);
   
    $res = $GLOBALS['db']->getAll($sql);
    $arr = array();
    if ($res)
    {
        foreach ($res as $key=>$value)
        {
            $article_id = $value['article_id'];
            $sql = "SELECT a.goods_id,g.goods_img FROM ".$GLOBALS['ecs']->table('goods_article')." as a,".$GLOBALS['ecs']->table('goods')." as g WHERE g.goods_id=a.goods_id and a.article_id=".$article_id;
            $goods = $GLOBALS['db']->getRow($sql);
            $arr[$article_id]['img_one'] = $goods['goods_img'];
            $arr[$article_id]['img'] =get_goods_gallery($goods['goods_id']);
            $sql = " SELECT count(id) FROM ".$GLOBALS['ecs']->table('comment_article')." as c,".$GLOBALS['ecs']->table('users')." as u WHERE c.user_id=u.user_id and   c.article_id=".$article_id;
            $comment_list = $GLOBALS['db']->getOne($sql);
            $arr[$article_id]['count_huifu']          = $comment_list;
            $arr[$article_id]['id']          = $article_id;
            $arr[$article_id]['title']       = $value['title'];
            $arr[$article_id]['content']     = $value['content'];
            $arr[$article_id]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ? sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $value['title'];
            $arr[$article_id]['author']      = empty($value['author']) || $value['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $value['author'];
            $arr[$article_id]['url']         = $value['open_type'] != 1 ? build_uri('article', array('aid'=>$article_id), $value['title']) : trim($value['file_url']);
            $arr[$article_id]['add_time']    = date($GLOBALS['_CFG']['date_format'], $value['add_time']);
        }
    }
    $arr[1]['page'] = $pager;

    return $arr;
}