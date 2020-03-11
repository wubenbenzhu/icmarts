<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if (!isset($_REQUEST['act']))
{
    $_REQUEST['act'] = "list";
}

if($_REQUEST['act'] == "output") {

    require_once(ROOT_PATH.'tcpdf/examples/tcpdf_include.php');

    $tcpdf_include_dirs = array(
        realpath('./tcpdf/tcpdf.php'),
        '/usr/share/php/tcpdf/tcpdf.php',
        '/usr/share/tcpdf/tcpdf.php',
        '/usr/share/php-tcpdf/tcpdf.php',
        '/var/www/tcpdf/tcpdf.php',
        '/var/www/html/tcpdf/tcpdf.php',
        '/usr/local/apache2/htdocs/tcpdf/tcpdf.php'
    );
    foreach ($tcpdf_include_dirs as $tcpdf_include_path) {

        if (@file_exists($tcpdf_include_path)) {
            require_once($tcpdf_include_path);
            break;
        }
    }

    $pid = intval($_REQUEST['pid']);

    if(!empty($pid)){
        $sql = "select * from ".$GLOBALS['ecs']->table('pdf_activity')." where pid = $pid";

        $res = $GLOBALS['db']->getRow($sql);

        $header = $res['title']."  活动时间：".local_date('Y-m-d H:i', $res['start_time'])." --- ".local_date('Y-m-d H:i', $res['end_time'])."\nwww.icmarts.com";

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('icmarts');
        $pdf->SetTitle($res['title']);
        $pdf->SetSubject('icmarts');
        $pdf->SetKeywords('icmarts');

// set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, '潮流生活百货'.' 038', $header);

// set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);


// ---------------------------------------------------------

// set font
        $pdf->SetFont('stsongstdlight', '', 20);

        $pdf->AddPage();

        $sql = "select goods_id from ".$GLOBALS['ecs']->table('pdf_goods')." where p_id = $pid group by goods_id";
        $goods = $GLOBALS['db']->getAll($sql);

        $goods_list = array();
        foreach($goods as $v){
            $sql = "select g.goods_id,g.goods_name,b.brand_name from ".$GLOBALS['ecs']->table('goods')." as g, ".$GLOBALS['ecs']->table('brand')." as b where g.brand_id = b.brand_id and g.goods_id = ".$v['goods_id'];
            $goods_info = $GLOBALS['db']->getRow($sql);

            $goods_info['gallery'] = get_goods_gallery($v['goods_id']);

            $sql = "select p.goods_attr,p.product_sn,p.product_sn_code from ".$GLOBALS['ecs']->table('pdf_goods')." as pg, ".$GLOBALS['ecs']->table('products')." as p where pg.product_id = p.product_id ".
                " and pg.p_id = $pid and pg.goods_id = ".$v['goods_id'];
            $re = $GLOBALS['db']->getAll($sql);

            foreach($re as $k=>$value){

                $attr_id = str_replace('|',',',$value['goods_attr']);

                $sql = "select attr_value from ".$GLOBALS['ecs']->table('goods_attr')." where goods_attr_id in ($attr_id) and goods_id = ".$v['goods_id'];

                $re[$k]['attr_value'] = $GLOBALS['db']->getAll($sql);

                $re[$k]['shop_price'] = get_final_price_new($v['goods_id'], 1, true, $attr_id,$_SESSION['area_rate_id'],$value['product_sn']);

                $goods_info['attr'][]=$re[$k];
            }
            $goods_list[$v['goods_id']] = $goods_info;
        }
    }

    $html = '';

    foreach($goods_list as $k=>$info){
    	$html .='<img  src="images/201503/thumb_img/4_thumb_P_1425345221997.jpg" >';
        $html .='<ul style="margin: 0px; padding: 0px; list-style: none;">';
        foreach($info['gallery'] as $gall){

        	$html .="<li style='margin: 0px 5px 0px 0px; padding: 0px; display: inline-block; float: left;'><img src=\"".$gall['thumb_url']."\" style='border: 0px; width: 107px;' alt=''/></li>";
        }
        $html .='</ul>';
        $html .='<h1 style="margin: 10px 0px; padding: 0px; font-size: 18px;">'.$info['goods_name'].'</h1>';
        $html .='<table style="border-collapse: collapse; border-spacing: 0px;">
        <tbody>
            <tr>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px; width: 100px;">商品品牌:</td>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px; ">'.$info['brand_name'].'</td>
            </tr>
            <tr>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px;">顏色:</td>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px;">蔚藍色、膚色</td>
            </tr>
            <tr>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px;">杯型:</td>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px;">B、C、D</td>
            </tr>
            <tr>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px;">尺碼:</td>
                <td style="margin: 0px; padding: 2px 20px 2px 0px; font-size: 14px;">32/70、34/75 、36/80、38/85、40/90</td>
            </tr>
        </tbody>
        </table>
        <h2 style="margin: 20px 0px 10px; padding: 5px; font-size: 16px; font-weight: normal; border-radius: 5px; text-align: center;  text-shadow: rgba(0, 0, 0, 0.2) 0px 2px 10px; background: rgb(230, 108, 145); ">&mdash;&nbsp;快速訂貨&nbsp;&mdash;</h2>
        <table style="border-collapse: collapse; border-spacing: 0px; width: 555px;">
            <tbody>';

        foreach($info['attr'] as $ka=>$attr){
            $html .='<tr>
                <td style="margin: 0px; padding: 0px; font-size: 14px;">'.$attr['product_sn'].'</td>';
            foreach($attr['attr_value'] as $a_value){
                $html .='<td style="margin: 0px; padding: 0px; font-size: 14px;">'.$a_value['attr_value'].'</td>';
            }
            $params = $pdf->serializeTCPDFtagParameters(array($attr['product_sn_code'], 'C128', '', '', 50, 20, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
            $txt = '<tcpdf method="write1DBarcode" params="'.$params.'" />';
            $html .= '<td style="margin: 0px; padding: 0px; font-size: 14px;">'.$_SESSION['currency_code'].$attr['shop_price'].'</td>
                <td style="margin: 0px; padding: 0px; font-size: 14px;">'.$txt.'</td></tr>';

        }
        $html .='</tbody></table>';
    }


    $pdf->writeHTML($html, true, false, true, false, '');

// ---------------------------------------------------------

    $pdf_name = $res['title'].".pdf";
//Close and output PDF document
    $pdf->Output($pdf_name, 'I');

//var_dump($html);
}

elseif($_REQUEST['act'] == "list"){

   // goods_pdf(1);die();
    $time = gmtime();
    $sql = "select * from ".$GLOBALS['ecs']->table('pdf_activity')." where areaid =".$_SESSION['area_rate_id']." group by cid";

    $list = $GLOBALS['db']->getAll($sql);

    foreach($list as $k=>$v){
        $sql = "select url from ".$GLOBALS['ecs']->table('pdf_activity')." where areaid =".$_SESSION['area_rate_id']." and cid = ".$v['cid'];
        $dow = $GLOBALS['db']->getAll($sql);

        $list[$k]['dow'] = $dow;
    }


    $smarty->assign('list', $list);

    assign_template();

    $smarty->display('activity_pdf.dwt');

}

function get_pdf($html,$type='html'){

    //require_once(ROOT_PATH."tcpdf/config/tcpdf_config.php");
    require_once(ROOT_PATH.'tcpdf/examples/tcpdf_include.php');

    $tcpdf_include_dirs = array(
        realpath('./tcpdf/tcpdf.php'),
        '/usr/share/php/tcpdf/tcpdf.php',
        '/usr/share/tcpdf/tcpdf.php',
        '/usr/share/php-tcpdf/tcpdf.php',
        '/var/www/tcpdf/tcpdf.php',
        '/var/www/html/tcpdf/tcpdf.php',
        '/usr/local/apache2/htdocs/tcpdf/tcpdf.php'
    );
    foreach ($tcpdf_include_dirs as $tcpdf_include_path) {

        if (@file_exists($tcpdf_include_path)) {
            require_once($tcpdf_include_path);
            break;
        }
    }

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 038');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 038', PDF_HEADER_STRING);

// set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);


// ---------------------------------------------------------

// set font
    $pdf->SetFont('stsongstdlight', '', 20);

// add a page
    $pdf->AddPage();

    $txt = 'Example of CID-0 CJK unembedded font.快快速訂貨
To display extended text you must have CJK fonts installed for your PDF reader:';
    // $pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

    /*$params = $pdf->serializeTCPDFtagParameters(array('123456', 'C128', '', '', 80, 30, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
    $txt = '<tcpdf method="write1DBarcode" params="'.$params.'" />';*/

    $pdf->writeHTML($html, true, false, true, false, '');

// ---------------------------------------------------------

//Close and output PDF document
    $pdf->Output('example_038.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

}


