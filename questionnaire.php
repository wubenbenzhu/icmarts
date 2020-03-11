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

if($_REQUEST['act'] == 'change_tel'){

    include('includes/cls_json.php');
    $json   = new JSON;

    $res = array('error'=>0,'mag'=>'');

    $tel = $_REQUEST['tel'];
    $tel_rate = $_REQUEST['tel_rate'];
    $sql = "select * from ".$GLOBALS['ecs']->table('users')." where office_phone='".$tel."' or home_phone='".$tel."' or mobile_phone='".$tel."' ";

    $user = $GLOBALS['db']->getRow($sql);
    if(!empty($user)){
        $sql = "select content from ".$GLOBALS['ecs']->table('reg_extend_info')." where reg_field_id=8 and user_id=".$user['user_id'];
        $rat = $GLOBALS['db']->getOne($sql);
        if($tel_rate == $rat){
            $rest['user_id'] = $user['user_id'];
            $rest['email'] = $user['email'];
            $rest['is_vip'] = 0;
            $rest['null_pas'] = 0;
            if($user['user_rank'] == 1){
                $rest['is_vip'] = 1;
            }
            if(empty($user['password'])){
                $rest['null_pas'] = 1;
            }
            $res = array('error'=>0,'mag'=>$rest);

        }else{
            $res = array('error'=>1,'mag'=>'手機區號和手機號不對應！');
        }
    }else{
        $res = array('error'=>1,'mag'=>'該手機號還沒有註冊賬號！');
    }

    die($json->encode($res));
}
if($_REQUEST['act'] == 'add'){
   // var_dump($_REQUEST);
    $tel = $_REQUEST['tel_rate'].'+'.trim($_REQUEST['tel']);
    $time = time();
    //限製提交時間 10分鍾
    $sql = " select confirm_time from ".$GLOBALS['ecs']->table('questionnaire')." where tel='".$tel."' or email = '".$_REQUEST['email']."' order by confirm_time desc";
    $confirm = $GLOBALS['db']->getRow($sql);
    if($confirm['confirm_time']+600 > $time){
        show_message('十分鍾內隻能提交一次');
    }

    $user_id = intval($_REQUEST['user_id']);

    if(!empty($user_id)){
        $user_info = array();
        if(!empty($_REQUEST['null_pas'])){
            //添加密碼
            $password = trim($_REQUEST['password']);
            $cm_password = trim($_REQUEST['cm_password']);
            if($password == $cm_password && $_SESSION['mobile_code'] == $_REQUEST['tel_code']){
                $user_info['password'] = md5($password);
            }else{
                //驗證碼錯誤
                show_message('驗證碼錯誤');
            }
        }
        $sql = "select email from ".$GLOBALS['ecs']->table('users')." where user_id=".$user_id;
        $user_email = $GLOBALS['db']->getOne($sql);
        if(empty($user_email)){
            //添加郵箱
            $user_info['email'] = trim($_REQUEST['email']);
        }

        if(!empty($user_info)) {
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $user_info, 'UPDATE', "user_id = $user_id");
        }
    }

    $des = $_REQUEST['desired_effect'];

    if(!empty($_REQUEST['desired_effect_o'])){
        $des[] = $_REQUEST['desired_effect_o'];
    }
    $desired_effect = implode(',',$des);

    $sex = $_REQUEST['name'].$_REQUEST['sex'];

    $sql = "insert into " . $GLOBALS['ecs']->table('questionnaire') . " (tel,email,desired_effect,degree_of,remarks,is_vip,confirm_time,is_sn,sn_ld,sex_name)
            value ('".$tel."','".$_REQUEST['email']."','".$desired_effect."','".$_REQUEST['degree_of']."','".$_REQUEST['remarks']."',".$_REQUEST['is_vip'].",$time,'".$_REQUEST['is_sn']."','".$_REQUEST['sn_ld']."','".$sex."')";
    $GLOBALS['db']->query($sql);

    show_message('提交成功');
}

else{
    if(empty($_SESSION['send_code']))
        $_SESSION['send_code'] = random(6,1);
    assign_template();
    $position = assign_ur_here('', '客戶需求調查');
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置
    
    $smarty->assign('categories',      get_categories_tree()); // 分类树
    $sql = "select * from ".$GLOBALS['ecs']->table('project')." order by order_by";
    $project_list = $GLOBALS['db']->getAll($sql);

    $smarty->assign('project_list', $project_list);

    $smarty->assign('mobile', $_SESSION['mobile']);
    $smarty->assign('send_code', $_SESSION['send_code']);
    $smarty->display('questionnaire.dwt');
    return false;
}
?>