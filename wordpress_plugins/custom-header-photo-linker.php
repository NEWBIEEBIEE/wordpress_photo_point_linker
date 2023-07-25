<?php
/*
  Plugin Name: CustomHeaderPhotoLinker
  Plugin URI: https://github.com/NEWBIEEBIEE/CustomHeaderPhotoLinker
  Description: トップページ画像の装飾・リンクの追加
  Version: 1.0.0
  Author: Naoyuki Sawabe
  Author URI: https://github.com/NEWBIEEBIEE/CustomHeaderPhotoLinker
  License: GPLv2
 */

?>

<?php

///////////////////////////////////////
// テーマカスタマイザーにロゴアップロード設定機能追加
///////////////////////////////////////
define('LOGO_SECTION', 'logo_section'); //セクションIDの定数化
define('LOGO_IMAGE_URL', 'logo_image_url'); //セッティングIDの定数化

define('UPLOAD_INFO_MAX_NUM', 10);

// ウィンドウサイズについて
define('WIDTH_SMART_PHONE',481);// スマートフォン
define('WIDTH_TABLET',769);// タブレット
define('WIDTH_LAPTOP',1025);// デスクトップ
// phpファイルを直接読み込まれないようにするため
if ( ! defined( 'ABSPATH' ) ) exit;

#register_activation_hook(__FILE__, 'chpla_install');// 有効化の際に一度だけ処理
#register_uninstall_hook ( __FILE__, 'chpla_delete_data' );// 無効化の際に一度だけ処理


//add_action('init', 'CustomHeaderPhotoLinker::init');
//add_action(
//    'wp_loaded', 
     //create_function('', 'return register_widget("CustomHeaderPhotoLinker");')   
     //new CustomHeaderPhotoLinker()
//);
$object = new CustomHeaderPhotoLinker();


/* 初回読み込み時にテーブル作成 */
function chpla_install(){
    global $wpdb;
    
    $table = $wpdb->prefix.'wp_locs_info';
    $charset_collate = $wpdb->get_charset_collate();

    // 画像位置情報
    if ($wpdb->get_var("show tables like '$table'") != $table){
        
        $sql = "CREATE TABLE  {$table} (
            ID int(11) not null auto_increment,
            FILE_PATH VARCHAR(400),
            MO_FILE_PATH VARCHAR(400),
            LOC_OF_CANVAS text,
            LINK text,
            MEDIA_TYPE VARCHAR(400),
            CUR_PHOTO_SIZE VARCHAR(400),/* 拡大と縮小のために 貼り付けた際のcanvasの全体のサイズを知らなければならない */
            KOTEI tinyint(1) DEFAULT NULL
            ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // 写真情報
    $table = $wpdb->prefix.'wp_photo_info';
    if($wpdb->get_var("show tables like '$table'") != $table){

        $sql = "CREATE TABLE  {$table} (
            ID int(11) not null auto_increment,
            ELEMENT_PATH text
            ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

/* プラグイン削除時にはテーブルを削除 */
function chpla_delete_data()
{
        global $wpdb;
        $table_name = $wpdb->prefix . 'photo_info';
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query($sql);
        $table_name = $wpdb->prefix . 'locs_info';
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query($sql);
}




    

class CustomHeaderPhotoLinker 
{

    function __construct()
    {
/*        if (is_admin() && is_user_logged_in()) {
            // メニュー追加
            add_action('admin_menu', [$this, 'set_plugin_menu']);
            add_action('admin_menu', [$this, 'set_plugin_sub_menu']);
            // アクションとフィルター設定
            add_action('wp_loaded', array($this, 'action'));// 全てが読み終わった後
            add_filter('wp_headers', array($this, 'filter'));//
*/
            add_action( 'wp_head', 'hiddenInformations' );// 設定情報をヘッダーに貼り付け
            
            add_action( 'customize_register', 'theme_customizer' );//カスタマイザーに登録

            add_action( 'wp_footer', 'cv_widget' );// 設定情報をヘッダーに貼り付け
        }
    }

    ///////////////////////////////////////
    // テーマカスタマイザーにロゴアップロード設定機能追加
    ///////////////////////////////////////
    function theme_customizer( $wp_customize ) {
        
        $wp_customize->add_panel( 'my_panel_setting', array(
            'priority' => 1,
            'title' => 'キャンパス画像リンクの追加',
          ));

        $wp_customize->add_section( 'my_section', array(
            'title'    => '設定', 
            'priority' => 1,
            'panel'    => 'my_panel_setting'    
        ));


        // 選択画像
        //　対象のCANVAS画像
        $wp_customize->add_setting( 'my_setting');
    
        $wp_customize->add_control(
            new WP_Customize_Control(
                $wp_customize,
                'my_control',
                array(
                    'label' => '画像の指定',
                    'section' => 'my_section',
                    'settings' => 'my_setting',
                    'priority' => 1,
                )
            )
        );

        
        $wp_customize->add_setting( 'my_image_url_loc', array(
            'type'      => 'option',
            'sanitize_callback' => 'wp_filter_nohtml_kses'
         ));
    
        $wp_customize->add_control( 'my_image_url_loc', array(
            'label'       => 'my_image_url_loc', 
            'type'        => 'text',
            'section'     => 'my_section', 
            'settings'    => 'my_image_url_loc', 
            'description' => '画像のあるURLが含む固有の値を入れてください。', 
        ));


        // 1
        $wp_customize->add_setting( 'my_image1');
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'my_setting', array(
            'label' => 'ロゴ1', //設定ラベル
            'section' => 'my_section', //セクションID
            'settings' => 'my_image1', //セッティングID
            'description' => '画像をアップロードすると画像を追加できます。',
        )));

        $wp_customize->add_setting( 'my_loc1', array(
            'type'      => 'option',
            'sanitize_callback' => 'wp_filter_nohtml_kses'
         ));
    
        $wp_customize->add_control( 'my_loc1', array(
            'label'       => 'location', 
            'type'        => 'text',
            'section'     => 'my_section', 
            'settings'    => 'my_loc1', 
            'description' => '画面の上にあるボタンより設定ください。', 
        ));

        // URL　リンク先

        $wp_customize->add_setting( 'my_url1', array(
            'type'      => 'option',
            'sanitize_callback' => 'esc_url_raw'
         ));
    
        $wp_customize->add_control( 'my_url1', array(
            'label'       => 'url', 
            'type'        => 'url',
            'section'     => 'my_section', 
            'settings'    => 'my_url1', 
            'description' => 'urlを設定してください。',
        ));

        // 2
        $wp_customize->add_setting( 'my_image2');
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'my_setting2', array(
            'label' => 'ロゴ2', //設定ラベル
            'section' => 'my_section', //セクションID
            'settings' => 'my_image2', //セッティングID
            'description' => '画像をアップロードすると画像を追加できます。',
        )));

        $wp_customize->add_setting( 'my_loc2', array(
            'type'      => 'option',
            'sanitize_callback' => 'wp_filter_nohtml_kses'
            ));
    
        $wp_customize->add_control( 'my_loc2', array(
            'label'       => 'location', 
            'type'        => 'text',
            'section'     => 'my_section', 
            'settings'    => 'my_loc2', 
            'description' => '画面の上にあるボタンより設定ください。', 
        ));

        // URL　リンク先

        $wp_customize->add_setting( 'my_url2', array(
            'type'      => 'option',
            'sanitize_callback' => 'esc_url_raw'
            ));
    
        $wp_customize->add_control( 'my_url2', array(
            'label'       => 'url', 
            'type'        => 'url',
            'section'     => 'my_section', 
            'settings'    => 'my_url2', 
            'description' => 'urlを設定してください。',
        ));


        // 貼り付ける画像と座標の設定画面
        /*for($i = 1; $i <= UPLOAD_INFO_MAX_NUM; $i++){
            add_photo_uploader($i,$wp_customize);
            add_info_form($i,$wp_customize);
        }*/

    }

    // 貼り付け写真のurlを返す
    function get_the_logo_image_url($num){
        return esc_url( get_theme_mod( LOGO_IMAGE_URL + "$num") );
    }



    /* テーマカスタマイザー用のサニタイズ関数
    ---------------------------------------------------------- */
    //ラジオボタン
    function sanitize_choices( $input, $setting ) {
        global $wp_customize;
        $control = $wp_customize->get_control( $setting->id );
        if ( array_key_exists( $input, $control->choices ) ) {
            return $input;
        } else {
            return $setting->default;
        }
    }

    function hiddenInformations() {
        $rootUrl = get_option("my_setting_url");
        $map = get_option("my_setting");
        $map = preg_replace ("/ (| )/", "", $map);
        $location1 = get_option("my_loc1");
        $photo_path1 = get_theme_mod("my_image1");
        $link1 = get_option("my_url1");
        $location2 = get_option("my_loc2");
        $photo_path2 = get_theme_mod("my_image2");
        $link2 = get_option("my_url2");

        // データベースより情報取得
        echo '<span class="photo_locations_information">';

            echo <<<EOF
            <input type='hidden' id='rootUrl' value="{$rootUrl}">
            <input type='hidden' id='maps' value="{$map}">
            <input type="hidden" id="point_loc1" class="location_point" value="{$location1}">
            <input type="hidden" id="photo_path1" class="photo_path" value="{$photo_path1}">
            <input type="hidden" id="hidden_link1" class="point_link" value="{$link1}">
            <input type="hidden" id="current_WH1" value="">
            <input type="hidden" id="point_loc2" class="location_point" value="{$location2}">
            <input type="hidden" id="photo_path2" class="photo_path" value="{$photo_path2}">
            <input type="hidden" id="hidden_link2" class="point_link" value="{$link2}">
            <input type="hidden" id="current_WH2" value="">
            EOF;
    

        //endif;

        // 場所と画像のファイル、リンク先
        echo '</span>';
    }

    function update_logos_information($id, $location, $photo_path, $link)
    {
        global $wpdb;

        $query = "SELECT * FROM $wpdb->prefix.'wp_locs_info' WHERE ID = $id";

        $results = $wpdb->get_results($query);


        if($wpdb->num_rows)// レコードの数
        {
            $query = "DELETE FROM $wpdb->prefix.'wp_locs_info' WHERE ID = $id";
            $results = $wpdb->get_results($query);
        }
        $query = "INSERT INTO $wpdb->prefix.'wp_locs_info' (ID, FILE_PATH, LOC_OF_CANVAS, LINK) VALUES ($id, $location, $photo_path, $link)";

        $results = $wpdb->get_results($query);
    }

    function update_photo_infomation($element_path, $photo_size)// データベース内の一番最初のレコードを削除し　挿入　(HTML内の写真情報)
    {
        global $wpdb;

        $query = "SELECT * FROM $wpdb->prefix.'wp_photo_info'";

        $results = $wpdb->get_results($query);

        $row_first = array_shift($results);

        $id = $row_first->ID;

        if($wpdb->num_rows)// レコードの数
        {
            $query = "DELETE FROM$wpdb->prefix.'wp_photo_info' ORDER BY ID ASC LIMIT 1";
            $results = $wpdb->get_results($query);
        }
        $query = "INSERT INTO $wpdb->prefix.'wp_photo_info' (ID, PHOTO_SIZE, ELEMENT_PATH) VALUES ($id, $element_path, $photo_size)";

        $results = $wpdb->get_results($query);
    }

    // hidden要素を書き換える
    function cv_widget(){
        $javascript_EOL = <<<CANVAS
        <style>
        /* 選択された画像を塗りつぶして分かるようにする */
        .active_canvas_target{
            /*content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;*/
            background-color: #57F;
            //opacity: 0.5;
            display:inline-block;
        }
        .active_pre_process{
            opacity: 0.5;
            display: block;
        }
        </style>
        <script type="text/javascript">
        // document.body.clientWidth;
        var url = location.href;

        //画面がカスタマイザーによる処理なのかでモード変更 urlではなく　bodyタグのクラスによる認識に変えること
        var custom_mode = false;
    

        var blockElems = ['SECTION','ADDRESS', 'BLOCKQUOTE', 'CENTER', 'DIR', 'DIV', 'DL', 'FIELDSET', 'FORM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'HR', 'ISINDEX', 'MENU', 'NOFRAMES', 'NOSCRIPT', 'OL', 'P', 'PRE', 'TABLE', 'UL', 'LI', 'OL', 'ARTICLE', 'FIGURE'];
    
        var targetImage = ""
        var new_canvas = document.createElement('canvas');
        var cvsStyle;
    
        var imageOrNot = true;
        var pointX = -1;
        var pointY = -1;
        var indexNum = 2;// 
        var arrTField = new Array(indexNum);// リンク先
        var arrShapes = new Array(indexNum);// 描写される図形の場所（座標）
        var oneCanvas = "";// キャンバス
        var installed;// キャンバスの描写を行うコンテキスト
        var arrPathCanvas = [];// パスを保存する2次配列
        var setAddPoint = false;

        // 画像のスケール
        var scale = 1;

        var icon_width = 0;
        var icon_height = 0;
    
        // 対象画像指定
        //var arrImgField = new Array(5);
        var testContext;
        // 元の対象img要素
        var targetElem;
        //　最後にアクセスしたカスタマイザー文字Input
        var lastFocusField;
        // 対象画像領域指定
        var lastFocusImgField;
        //　最後にアクセスした対象パネル画像の幅と高さ
        var lastWidth = -1;
        var lastHeight = -1;

        var locSwitch = false;// locのテキストとフィールドに値が入ったとき
        var locPointSwitch = false;// 上の細かい制御
        var locTargetValue;// 上記二つでlocのテキストフィールドを制御し、ここに値を代入
        var arrIndex = 0;

        var width;
        var height;
        var lnk_elems;
        var imgCurWH = new Array(indexNum);
        // 対象画像領域指定オンオフ
        var imgFieldOnOff = false;
        let idx = -1;

        // ボタン
        var titleBtn;

        // 2022追加 追加canvasについてid取得用
        var icons_map = null;
        // CANVAS範囲配列初期設定
        //for(var i = 0 ; i < canvasNum; i++){
    
        //}
        // デフォルトのマウスポインター値
        var defMousePointer = document.body.style.cursor;
        var mouseDownOnCanvasIconBool = false;

        // 元のアイコンの大きさの縦横値
        var pre_vertical = 0;
        var pre_horizonal = 0;


        // スケールに対してアイコンの中央の座標
        function imgMidPoint(pointX, pointY, imgUrl, scale){ // 使われていない
            var arrIconWH = naturalIconSize(imgUrl);
            return array(pointX + ((arrIconWH[0]/2)*scale), pointY + ((arrIconWH[1]/2)*scale));
            
        }
        // スケールに対してアイコンの大きさを返す
        function imgScaleWH(imgUrl, scale){// 使われていない
            var arrIconWH = naturalIconSize(imgUrl);
            return array((arrIconWH[0])*scale, (arrIconWH[1])*scale);
        }
        // スケールを返す
        function getScaleOfMap(targetImage){ // 使われていない
            //width = targetImage.naturalWidth;
            //height = targetImage.naturalHeight;
            var width = targetImage.width();
            var height = targetImage.height();
            var squareEdge1 = width >  height ? height : width;
            var sEArr = getDefaultMapSize();
            var squareEdge2 = sEArr[0] > sEArr[1] ? sEArr[1] : sEArr[0];
            scale = (squareEdge1 / squareEdge2);
            return scale;
        }
        function getDefaultMapSize(){ // 使われていない上記関数でのみ
            var defWH = window.parent.document.getElementById("_customize-input-my_map_size").value.split(',');
            var defW = parseFloat(defWH[0].replace("px",""));
            var defH = parseFloat(defWH[1].replace("px",""));
            return array(defW, defH);
        }
        function iconReIndex(index){// 1 リサイズイベントでのみ
            console.log("iconReIndex");
            //var iconImg = new Image();
            //iconImg.src = document.getElementById("photo_path" + index).value;
            console.log(document.getElementById("photo_path" + index).value);
            console.log("iconImg.src:" + document.getElementById("photo_path" + index).value);
            //return [iconImg.src, iconImg.naturalWidth, iconImg.naturalHeight];
        }
        // 座標を取得 使われていない　下記関数のみ
        function point_location(id){
            var hid_size = document.getElementById("point_loc"+id).value;
            var ver_hor = hid_size.split(',');
            pre_vertical = parseFloat(ver_hor[0].trim());
            pre_horizonal = parseFloat(ver_hor[1].trim());

        }
        // 現在のCanvasの大きさに対して拡大・縮小が必要ならば拡大・縮小時に縦横に対して正方形の形でアイコン画像の拡大・縮小をする
        function photoMag(id){
            point_location(id);
            var mag_origin = pre_vertical > pre_horizonal ? pre_horizonal : prevertical;//アイコンの正方形の大きさ

            var client_w = document.getElementById('icon_map').clientWidth;// 今現在のCanvasのサイズ width
            var client_h = document.getElementById('icon_map').clientHeight;// 今現在のCanvasのサイズ height
            var mag_client = client_w > client_h ? client_w : client_h;// 正方形の大きさ

            return (mag_client / mag_origin);
        }
        // 座標を取得
        function point_location(id){
            var hid_size = document.getElementById("point_loc"+id).value;
            var ver_hor = hid_size.split(',');
            pre_vertical = parseFloat(ver_hor[0].trim());
            pre_horizonal = parseFloat(ver_hor[1].trim());

        }
        function iconOriginMidPoint(origin_x, origin_y, path){ // 使われていない
            var icon_size = naturalIconSize(path);//アイコン画像の大きさ取得
            var origin_mid_x_point = (icon_size[0] / 2) + icon_x_point;//icon_x_pointはアイコンの左上座標
            var origin_mid_y_point = (icon_size[1] / 2) + icon_y_point;//同上
            return array(origin_mid_x_point, origin_mid_y_point);
        }
        // 要素からそのアイコンの座標取得
        function pointGetter(id){
            var po_loc = document.getElementById("point_loc"+id).value;
            var raw_point_x_y = po_loc.split(',');
            var point_x_y = [parseFloat(raw_point_x_y[0].trim()), parseFloat(raw_point_x_y[1].trim())];
            return point_x_y;
        }
        // 現在のcanvasの実際の大きさ
        function curCanvasSize(){
            var client_w = document.getElementById('icon_map').clientWidth;
            var client_h = document.getElementById('icon_map').clientHeight;

            return [client_w, client_h];
        }
        function naturalIconSize(path){
            var element = new Image();
            element.src = path;
            icon_width = element.naturalWidth;
            icon_height = element.naturalHeight;
            return array(icon_width, icon_height);
        }
        window.addEventListener( 'load', function () {
            console.log("window load");
        }, false );







        // img要素から
        function imageRefCheck(){ // マウスオーバー処理でのみ
            console.log("imageRefCheck");
            var imgRange = new Array();
            var element = new Image();
            var imgClass = document.getElementsByClassName("photo_path");
            for(var i = 0; i < imgClass.length; i++){
                if(imgClass[i].value) ;
                element.src = imgClass[i].value;
                if(element.width) element.width = 0; 
                if(element.height) element.height = 0; 
                imgRange.push([element.width*scale, element.height*scale]);
            }
            return imgRange;
        }

        // img要素からCanvasに画像を転送(ロード時＆リサイズ時) 変更必要
        function resizePhoto() // リサイズして貼り付けるだけの処理
        {
            console.log("resizePhoto");
            cvsStyle = window.getComputedStyle(new_canvas);
            targetImage = document.getElementById('targetImage');// 変更 20230406
            if (targetImage) {
                // 下記はターゲット画像のもともとの大きさになってしまうのでここは変える

                
                
                if(lastWidth > 0 || lastHeight > 0)
                {
                    //拡大後と拡大前で寸法が違うために掛けなおし
                    pointX = pointX * ( parseFloat(cvsStyle.width.replace("px","")) / lastWidth );// 変更必要
                    //同上
                    pointY = pointY * ( parseFloat(cvsStyle.height.replace("px","")) / lastHeight); // 変更必要
                    //alert('resize:' + (pointX) + ',' + (pointY));
                    if(lastFocusField)
                    {
                        // カスタマイザーの対象の文字フィールドに座標入力
                        lastFocusField.value = pointX / parseFloat(cvsStyle.width.replace("px","")) + ',' + pointY / parseFloat(cvsStyle.height.replace("px",""));
                        var arrIndex = (parseFloat(lastFocusField.id.replace('_customize-input-my_loc', ''), 10));
                        arrShapes[arrIndex - 1] = lastFocusField.value;
                        console.log("lastFocusField.value" + lastFocusField.value);
                        console.log("arrShapes:" + arrShapes);
                    }
                }
                // 対象Canvasの大きさを固定　ここも変える
                new_canvas.width = installed.offsetWidth;
                new_canvas.height = installed.offsetHeight;
                // Canvasの大きさを固定し画像を再度貼り付け治す処理
                putImageToCanvas(width, height);
                // 一番最初に取得した要素の横幅、縦幅を再度取得
                lastWidth = parseFloat(cvsStyle.width.replace("px",""));
                lastHeight = parseFloat(cvsStyle.height.replace("px",""));

    
            }
            loadCanvas();
        }
        // リサイズ対応 画像を取得し、再度貼り付け治す 画像本来の大きさになってしまっているので変えること
        function putImageToCanvas(width, height) {// targetElemをその場所に書くだけの処理
            console.log("putImageToCanvas");
            if(targetElem)
            testContext.drawImage(targetElem, 0, 0, width, height);
        }

        //　リサイズ対応　　一つのキャンバスにつき一回は必要な処理 再描写＆一番最初の描写
        function loadCanvas(){// loadShapePositionsで一括でCanvasに書くだけ
            console.log("loadCanvas");
            for(var i = 0; i < arrShapes.length; i++){
                // テキストフィールドから座標を取得
                if(arrShapes[i] && arrShapes[i].includes(',')){
                    // 座標に図形を書き込む　画像に書き込みたい

                    loadShapePositions(i + 1, pointX, pointY);
                    console.log("***");
                }
            }
        }
        // 図形を指定個所に書く locのhidden要素の書き換え
        function loadShapePositions(index, posX, posY){
            console.log("loadShapePositions");

            var icon_inst = new Image();
            var imgUrl = document.getElementById("photo_path" + (index)).value;
            icon_inst.src = imgUrl;
            console.log(imgUrl);
            if(testContext){
                 /*testContext.drawImage(icon_inst, (posX + parseFloat(cvsStyle.width.replace("px",""))), (posY + parseFloat(cvsStyle.height.replace("px",""))));*/
                 testContext.drawImage(icon_inst, posX, posY);
                 document.getElementById("point_loc" + (index)).value = posX + "," + posY;
                }
        }
        // リサイズ対応必要
        function attachUnitCanvas(newCanvas){
            if(testContext)
            for(var i = 0; i < indexNum; i++){
                var location = document.getElementsByClassName("location_point")[i];
                var image =  document.getElementsByClassName("photo_path")[i];
                if(location.include(",")){
                    var img = new Image();
                    img.src = image.value;
                    testContext.drawImage(img, posX, posY);
                }
            }
        }
        //　ユーザー側で必要な処理　クリック時にリンク先に飛ばす（カスタマイザーじゃないとき）
        function mouseDownListner(e) {
            console.log("mouseDownListner(e)");
            /*
            // 要素の短径を取得し、全体からのマウス位置に減算すると要素内でのマウスクリック位置
            var rect = e.target.getBoundingClientRect();
            //座標取得
            var mouseX1 = e.clientX - rect.left;
            var mouseY1 = e.clientY - rect.top;
            // 押下した座標が図形だった場合、リンク先に飛ぶ　±20は図形の大きさ
            for(var i = 0; i < arrShapes.length; i++){
                if(arrShapes[i] && arrShapes[i].includes(',')){
                    if (mouseX1 > parseFloat(arrShapes[i].split(',')[0]) * parseFloat(cvsStyle.width.replace("px",""))-20 && mouseX1 < parseFloat(arrShapes[i].split(',')[0]) * parseFloat(cvsStyle.width.replace("px","")) + 20) {
                        if (mouseY1 > parseFloat(arrShapes[i].split(',')[1]) * parseFloat(cvsStyle.height.replace("px",""))-20 && mouseY1 < parseFloat(arrShapes[i].split(',')[1]) * parseFloat(cvsStyle.height.replace("px","")) + 20) {

                            // リンクに飛ばす処理
                            if(lnk_elems[i].value)
                            location.href = lnk_elems[i].value;// canvas内の図形のリンクに飛ばす
                        }
                    }
                }
            }*/
            if(mouseDownOnCanvasIconBool){
                // リンクに飛ばす処理
                if(lnk_elems[i].value)
                location.href = lnk_elems[i].value;// canvas内の図形のリンクに飛ばす
            }
        }

        function mouseDownListnerForm(e) {

            var img = new Image();
            img.src = 'https://placehold.jp/150x150.png';
            if(new_canvas)
            testContext = new_canvas.getContext("2d");

            console.log("mouseDownListnerForm(e)");
            console.log(e.target.id);
            if(e.target.id.includes("my_loc")){
                console.log("ここに処理が入ります");
                locSwitch = true;
                locTargetValue = e.target;
                if(new_canvas !== null && testContext !== null && testContext !== undefined){
                    locPointSwitch = true;
                    console.log("この処理がしたい");
                }
            }
        }

    
        // pointXとpointYにクリックした要素内の座標を格納
        function getCanvasPointXY(e){
            console.log("getCanvasPointXY(e)");
            var rect = e.target.getBoundingClientRect();
            pointX = (e.clientX - rect.left);
            pointY = (e.clientY - rect.top);
            if(locSwitch){
                locTargetValue.value = (pointX) + ',' + (pointY);
                var index = parseFloat(locTargetValue.id.substr(-1, 1));
                loadShapePositions(index, pointX, pointY);
                //let e_ch = new Event('change');
                let e_ch2 = new Event('input');
                //locTargetValue.dispatchEvent(e_ch);//カスタマイザーの公開ボタン
                locTargetValue.dispatchEvent(e_ch2);//カスタマイザーの公開ボタン
            }
            locSwitch = false;
        }
        // 座標を最後にアクセスしたテキストフィールドに反映させる。(カスタマイザー)
        function setXYPointToText(e){
            console.log("setXYPointToText(e)");
            if(lastFocusField) // 初期値なし
            if((lastFocusField.id.includes('_customize-input-my_loc'))){
            
                lastWidth = parseFloat(cvsStyle.width.replace("px",""));
                lastHeight = parseFloat(cvsStyle.height.replace("px",""));
                lastFocusField.value = pointX / lastWidth + ',' + pointY / lastHeight;
                var arrIndex = (parseFloat(lastFocusField.id.replace('_customize-input-my_loc', ''), 10));
                arrShapes[arrIndex-1] = lastFocusField.value;// 

                // arrIndexのImgUrlからImageクラスを生成する処理

                loadShapePositions(arrIndex, pointX, pointY);
            }
        }

        function savaShapePosition(index, posX, posY){
            document.getElementById("photo_path" + (index)).value = posX + "," + posY;

        }
        // デフォルト値 初期設定　hidden要素取得
        function loadPointPositions(){
            console.log("loadPointPositions");
            // リンク情報
            lnk_elems = document.getElementsByClassName("point_link");// widgetに追加するhidden input
            // XY座標
            var loc_points = document.getElementsByClassName("location_point");// widgetに追加するhidden input
    
            // 此処に追加
            // カスタマイザーのテキストフィールド上から反映
            for(var i = 0; i < arrShapes.length; i++){
                arrShapes[i] = loc_points[i].value;//クラスの集合から取得
                arrTField[i] = lnk_elems[i].value;//クラスの集合から取得　ここ以外(描画以外)で使う
                if(arrShapes[i] && arrShapes[i].includes(',')){
                    loadShapePositions(i+1, pointX, pointY);
                }
            }
        }
    
        // 子要素の走査 対象要素のセット
        function scanChildElems(elem, tagName, path, minMax, layerNum){
            console.log("scanChildElems");
            var addedPath = path;
            var result = false;
            var addedTagAttr = "";
    
            //　タグ名追加
            if(elem.nodeName){
                // addedTagAttr += "{\#tag:" + elem.tagName + "}";
                addedTagAttr += "{\#tag:" + elem.nodeName + "}";
            }
            // id名追加
            if(elem.id){
                addedTagAttr += "{\#id:" + elem.id + "}";
                if(addedPath !== ""){
                    addedTagAttr = addedTagAttr + "=>" + addedPath;
                }
                return addedTagAttr;
            }
            // class名追加
            
            if(elem.className){
                let doc = document.getElementsByClassName(elem.className);
                idx = -1;
                for(var i = 0; i < doc.length; i++){
                    if(doc[i] ===  elem)
                    idx = i;
                }
                idx = doc.indexOf(elem);
                addedTagAttr += "{\#cls:" + elem.className + "[" + idx + "]" + "}";

                if(addedPath !== ""){
                    addedTagAttr = addedTagAttr + "=>" + addedPath;
                }
                
                return addedTagAttr;
            }
            if(addedPath.length > 0)
            /*addedPath += "=>";
            addedPath += addedTagAttr;*/
            addedTagAttr = addedTagAttr + "=>" + addedPath;
            addedPath = addedTagAttr;
    
            if(minMax.toLowerCase() == "min" && addedPath.indexOf(tagName) >= 0){
                // 指定したタグが入っている場合
                //return addedPath;
            }else{/*
                if(elem.firstElementChild){
                    addedPath = scanChildElems(elem.firstElementChild, tagName, addedPath, minMax,layerNum++);
                }*/
                // 指定したタグが入って居なく深堀が必要
                /*if(addedPath.indexOf(tagName) < 0){
                    if(layerNum > 0){
                        if(elem.nextElementSibling){
                            addedPath = scanChildElems(elem.nextElementSibling, tagName, Path, minMax, layerNum);
                        }
                    }*/
                if(elem.parent){
                    if(addedPath.indexOf(tagName) < 0) addedPath = scanChildElems(elem.parent, tagName, addedPath, minMax,layerNum++);
                    else return addedPath;
                }
                
                    //alert("2:" + elem.nextElementSibling.nodeName);
                
            }
            return addedPath;//関数のネスティング(再起処理)で全部の文字列が返る
        }
    
        // 要素から該当タグの要素取得
        function targetTAGChildParser(targetElem, remainTagArr, terminal){ // remainTagArrは
            console.log("targetTAGChildParser");
            var elemBox = targetElem;
            if(elemBox !== null && elemBox.tagName && elemBox.tagName == terminal){
    
                return elemBox;
            }else if(elemBox !== null && elemBox.tagName == remainTagArr[0]){// 同じ要素でも子要素
                if(remainTagArr.length > 0 && targetElem.firstElementChild.tagName !==  remainTagArr[1]){
                    elemBox = targetTAGChildParser(targetElem.firstElementChild, remainTagArr.slice(1), terminal);
                }else{
                    if(elemBox !== null && elemBox.nextElementSibling){
                        elemBox = targetTAGChildParser(elemBox.nextElementSibling, remainTagArr, terminal);
                    }else{
                        return elemBox;
                    }
                }
            }
            return elemBox;
        }

        function compareClassEach(clss1, clss2){
            for(var i = 0; clss1.length; i++){
                for(var q = 0; clss2.length; q++){
                    if(clss1[i] === clss2[q]) {
                        return [i, q];
                    }        
                }
            }
            return [];// falseはあり得ない
        }

        // 文字列に入っている該当の要素から要素取得
        // canvas要素を追加する
        function initCanvasField(){
            console.log("initCanvasField");
            const regexpID = /\{\#id:(.+)\}/;
            const regexpCLS = /\{\#cls:(.+)\[?(\d*)\]?\}\{/;
            const regexpTAG = /\{\#tag:([A-Z]+)\[?(\d*)\]?\}/;
            var u = 0;
            if(window.parent.document.getElementById("_customize-input-my_control"))
            oneCanvas = window.parent.document.getElementById("_customize-input-my_control");// 修正必要
            console.log("initCanvasField Onecanvas" + oneCanvas);
            // 上記に対してCANVASタグを追加する
            //まず対象を取得する
            console.log("条件分岐1");
            console.log("oneCanvas" + oneCanvas);
            if(oneCanvas !== null || oneCanvas !== undefined || oneCanvas !== ""){
                
                if(oneCanvas.value !== null && oneCanvas.value !== "" && oneCanvas.value.includes("=>")){
                    console.log("条件分岐1通過");
                    arrPathCanvas = oneCanvas.value.split("=>");// 一つ一つの親子要素について配列順に入れなおす
                    var candyElements;
                    arrTagPathCanvas = [];
                    for(var o = 0; o < arrPathCanvas.length; o++){
                        console.log("for文1通過" + o);
                        var tagMatch = regexpTAG.exec(arrPathCanvas[o]);
                        if(tagMatch && tagMatch.length > 0){
                            arrTagPathCanvas.push(tagMatch[1]);
                        }
                    }
                    console.log("arrTagPathCanvas:" + arrTagPathCanvas);
                    console.log("oneCanvas.value: " + oneCanvas.value);
                    if(arrPathCanvas !== []){
                        console.log("条件分岐2通過");
                        for(var q = 0; q < arrPathCanvas.length; q++){
                            console.log("for文2通過" + q);
                            if(arrPathCanvas[q] === null)
                                break;
                            var idMatch = regexpID.exec(arrPathCanvas[q]);
                            console.log("arrPathCanvas: " + arrPathCanvas);
                            var matchStr = arrPathCanvas[q];
                            console.log(typeof arrPathCanvas[q]);
                            console.log("matchStr" + matchStr);
                            var clsMatch = regexpCLS.exec(matchStr);
                            var tagMatch = regexpTAG.exec(arrPathCanvas[q]);
                            if(idMatch)
                            {
                                let idWord = regexpID.exec(arrPathCanvas[q]);
                                console.log("targetElem 正常代入");
                                targetElem = document.getElementById(idWord[1]);// 正規表現の二つ目の要素が()に入っている値を取得
                                
                            }

                            if(clsMatch){
                                console.log("条件分岐3通過 (class)");
                                console.log("clsMatch[0]" + clsMatch[0]);
                                console.log("clsMatch[1]" + clsMatch[1]);
                                clsMatchs = clsMatch[1].split(' ');
                                var tarFromCls = "";
                                //for(var i = 0; i < clsMatchs.length; i++){
                                    console.log("for文3通過");
                                    //console.log(" clsMatchs "+clsMatchs[i]);
                                    //if(tarFromCls.length === 0) tarFromCls += clsMatchs[i];
                                    //else tarFromCls += " " + clsMatchs[i];
                                    //console.log("tarFromCls" + tarFromCls);
                                    if(clsMatchs !== ""){
                                       // console.log("document.getElementsByClassName(clsMatchs[i]).length" + document.getElementsByClassName(clsMatchs[i]).length);
                                        //if(document.getElementsByClassName(clsMatchs[i]).length > 1){
                                            console.log("class配列取得");
                                            var clsArrNum = /\[(\d+)\]/;
                                            var num = clsArrNum.exec(clsMatchs[1]);
                                            tarClassName = clsMatch[1].replace(clsArrNum, "");
                                            console.log("tarClassName " + tarClassName);
                                            if(num){
                                                console.log("通過したいところ通過");
                                                console.log("classArrNum:" + num[1]);
                                            
                                                var targetElems = document.getElementsByClassName(tarClassName);
                                                targetElems = [].slice.call(targetElems);
                                                targetElem = targetElems[num[1]];
                                            }else{
                                                console.log("特定できているか？");
                                                targetElem = document.getElementsByClassName(tarFromCls);
                                            }
                                            if(targetElem.tagName === "IMG"){

                                                break;
                                            }
                                        //}else{
                                        //    console.log("クラスから要素取得できませんでした。");
                                        //}
                                    }
                                //}
                            }
                            if(tagMatch){

                                var arrTagName = [];
                                var remaingIndex = 0;
                                for(var v = q; v < arrPathCanvas.length; v++)
                                { // タグの配列に直す
                                    // 正規表現でタグ名取得する
                                    arrTagName[remaingIndex] = arrTagPathCanvas[v];
                                    remaingIndex++;
                                }
                                targetElem = targetTAGChildParser(targetElem, arrTagName, "IMG");
                                //targetElem = targetTAGParentParser(targetElem, arrTagName, "IMG");
                                break;
                                //}

                            //targetROOT = arrPathCanvas[q+1];// 次の子の要素をセット　この時にtargetElemには目的の子要素までの実際の要素が入っている
                            }   
                        }
                    }
                }else if(oneCanvas.value !== null && oneCanvas.value !== ""){
                    console.log("active_pre_process Match Error");

                
                }
            // 上記で取得したIMGタグについてCANVASタグを設定

                new_canvas = document.createElement('canvas');
                console.log("CANVAS設定前のtargetElem:" + targetElem);
                if(targetElem !== undefined)
                for(var i = 0; i < targetElem.length; i++)
                    console.log("CANVAS設定前のtargetElem:" + targetElem[i].tagName + targetElem[i].className);
                else{

                }
                if(targetElem){
                    console.log("CANVAS設定");
                    installed = targetElem.parentNode;
                    console.log(installed);
                    console.log("親のタグ名" + installed.tagName);
                    installed.appendChild(new_canvas);

                    targetElem.id = "targetImage";

                    targetImage = document.getElementById('targetImage');
                    new_canvas.id = "icon_map";
                    installed.style.position = "relative";
                    new_canvas.style.position = "absolute";
                    new_canvas.style.position = "block";
                    new_canvas.style.zIndex = "999";
                    new_canvas.style.top = "0px";
                    new_canvas.style.left = "0px";
                    new_canvas.width = installed.offsetWidth;
                    new_canvas.height = installed.offsetHeight;
                    console.log("ここからCANVASデフォルトのサイズ");
                    console.log(new_canvas.width);
                    console.log(targetImage.width);
                    console.log(new_canvas.height);
                    console.log(targetImage.height);
                    new_canvas.addEventListener('load', function(){
                        console.log("new_canvas_loaded");
                        resizePhoto(targetImage);
                        loadCanvas();
                    }, false);
                
                    new_canvas.addEventListener('click', function(e){
                        //resizePhoto();
                        console.log("new_canvas_clicked");
                        if(custom_mode && locPointSwitch){
                            getCanvasPointXY(e);
                            setXYPointToText(e);
                            locPointSwitch = false;
                        }
                    }, false);
                    if(!custom_mode)
                    new_canvas.addEventListener("mousedown", mouseDownListner, false);// canvas内のリンクに飛ばす
                    //putImageToCanvas(targetElem,new_canvas.width, new_canvas.height);
                }
            }else{





            }
            

            return new_canvas;
        }
    
        // マウスオーバー処理
        document.addEventListener("mouseover", function(e) {
            console.log("e.target mouseover"); //event.targetの部分がマウスオーバーされている要素になっています
            
            icon_point = imageRefCheck();
            if(new_canvas == e.target)
            {
                // 該当座標でマウスポインターの変換

                // mouseDownListenerと同じ処理 そっちを削除するべきかもしれない
                // 要素の短径を取得し、全体からのマウス位置に減算すると要素内でのマウスクリック位置
                var rect = e.target.getBoundingClientRect();
                //座標取得
                var mouseX1 = e.clientX - rect.left;
                var mouseY1 = e.clientY - rect.top;
                // 押下した座標が図形だった場合、リンク先に飛ぶ　±icon_point/2は図形の大きさ
                for(var i = 0; i < arrShapes.length; i++){
                    if(arrShapes[i] && arrShapes[i].includes(',')){
                        if (mouseX1 > parseFloat(arrShapes[i].split(',')[0]) * parseFloat(cvsStyle.width.replace("px",""))-icon_point[i][0]/2 && mouseX1 < parseFloat(arrShapes[i].split(',')[0]) * parseFloat(cvsStyle.width.replace("px","")) + icon_point[i][1]/2) {
                            if (mouseY1 > parseFloat(arrShapes[i].split(',')[1]) * parseFloat(cvsStyle.height.replace("px",""))-icon_point[i][0]/2 && mouseY1 < parseFloat(arrShapes[i].split(',')[1]) * parseFloat(cvsStyle.height.replace("px","")) + icon_point[i][1]/2) {
                                
                                mouseDownOnCanvasIconBool = true;

                                if(icon_point != null)
                                {
                                    icon_point.style.cursor = "pointer";
                                }
    
                                // リンクに飛ばす処理
                                //if(lnk_elems[i].value)
                                //location.href = lnk_elems[i].value;// canvas内の図形のリンクに飛ばす
                            }else{ // 図形を指していない場合はマウスポインターを元に戻す
                                icon_point.style.cursor = defMousePointer;
                                mouseDownOnCanvasIconBool = false;
                            }
                        }
                    }
                }
            }
        });    

        window.addEventListener('resize', function(){
            console.log("イベントresize");
            resizePhoto(targetImage);
            //loadPointPositions();
    
            //これは場所の座標のテキストフィールド　全てに実施
            for(var i = 0; i < arrShapes.length; i++){
                if(arrShapes[i] && arrShapes[i].includes(','))
                var icon = iconReIndex(i+1);


                loadShapePositions(i + 1, pointX, pointY);
                console.log("***");
            }
        }, false);
        /*
        var intervalId = setInterval( function(){
            resizePhoto();
            clearInterval( intervalId ) ;
        }, 500 );
        */
    
    
        function windowDomLoaded(){
            // 画像要素キャンパスの追加　一番最初に必要
            console.log("読み込み完了");
            new_canvas = initCanvasField();

            /*　複数の画像に対して実装する予定だった。カールセル対応*/
            const regexpSEC = /=>/g;

            if(url.includes('customize'))
            {
                const element = document.getElementById('option_menu');
                if(element != null) 
                element.remove();
                custom_mode = true;
            
                
                if(window.parent.document.getElementById("_customize-input-my_control"))
                oneCanvas = window.parent.document.getElementById("_customize-input-my_control");// iframeしているときは外側に走査走らないためwindow指定
                if(oneCanvas){
                    // 上記に対してCANVASタグを追加する
                    //まず対象を取得する
                    var arrExps = new Array();
                    if(oneCanvas.value.lastIndexOf('=>') > 0)
                    {
                        if(arrExps=regexpSEC.exec(oneCanvas)!=null){
                            var startIndex = arrExps.index; // インデックスを保存
                            arrPathCanvas = split("=>", arrExps.index + 1)
                            console.log("windowDomLoaded 条件分岐3");
                        }
                    }else{ //パスを表すテキストがなかった時

                    }
                    if(new_canvas !== undefined){
                        testContext = new_canvas.getContext("2d");
                        //testContext.beginPath();
                    }
                }
                if(custom_mode)
                {
                    //指定する(画像領域)
                    // カスタマイザー要素に該当の座標を記述する(Canvasをクリックした際に最後にフォーカスを当てたテキストボックスに記述されるためのモノ)
                    oneCanvas.addEventListener('focus', function(e) {
                        lastFocusImgField = e.currentTarget;
                        //console.log(lastFocusImgField.id);
                        imgFieldOnOff = true;
                    }, false);

                    // 残処理
                    cvsStyle = window.getComputedStyle(new_canvas);
            
                    //resizePhoto();
                    //loadPointPositions();
                    //loadCanvas();
                }
                window.parent.addEventListener("mousedown", mouseDownListnerForm, false);// canvas内のリンクに飛ば
            }
        }
    

        document.addEventListener("DOMContentLoaded", windowDomLoaded, false);

        window.addEventListener('beforeunload', function(e) {
            e.returnValue = '保存忘れはありませんか？';
          }, false);

        //https://note.com/fuminon3745/n/n33184d12ce30
        // 指定した要素に対して場所をフィールドに記載し、色付けてわかるようにする。
        document.body.onclick = (e) => {
            console.log("document.body.onclick");
            windowDomLoaded();
            if(imgFieldOnOff){
                console.log("クリックの条件分岐1");
                // 他のアイテム(Canvas設定を削除)
                var removedClass = document.getElementsByClassName("active_pre_process");
                for(var i = 0; removedClass.length; i++){
                    if(removedClass[i].getAttribute('alt')){
                        
                    }else{
                        removedClass[i].setAttribute('alt', ' ');
                    }
                    removedClass[i].classList.remove("active_pre_process");

                    document.getElementById('icon_map').remove(); 

                    oneCanvas.value = "";

                }
                
                var rect = e.target.getBoundingClientRect();
                var elementUnderMouse = document.elementFromPoint(rect.left, rect.top);
                if(elementUnderMouse)
                if(elementUnderMouse.id !== null && elementUnderMouse.id !== undefined){
                    if((elementUnderMouse.id).includes("_customize-description-my_loc")){
                        setAddPoint = true;
                    }
                }
                if(setAddPoint){ 
                    lastFocusField.value= "" + rect.left + ", " + rect.top;// 座標を入力
                }
                setAddPoint = false;

                console.log(rect.left + ", " + rect.top + " :" + elementUnderMouse);
                //const range = document.createRange();
                //range.selectNodeContents(elementUnderMouse);
                
                //const selection = window.getSelection();
                //selection.removeAllRanges();
                //selection.addRange(range);
    
                var nodeChain = elementUnderMouse;
                //elementUnderMouse.style.backgroundColor = "#FFFF00";
                if(nodeChain !== null){
                    nodeChain.classList.toggle("active_pre_process");
                }

                lastFocusImgField.value = "";
                while(true && nodeChain !== null){
    
                
                    if(nodeChain.tagName){
                        if(lastFocusImgField.value !== "")
                        lastFocusImgField.value = "{\#tag:" + nodeChain.nodeName + "}" + "=>" + lastFocusImgField.value;
                        //　要素を繰り上がる
                        else
                        lastFocusImgField.value = "{\#tag:" + nodeChain.nodeName + "}";

                    }
                    if(nodeChain.id !== null && nodeChain.id !== undefined && nodeChain.id)
                    {
                        lastFocusImgField.value = "{\#id:" + nodeChain.id + "}" + lastFocusImgField.value;
                        break;
                    }
                    if(nodeChain.className !== null && nodeChain.className !== undefined && nodeChain.className){
                        let doc = document.getElementsByClassName(nodeChain.className);
                        console.log("class name: " + nodeChain.className);
                        doc = [].slice.call(doc);// argumentを配列に変える
                        //let idx = doc.indexOf(e.target);
                        idx = doc.indexOf(elementUnderMouse);
                        console.log("idx" + idx);
                        nodeChain.classList.remove("active_pre_process");
                        console.log("nodeChain.className:"+nodeChain.className);
                        if(nodeChain.className !== "active_pre_process" && nodeChain.className !== ""){
                            if(idx > -1)
                            lastFocusImgField.value = "{\#cls:" + nodeChain.className + "[" + idx + "]" + "}" + lastFocusImgField.value;                    
                            else{
                                 
                                var domdoc = document.getElementsByClassName(nodeChain.className);
                                if(domdoc.length > 1){
                                    idx = 0;
                                    for(var h = 0; h < domdoc.length; h++){
                                        if(nodeChain === domdoc[h])
                                        lastFocusImgField.value = "{\#cls:" + nodeChain.className + "[" + idx + "]" + "}" + lastFocusImgField.value;  
                                        idx++;  
                                    }
                                }else{
                                    lastFocusImgField.value = "{\#cls:" + nodeChain.className + "}" + lastFocusImgField.value;
                                }
                            }
                            break;
                        }
                        
                        nodeChain.classList.add("active_pre_process");
                    }
                    nodeChain = nodeChain.parentNode;
    
                }
                //対象のタグが上記で取得できないときは子要素を走査し、該当タグを探す
                /*console.log(lastFocusImgField.value);
                if(lastFocusImgField.value.includes("IMG")){
                    lastFocusImgField.value = scanChildElems(elementUnderMouse, "IMG", "", "min", 0);
                }
    
                nodeChain = elementUnderMouse;
                // ブロック要素走査しクラス追加 追加される画像の要素を塗りつぶし、わかるようにする
                for(var i = 0; i < 50; i++){
                    if(nodeChain.tagName){
                        console.log(nodeChain.tagName);
                        if(blockElems.indexOf(nodeChain.tagName) >= 0)
                        {
                            nodeChain.classList.add('active_canvas_target');
                            break;
                        }
                        if(nodeChain.tagName != 'BODY'){
                            
                        }else{
                            break;
                        }
                    }
                    nodeChain = nodeChain.parentNode;
                }*/
                imgFieldOnOff = false;
                window.parent.document.getElementById("_customize-input-my_control").value = lastFocusImgField.value;
                initCanvasField(); // 再設定
            }
        }
        </script>
    CANVAS;
    echo $javascript_EOL;
    }



    
 // end of class

/*


要件
指定した要素にリンク付き画像を表示させたままにしたい

既存の関数は()内で記載

全体の初期処理（DOMContentLoaded）
initCanvasField

保存していたトップ画像などの要素を取得（Canvasロード時）（eventListener Load）

resizePhoto>putImageToCanvas
           >loadCanvas[図形貼り付け]>loadShapePostions[図形を指定の場所に張り付ける]




死にたい。自分ではそう願ってる。そしてそうあるべき。自分がそうあるべきだけ。他のものはそれを一度も大事にしてくれないのに、

そういうふうにしか世界は流れない。

どうしようもない自分。どうしようもない世界。



生きていたくない。死を恵んでください。神様、仏様


神は死を望んでるものを自ら手ずから殺すことなく、他人の手を幾らでも借りて殺しに来る。


------------------------------------------------以下、小説メモ----------------------------------------------------



昔からあったと思う、クラスの誰かもが答えられない問題を答えるのが正しいというような風潮は。
それが自分の場合は病的で許されないことのように思えた。
だから誰しもが思っていて言わないであろうことはいうのが正しいようにも思えたし、
それがわからなかった。
他の人間が言わないでいるにはちゃんとした理由があったし、
逆に他人が思っているに違いないとして言うことが多かった。強迫的にそれを言ってしまう病気だった。
だから対面で自分を疑う者には口でありもしない材料を作った。
誰が正しいとかではない。自分自身の悪感情に、それに従う相手が正しくないことの証明になると思ったからだ。
相手が間違っているならば自分が正しい。そうなる論理だった。

何でも自分の一番欲しいものは買ってもらったことがない。親は幼少期のプラレールを買ったことを持ち出す。そうじゃない、それは幼少期の話だし、
他の子はもっと高い年数まで一番欲しいものを買ってもらっているからだ。
俺はゲームボーイが欲しかったんだ。他の子はみんな自分が欲しいものを買ってもらっていて、自分の居場所なんかなかったし、遊び場所もなかった。
友達なんかいなかったし、横から眺めるだけの存在だった。それが疎ましくて呼ばれなかったことさえある。
結局、親から自分の人生を有機物としてではなく無機物として扱われたのが問題だったと思う。
親にとってはすべて適当に扱えば済む話で、子供が人としてのデリケートな部分にまで、そうした。

自分の世界というものが親には許せなかった。
自分の感情を親に見せることができなかった。みせれば暴力や殺人に発展するからだ。
親はちゃんと受け止めなかった。笑ったり、支配的でそれがそれ自体が自己の尊厳であるかのようにふるまった。
だから自分の要求は許されなかった。男性的なものも環境を理由にして許さなかったが、母は男性的なものが嫌いだった。
そのような時、自分が男性であるのにかわいい女の子のようなふりをして従順そのものであるように見せなければならなかった。
だから危害を加え続ける他者に対する適切な態度が身につかなかった。

高校生の時、強迫性障害はひどかった。
例えば帰り道に路地裏で人を脅して持っているはずのないナイフで脅して財布を取ったんじゃないかとか。そういって、その路地裏まで見に行ったり、
持っているはずのないキャッシュカードを道端に落としたのではないかと思い、帰り道を往復するなどの奇行があった。
殆ど正常な前後の記憶が抜け落ちてる状態だった。その状態で大橋や千田はでっち上げて犯罪やストーカー、万引き犯、強姦犯などに俺を仕立て上げた。
誰かが俺を疑い詰めたときにやってもいないことを自分から自白してしまうのは中学からだった。

母親などからの自分の外側の世界、ではなく自分に対して注意や責め立てるのが行くのは最初からだった、認知症の父方の祖母を１年と２か月程自宅で介護した空白期間を
経て、段階的なものは飛ばされた。そのせいもあってか、自分に対してちゃんとした認識を持つことは難しかったし、母に自分の精神的な問題を言うのは難しかった。
母親はダンベルを持つことも俺に許さなかった。「どこか私の知らないところに行ってしまいそうだから」「細身の男性が好きだから」と子供の成長よりも自分の過干渉を優先した。
毅然とした対応をするのにも自分にとっては筋力が必要だった。なのにもかかわらず、そのままで毅然とした対応を取れと言ってきた。
だから体を鍛えることができなかった。それがまた自分を自暴自棄にし、解離性障害を強めた。相手を殴らなければいけない場面で自分の頬を殴るようになった。

数年前、障碍者手帳や薬による治療を受けたときも、表では何も言わなかったが、今になってそれが自分の思想をあきらめたかのように言い出した。
「じゃあ、なんでもやればいい」と私は薬や息子が障碍者であることを認めることに反対だったとでも言いたげだった。
だからそれまでも、治療にも障碍者として福祉を受けることにも口ではそうでないといっていても、俺の聞こえるところでの「影」ではそういってきて
邪魔をしてきた。
箱庭療法だといって、現実で放置したり、健常者同様になるように命令してきた。何があっても何も起きはしないといって、無責任なことを言った。
それは俺にやってはいけないことを煽り立てるようだった。その癖、テレビで自己責任といったワードが流行りだすと何があっても自己責任だといい始めた。
発達障害の専門書を渡しても自分通りの解釈で、俺が言いたいところを言っている登場する人物の志向を捻じ曲げて、自分の書かれていない逆の解釈に捻じ曲げた。
その癖テレビでそのことが取り上げられると、それを信じた。

許せなかった記憶があるのはやはり自身の精神疾患についてだった。
唾液をため込む癖があった。これは同級生の加藤からナメクジの粘液を口に含まされたことに由来する。

明らかに強迫性障害の症状で蛇口の出し入れをしているのに父親が怒鳴りつけたり、
俺のことをレイプ犯にのように罵りその場にいる人間に対して吹聴した。清水と米山。
清水からは殴られるなどされた。そして俺の後輩に俺がレイプ犯であると吹聴した。
米山からは執拗に暴力を受けた。（蜂合わせたときに駅のトイレの洗面台に頭をぶつけられたり、追いかけられて蹴るや殴るなどの暴行や怪談から突き落とされるなどした。）
主観的に感じたことは俺が死ぬまでそういうことをするのだろうと。実際「死ね」や「殺してやる」、この二人は俺がいつまで続くんだといったとき「お前が死ぬまで」と言ったりさえもした。
清水は学内や学外の人間にまで俺のことを吹聴して執拗な暴力や脅迫などを受けさせた。
母と父は精神科の受診に対して全く協力的でなかった。


自分の人生には味方が居なかった。いつだって孤立するだけだった。
自分の存在そのものに関して、そういう風に揶揄したり恣意的にいる存在に対してどうすることもできなかった。
それは親でさえ兄弟でさえそうだった。
結局自分の存在よりも、自分を排除するための要求が価値として上回った。助からなかった。誰からも大事にされた記憶がない。
全て二の次だった。

子供は3人も育てられなかった。
パソコンは１台しかなかったからだ。友達を呼んでも仕方なかった。

誰からも見下げた存在だったのは、自分からそうしたともいえるし、最初からそうだったともいえる。
自分は運動が女子よりもできなかった。泣かされたことだってある。危害を加えても反撃できない存在だと認知されていたと思う。それが嫌だった。
だから親が勉強を頑張ればいいだなんて、全然説得にかけていたし、勉強だって他の才能や環境の用意された子にはとても追いつかなかった。

親父ががんになって転校した先は地獄だった。しかし、卒業アルバムを捨てたのは後悔している。実名がわからなくなったからだ。
第三者の見てないところで、あるいは見ていておみて見ぬふりをするか、その場から離れて証人にさえなろうとしなかった。
田海の俺に対する扱いがクラスの標準になった。

人としての尊厳が周りから許されない存在だった。成人式の日、セレニリーの時に声を張り上げていないのに誰かが俺が声を張り上げたと嘘をつかれて執拗に
そうだと決めつけられた。

一番許せない川端紗記について書こうと思う。
ベトナムの戦争にアメリカが介入してめちゃくちゃにして行ったように此奴は俺の人生をめちゃくちゃにした。
俺はこのころから精神心疾患もちで、今もある解離性障害だったと思う。

オメーの仲間が嘘吹聴したから俺がその通りにやったんだよ
前後の記憶がない病気だったから感覚的にやったのかやってないのか分からなかったんだよ
画鋲を水道に入れぢのお前の仲間が抜かしやがって
それを見たお前が強制的にオレにそれを強要しやがったよな
それで目に当てやがったんだよ
それをまたその仲間よんで集団リンチのために証拠にしやがったんだよ

その時点で自分は心の病で精神病だといったのにもかかわらずだ。
その自分に対して反発心の出るようなやり方でしか、やらなかった。
完全に木曾に加担するやり方で俺に対して排除をした。
そういう状態の俺に対して、明らかに公平性の欠くやり方で、自白を強要した。
自分に対して暴力をふるったことのある女性で輪して逃げられないようにし、自白を強要した。
自分は周りの人間が恣意的である方向にしか発言できなかった。


殺すと脅迫を受けた。

顔の利くものに事実として吹聴して明らかに収拾のつかないやり方て俺のことを追い詰めた。店の定員二でさえも鉢合わせたときに吹聴した。
それまで関係の悪くなかった様々な人から因縁や暴力を受け続けることになった。
また本人も自分と関係のあるもの人間に積極的に吹聴して回った。
本人の交際相手から川口駅で腹を殴るなどの暴力も受けた。
黒川からは洗面器に二回ほど頭をぶつけさせれた。
新川優菜からは出会い頭に殴るや催涙スプレーを掛けられるなどをされた。
野沢愛花からは夜道にカッターナイフで切りつけられるや、商品を私物の袋に入れて万引き犯に仕立て上げられた。
女の方の清野は開いたATMから現金１万円をかすめ取られた。
卓球部の竹田や酒野からは絶対に許さないと恐喝を受けた。
叩くや蹴る。身体的に危害を加える。ズボンを脱がされ、トイレで個室に追い込まれて盗撮されるなど、万引き犯に吹聴されたり、商品の陳列棚に投げ出されるなどもされた。


強い奴には手を出せないというよりも好意的な解釈を無条件でする。その癖、ちょっとした想像力さえない。いじめがあったことさえ耳に入れてるのか耳に入れてるんだろうか知らないが、
何せ好意的だ、それが問題でなんて考えない。原因はすべてそいつに合って、という話になるらしい。それは正しいんだろう。一部においては。

周囲の判断が自分の判断で責任がないことに対して、なぜか一層自分の力や残酷さ、判断しなくていいからただ自分の力や圧力を掛けようとする。
風向きが変わるのは認めない。自分の判断肉類があったことや非があることを自分自身が責めなくてはいけないからだ。考え直そうとは思わない。

多くの人から「死ね」や「殺したい」と言われた。それに俺がそうなるのに加担した人間でさえ、俺を攻撃するのは理解の前に理由にしかなkらなかっただろう。
それが事実でなくてもよい。加担できればいい。尊厳など与えない。そういうことしたやつが悪い。そういう人間なんだろう。だからこれも是に違いない。
まったく疑いようがない。
そんな風に乗っかってきた吉田櫻子。お前は許せない。その癖、お前はうまくいっている。俺がそうなるのに加担したくせに。
お前は悪いんじゃなく邪悪なんだ。


穿った考え方だろうか？病院の先生なら患者や他者から「許さないぞ」と言われても。客観的に自己の命の価値について証明する材料がある。
俺にはそれがいつまでもなかった。他人からいじめられ都合の良い存在だった。例えば生きていればいいこともある、だからいつでもやり直せるだなんて
自分が責めている存在がそう思っているなんて、誰も思わないだろう。自己の命に対して、他人の自己に対する命の価値が乖離しているだろうと思う。
逆に他人が責めて自分に対してそう思うことを、自分でもそう思うようにしていたら、これは自分の命に対して他人が思うものや尊厳の踏みにじりにそうとしか思っていないことに近づいてると言っていいのではないか？
だから、大量虐殺が起きる。鉄砲玉のような命だ。それが向けるべきべき人間に対して向いてない。


川端紗記、お前は自分がそうしておきながら、相手がそれを自分にするのが許せない癖に
白黒はっきりつけろだの偉そうに抜かす性格だったな。
病気の症状で親の要求を突っぱねられず、自分のことが話せない俺に向かって、
自白を強要し続けた。俺は真っ白なのにもかかわらず自分がやったとしか、病気の症状でそういう風に言うことしかできなかった。
お前はこともあろうか俺のことを元からいじめていた北島や板倉、田海、笠原、その他大勢、人間のいうことを耳打ちで信じ込み更にお前の持ちうる集団によって
暴力を加速させた。お前はのうのうと生きている。


・土岐駿斗



・加藤祐一



・北島利輝・田海じょうじ




・板倉・笠原



・仲村ゆみ


・千田祥子
やってないことや自分が被害者なのに加害者であるように吹聴したり、目の前でその前提で脅しつけてきた。
センター試験で態々、俺の前の食堂の席に座って、俺が性犯罪者であるように周りに十分聞こえるように恫喝した。
俺はそれをどうすることもできなかった。それどこか、周りの人間は何もなければそんなことは起きない＝俺が悪い。そんな風に目を向けるものであり、
此奴を疑うという風に感じられなかった。だからどうしようもなくて自分がやったかのようにふるまってしまった。
画鋲の時もそうだった。渡辺が俺の靴に画鋲を入れてそれを下駄箱の上に取り除いた時もなぜか俺が誰かの靴に入れた香のように前提を作り上げて
毅然とした対応ができないでいると、大橋にその前提を言って清水とともに大橋が俺の靴に画鋲を再度入れて誰かの下駄箱に入れるように俺を仕向けさせた。
他には店で遭遇したときに万引きをしたとそういって大橋に俺に万引きをさせようと仕向けさせた。
そういう前提があり清水は見たのに、俺を加害者側として取り扱った。



・高橋公子
俺が危害を加えられているのに加害者として扱い俺を疑った。センター試験の時も俺が大橋についぞし返そうとしたときに岸を読んで邪魔した。
俺がヒステリーを起こした後も素知らぬ顔で大橋と一緒に横に並んで何事もなかったかのようにした。
俺ばかりを疑うだけで自分の身内には目を向けず加害者側の人間として一方的に疑ってきた。俺への被害は矮小化し、仲間への被害は過大にした。



・大橋由貴

まず、大橋がどんな人間だったかというと、自分の加害について見向きもせず、自分のやったことについては自分通りの解釈しかしなった。
他人に対して、それが自分に関係がなくとも入り込み、あたかも自分が被害にあったかのように受け取る人間だった。
だから攻撃材料は無限にあった。それが事実と反していても関係ないし、客観的にそうとしか取れないような言動をして、
周りの人間を巻き込んだ。自分から自分への被害をでっち上げることすらあった。
それでいて執拗に陰では付け回し、自分のやっていることでさえ、
何もやっていない相手に擦り付けるような人間だった。
自分が正しいに決まっているから、自分が正しいから、自分への注意は許さない。相手への被害は終わったことであり、
自分への被害や被害がなくても相手が悪いといった自己愛にまみれた人間だった。
それでいながら、自分の言ったことさえ守らない。自分の衝動性が正しく、相手に自分が危害を加え続けることは正しいとしておきながら、
それは陰で行うか、公の場では高圧的に自分が正しいという立場をとった。一度それが行えるものに対してはそれをやめるということをしなかった。
手段はありもしないことの吹聴や暴力や犯罪教唆や脅迫、恫喝が多かった。
高校で金閣寺に行ったときのホテル

大橋さんがつけて旅館で二人になったときに脅迫を受けて女子トイレに入れさせられて盗撮しようとしていたとでっちあげられた、それで気持ち悪くなってトイレで吐いていたのを清水さんが背中を擦ったのを覚えている。

大橋さんに卒業式の日に下田箱にこいと脅されて、洗いざらい吐かせてやると言われた。それで携帯の録音機能で…
絶対にお前のことなど許さないとそのようなことを言わせた気がする
もちろん彼女に対してなにかしたことなんかない
そういうようなことは続いた。
誰かオレを知らない人が俺を知ってる人に聞いたときそれがつきまとう結果にしかならなかった。だから気兼ねなく周りの人間も暴力を振るえられた
親に金銭的なことで責められているのにバイトもできなかった


大橋は使い分けが上手かった。俺以外の外部の人間がいるときは優等生や被害者になりきり、二人きりになったときには脅迫や恫喝に講じた

大橋がセンター試験時にヒステリーを起こさせたとき、清水さんに「何か隠しているぞ？よく見ていろ」といったことで、それ自体でどちらかが加害者なのが明白でもあるにもかかわらず、
清水さんはそれを無視した。

・清水

・末広

・吉田櫻子

・高野の妹

・野沢彩花

・清野（女）

・坂東

・渡部志穂

・伊藤しおり

・日高誠

・笠原彰子・板倉

・坂東

・大舘

・中田さやか

・竹田

・加村先生

・新井（女）やその友人

・その他高校・中学の同級生

・大学の同級生・空手部

・出口加奈子
https://rikeinavi.com/int/contents/magazine/img/pdf15g/15girls_1213.pdf
１４歳の女の子をレイプして堕胎させたと嘘を吹聴した。



・米山


・



なぜ、自分の人生は軽かったかというと、他人の背中を見たときにそれは明白だった。彼らは選択の自由があり、その選択に自分の体重を預けることができる。
それが自分の責任だからだ。私の親は私から自分の人生の責任を取り上げた。
私は他人から次第に遊びに誘われなくなった。傍から他人が遊んで楽しそうにしているのを眺めることしかできなかった。
遊び道具さえあればと思う。ただそれを親は自分の人生の責任の方が重いといって取り上げた。
自分には他人の人生で我慢しなければならない一部分というのが、自分にとってはすべてだったんだ。
行った先で、今これだけどうしようもなくても、誰かがきっと助けてくれる。そんな風に思い続けていた。
でもそれは、親でもなければ神様でもなかった。自分でも他人でもなかった。
クラスで係を決めたときにお笑い係になったこともある。自分は他人から加害を加えられることでしかコミュニケーションを取ることができなかった。
いじられているときはまだ。それはマシだった。だからそれでもそれが嫌だった。いじられる奴、俺はそういう風に思われたくなかったんだ。
だから、。。。。。。。

親には話さないし、話しても理解しない。理解したとしても忘れて都合を押し付けるだけの存在だ。

だから、今日、自分の全体重を首筋の縄、それは何でもいい。それに私のこれだけ膨れ上がった体重を預け、永遠に眠りにつこうと思う。


自分の人生を一言で表すなら、現実に起こる不整合の連続だった。誰かが悪いということではなく、自分が意図したわけでもなく、病的であったにせよ（病的であったならなおのこと）
そうなってしまった。

母は精神障碍者の親であることを拒んだ。ケアをして改善させるようにはしなかった。精神病の人の著書などを渡しても、都合よく文脈を読み替えた。
それなのに朝の芸能人の発信番組で取り上げたときはそれを信じ、まるで他人事のように話に出した。それまでは子供の言うことは全て嘘だった。
自分では何の責任も負いたくない。家族相手に全てを負担させるのが常だった。

正義だと、それに殉ずるのではなく、それで人を支配し、自分の心まで自分が思うように支配する。もう怖い。

正しいと思っても危害を加えた事実があるなら、ダメだわ。代わりにこれをやればいいなんて代替なんか聞かないと思う。

神様は何かの代替は許さない。それをやったからどうということではない。

精神疾患になったら、それまでの主観的に他人を許さなければいけない頻度とその程度が増える。

田舎で暮らしてると都会がいかにありえないかがわかる。

バカで貧乏でブスな人間と頭が良くて金持ちでイケメンな人間

に加えて、さらに前者には人間関係が希薄で弱者になりやすい。後者は親の金を使えてコミュニティに参加できる。

田舎でありえないのは子供が打ち上げをしたり、友達同士でカラオケ行ったり、焼肉行ったり、子供が親の手伝いをしないのに部屋や趣味が充実していることだ。そうでない人間に対して理解がなく、なにせ健康である、精神疾患とは無縁で、理解もなく人を傷つける。身内に対して肩を持ちやすいので、そうでない人間に対して身内が何してても肩を無条件で持つし、尚且つ当事者同士の問題がそうでなくなり、増悪されるか、お互いに黙っているので問題が発覚するか、しても肩を持つ。

高嶋、おまえは俺だけを悪者にして裁こうとするが、俺が何を言っても田海を疑わないお前はこの状況をそもそも表してるよ。
笑った顔で大学生の時まで追いかけたり、殴ってきたり、俺が急いで自転車で帰っているときにアイツが自転車を前に笑いながら出したせいで盛大にずっこけたんだ。
お前にはやらないだろうし、知っててもそれを矮小化するんだから仕方ないよな。
だから俺が川端紗記に集団リンチしているときもアイツはそれに乗っかって俺に対する攻撃材料をアイツに言ったりしたんだよ。
これが俺がアイツから何されても仕方ないと思うのは当然のことだろ。

YesかNoか？聞かれたら。お前らはもうGame Overだ。2014年の最後の日以前からな。

始点と終着点が最初から親として有るまじきものだった。言語教育を全く行わず、会話を求めてる子供に対して辞書を読めと、辞書すら手持ちでは用意させてない。それで会話する気など最初からないし、
自分の言いたい要求だけしか通さない親だった。全て自分が思い描いた美しい世界で、そこに対して最初から交わらないか交わろうとしても突き放す親だった。
子供がどうあるべきかわからないし、親としてどうあるべきなのかもわからない。

最初から人に対して、平等の観念や道徳的な観念なんか持ってないと思った方がいい。自分が想定している人間に対してそれはいいが、それ以外の人間に対しては
自分がそうしているくせにそれを認めない人間がいる。最初から最後までなんとも思わない。

自己愛性人格障害の人間に感想文を書かせると、文章に抑揚がないことがわかる。他人から共感性の得られる文章が書けないことが自分でもわかっているから。
文章を書いたときに感情やそこでしか得られない体験というのが、機能や構造としてしか認識されてない。花鳥風月であるのに、ものでしかない。
普遍性のないものが普遍的に見える。感情の積み重ねがない。そんな印象だ。普通の人間は右肩上がりなのに対し、そして並行的なそこから伸びしろのない損得勘定しかない。

機会に恵まれた人間はそれがない他人のことなど顧みることがない。

支配者がいて、早津の善悪の問題があるんだろう。そいつが私に対して何かさせようというのであれば、私は間違い無く刺す。私に対する被害を矮小化する張本人に過ぎないからだ。

誰にとってもそれがチャンスであれば、自分がそれを逃すことによって、誰かが幸せになるはずだ。何も不幸を知らずに。

その人間の言動にその人間や聞いた人間にとっての都合があるのならば、それは必要な情報が盛り込まれていないし、都合以上の情報を持たない。

フェミニストについて最近は理解できる。なぜこうなっているかというと、やはり力関係で男性と一体一になってしまったときに弱い方が相手の思い通りになってしまうことに
由来するのだと思う。だから、本来それらのそれまでのやり取りについて理解されるべき点であるのにもかかわらず、恋人関係でそうなってしまっているから、弱い側の立場を尊重されるということがなく
立場としてどうとでも取れるし、前後関係のない、脈絡のない一方的な文言だけが最終的に他方にまで飛び交ってしまっているから、そうなっているんだと思う。
結局、男性も女性も身内の自己愛性の人間の嘘や他者への態度の豹変に何の疑問も抱かないだけだ。
当たり前だが結婚できないよな。だって他人のことを理解せずに自己愛性の人間にばかり寄り添ってんだから。
だから嫌いだ。男と女も。

今まで私刑についてさんざん否定してきた。だが法律ではさばけないものというのがあり、結局、流動性の高い犯罪は証拠も流れてしまい、裁けない。連続する。それが集団で行われることすらある。
被害者の認識さえも流れてしまうことがあるのだ。周りは気づかず、時には被害者を加害者として認識する。裁かれるとしても全てが終わった最後だし、それまで疑おうともしない。

俺に対しては何をしてもいい、そう思っているくせに俺に対しては自分の存在を好意的で肯定的であるかのように求める。死んでしまえ。

自分の罪を知らない人間が、そこに近づこうとするんだろうとこの人生で悟ってしまった。それは他人の方からもやってくるし、他人の罪を自分の身によって負担させられることもある。
生であり、死であり、場所であれ、人であれ、出来事であれ、言葉であれ、なんであれ、人の手すら借りてそれらはやってくる。

誰に対しても絶対悪なのと、誰に対しても正義を働かせるのはなんか違いがあるのか？

現実が、そうあるべきとは真逆の恰好であるにもかかわらず、正しい正しくないの問題は、自分について残酷でしかなかった。そういう風に流れ続ける。一度もそれを正せるように
という判断材料さえ与えない。

あの人のことはもう、個性の認められない人間に思っている。いわばあの社会の手先なんだ。
今、一番恐れていることは、あの社会で下された決定が、社会全体の絶対的な決定につながってしまうことなんだ。

フィクションと違って現実で何より恐ろしいのは、原点が存在しないということだ。あったとしても誰もそれを認識しない。物語には始めがあってフィクションの中の悪役は全てを最後には説明する。
まるで理知的であるかのように。現実でそのような人間は存在しない。悪い意味で社会的だ。最後まで社会に組み込まれようとする。原点を知っている人間はそれを説明しないし、やった本人もそれが原因だとは思わない。それが当たり前だと思ってる。
思ってなくても説明しない。何が人間らしいのだろう。全てが人間らしく思える。社会性？罪悪感？達成感？
性善説や性悪説があるにしろ、人間とは生来に不完全なものであることには変わりがない。

お前は自分が恵まれているくせに、それより恵まれている人間を蹴落とす最低で卑怯な人間だ。自分では美徳のように「自分は善人でない」と厚顔無恥で話す。

悪など必要としていないと口では言いながら、悪を必要としている。それも無意識にその根本と手をつないでいる。

状況に甘んじてそれに寄っかかるのではなく、打てるうちに打てる手は打っておかなければならない。他人のためじゃない自分のためだけに。他人に期待するのは何にしてもダメだ。

ふと何かの拍子に、石を蹴ってしまい。それがおはじきのように繰り返されて大きい石になって、それによって下にいた大勢の人間が巻き添えになる。
それをどう思う。

合法的に女子高生とセックスする方法を一流のすし職人が伝授します。まず手順があって、まずコールドスリープ装置を外発します。
俺のためにそれに入ってくれる17歳のJKを冷凍します。18歳になりました。法定年齢の18歳だけど17歳のJKが実質的に抱けるというわけよ。
俺たち一流のすし職人の間では、冷凍したマグロのことなんか新鮮だとは言わねんだよ。

君の言うとおりだったよ。この世界では何一つ成り立たない。責任を取らない人間は最後まで責任を取らない。自分が今まで生きてきて社会的にどういう位置づけなのかわかるだろう。
蛙の子は蛙。そうやって言うことも許されない。そうやって何かの欠点を贖うために天から何かを与えられても、それに胡坐をかくだけの連中しかいない。
それで誰かひとりに対して自分の責任を勝手に集中させて、それが贖えないとなれば、そのことに勝手に腹を立てる。そこに何の理知などない。
それだけだよ。俺の周りはそうだ。

自分のこれからする行動について、これまでを見ずに言語化もしない人間は、動物的な体感によって身をゆだねようとする。それは要するに、痛みや苦しみを味わうまででやり続けるということだ。
結局、途中で疑問に感じても、自分がそれを実感することさえも自分の行動によって崩すのだから、どうしようもないし、正当性もない。言語化したとしてもごく主観的な決めつけを行う。
「アイツはマゾに決まってるから」馬鹿か？お前なんか死んだ方がいいんだよ。小学校から果てまで大学までやるなんて、死ねよ。

自分たちでさえ従っていないルールを、余所のものに強要するんだよ。しかも明文化されてない。せまい世界のものをだよ

支配者がいて、善と悪の問題があるのだ

だから他人に対して不当な立場を押し付けるのはもとより、現実の論理的に整合性の取れない問題に対してでさえ

自分の論理で解決しようとする

結局、自分の立場ではそれが成り立たないということにさえ分からない

そしてより密接な身内との社会はそれらを隠蔽する

神のような立場で善悪というのはあるのだろうか?完全な善悪判断は?どこかの時点で存在するのか?

人間が神を定義した。人間を定義するために。

社会を裁くにはどうしたらいい？

まず人間さえ正当に裁けない

結局裁くのは人間だからだ。

人間を裁くには人間を神にするしかない。



まず電磁波っていうのは電気であって電子だ。だから素粒子だ。

そして素粒子レベルで人体を計測するなんて不可能だ。

素粒子は観測できないものの代名詞だ。そしてコロナワクチンの検証さえ取れない。そして検証自体、長きに渡ってすること自体、法律で定められてはいない。

必ず期限がある。

だから電磁波の検証なんかできない。

それにする必要がない。

そもそも人体の全てについて検証なんかされてないし、何なら都会の排気ガスのほうが問題だとすれば何も言えないし、そういう相対的な問題が積み重なっていて現状の問題が起きてるとすら都合が良く解釈しない。そもそも解釈しないのだ。

敵国の戦争の兵器に使われるとなれば話は別だが、自分の利益になると信じれば、それは簡単に受け入れる。

そもそも素粒子が観測できないのは

そもそも素粒子も我々を見ているからだ。



素粒子レベルだ全て。善と悪さえも素粒子に存在している。

それが本物の善悪だ。



後付の理由なんかなんのためにもならない。



言われたことや聞いたことに対して相手の本心など最初から眼中にない

自分の本心だけの人間がいるんだよ。



人間とは本来乞食であって、イスラム教のような厳しい戒律のもとでも異教徒に対して、それらが行われる。

二人との間に盟約が結ばれ愛し合うというのに自ら口から吐いてそれが周りのものにも求めるように呼応させ自分の愛した女でさえその汚い男どもに強姦させる。

今思えば、盟約を結ばせたのは男の方だ。言葉だけでなく演技という行動も含まれていた。そいつは成人君主のように周りから扱われていて、誰も疑わなかった。女はそれで売春婦扱いされさらなる凌辱が待ち受けていた。男は女との愛を選ばずに社会を選び、自分の吐いた嘘も忘れ、女を嘘つき呼ばわりした。その男に相手が批難すればそれに腹を立て酷いことをするのは目に見えたことなのにそれまでの行動や言動でも相手にそれを許さなかった。

そのくせ嘘を付きついた自覚すらない。勝手なことばかりする

君たちはそうやって陳腐な論理で、この社会においてなぜそれが生まれるのか其のたび其のたびに考える。
だから本質的に理解しようとは思わない。自分が納得すればそれでいい。
我々の社会からそれが生まれているということには疑問も感じない。
善悪で全てそれに由来すると思っている。

自分が生きていくうえで、それも正しい一つであるということにいい加減気づいた方がいいんだろう。相手を人として持ちうるべき心を持つなんて思わない方がいい。なんでもすべて過去の延長上だ。
人を見るときに人を見ないのは正しいことだ。人として相手を思わない。相手の言葉ほど信じられないものはない。
人間を見るときはそれまでの判断材料であって、相手の人としての心ではない。そんなものに期待するな。自分がそれに対してどう思うかではなくて、どう動くかなんだ。だから期待した感情なんてものは現実とは常に裏腹でしかない。
銀行員は正しい。別に相手が恨んできてもそれは逆恨みだろう。それも正しいのだろう。
これが自己に向ける正しさであり厳しさであるということに気づかなくては。

で、動くときは多種多様な人間で動かなければいけないんだ。自分だけが動いても仕方ない。ある意味国籍や宗教や人種を問わず動くようにしないとユダヤ人のように。
本当の正義や正しい社会性は発生しない。

正しさは反復横跳びするんだ。

マイナス一にマイナス一をかけても、プラス一になるとは限らない。相手にとって望ましくない人間が施そうなんて思うな。望ましくない人間の望ましくない行動をするだけだ。
ただでさえ助けられないというのに。

それが語られないのは、それがないからではなく。語りようがないからだ。誰に対しても。

一言に因果応報といっても、その場面だけを切り取っているだけに過ぎない。ホントはもっと長い流れがあって。
当事者でしか知りえない情報などがそこにはある。良い悪いという判断が下されるのは一番最後の方だ。
ホントはそれまでの流れについて正当な評価がされなければならない。


本当に正当なものは正当な社会でしか評価されない。

はっきり言って身内を叩く人間は絶対にいないということだ。

この世に善悪とはあるのだろうか。結局強いものが支配する。多分それ自体は悪いことじゃないんだろう。


お前の最初の息子を知っているか？
この世の全てが因果応報だとしたらどうする。お前の魂の最初の息子はお前の言うとおりに全てお行い遂行し。この世の醜さを目の当たりにし、自分自身さえの醜さを目の当たりにした。
完全な平等を求めた。そして彼は完全に死んだ。
そして私たち神は彼の遺言に従い、神と同等な平等な魂に彼の記憶を移し替えた。
だから彼はお前の息子の魂と同じ記憶をもつ別の魂だ。そしてそれは平等だ。
お前は彼に対して分かり合えるなどと言っていたが、この世の不均衡をもたらすお前に、それができることなんかあるだろうか？
お前は結局、自分が愛されたいだけだ。自分が楽になりたいだけだ。
そして、一番最初にお前を心から求めてやまなかった魂などという文言に特別惹かれ愛されようとした。お前はただの自己愛の権化だ。
私たち神は誰に対しても完全な死を求めていないが、お前はそれを誰かに対して求めている。


別に嘘でもいえるだけ語ればいい。あとで嘘だと言ってくれるならね。でも嘘の中にでもこれは本当のことなんだと思うものがあるなら、それは私もその通りだと思う。
自分がその責任を取らないからって、それを悪用する人もいるだろうけど、そうやって何でもやってしまう人は結びつけようとしても根本的に自分とそれと結びつくことなんかないの。
何にでも転がってしまうから。
もちろん相手の立場も認めるし、自分の立場も認められればいいんじゃない？それ以上を相手に求めるのはどうなのかな？相手は自分そのものだよ。何にしてもね。
だから自分のことを知るっていうのは相手のことを知ることでもあるんだよ。

精神病だとして、先天的であれ、後天的であれ、そうなってしまったもうどうしようもない人間がいたとしてさ。
素養でも環境でも私たちはその人より恵まれていたでしょう？なのにその恵まれていた部分を当たり前のように認識してその人を攻撃するのはおかしいよ。
だからそうじゃないかもと思うのは当然のことでしょ。なのにあなたはそれをせずにそうする。

幸せって何だろう。快楽を得続けることか？それはどういうことだ？快楽なんて続いたところで、それに慣れるだけで機械的で、なんの楽しみもない。
他人から愛されることが快楽だろうか？好きにやって無条件に好かれる、そうやって秩序もなく方向性もない快楽が果たしてそうだろうか？それはただの暴力だ。
それに同調するなんて、それこそ怪しい。
気持ちいいとはなんだ？一緒にいたいとは何だろう？自分がその当事者になれるのか？長く続くのか？
違和感しかない？違和感はその正体をただ突きつけるだけだ。確信したときにそれは遅いのだろう。
他人より上位の存在であること？その愉悦が幸せなのか？自分さえそう思っていればいいのか？
他人が不幸で、自分が幸せなのは本当に幸せといえるのか？
不幸を共有することが幸せであるかもしれない。人間とはどうしようもない生き物に違いない。

人とのかかわりあいについて善悪で切り捨てるのは難しい。本当の正義感がいるなら、誰からも嫌われることだろう。

正義感が強い人間というのは結果的に支配欲の塊でしかないのと同義だろう。本人にその気がなくてもだ。
事実について話しただけで反省していないといい始める。周りからその人間が攻撃されるのはそいつが悪いからだ。その前提を崩されるのも嫌がる。
その癖、自分が追求され悪者になったときは相手がそう出ることを許さず、自分の認識をさも語る。自分は関係ないから責任がない。
アイツがおかしいから悪いんだ。そういって自分が追求されることもない。何の責任も取らない。

正義感の強い人間ほど自分の行動について客観的な言語化されるのは嫌がる。だから自分のしていることについて話を出したがらないし、都合よく言いくるめる。
そもそも、その認識すらない。

解離性障害で一たび仮想的な人格を作り上げ、自分が被害にあったわけじゃないと逃げる。それが前提になってしまって、被害にあっても警察に相談できないし、それを正しく人に説明することもできない。
それで人格の基づいた問題行動や言動をしてしまい、周りからそれを魔に受け止められて危害を加えられるうちに、それがもっともそれらしく増悪させて、問題行動は拡大される。

全く身に覚えない罪について追求されたとき、相手はただ因縁をつけるだけで吹聴されたどれのことだとも言わなかったし、自分自身が解離性障害で事実でないことを言ってしまっていたので、
何についてかさっぱりわからなかったし、毅然とした対応も取れなかった。

思い付きや思い込みに近いもので話に加わる。それを行う人間に責任が追求されるとなれば、話が変わるのにもかかわらず、その一点だけで話をさらに増悪させ、それを正しく負担するなんて不可能だ。
追求するべき相手に対して追求するのではなく、そう思われてる人間に対して追求が行ってしまう。
それを事実としてないにもかかわらず話として共有され、それが前提にされてしまう。誰がそんな話をしたのかさえ追求できない。
当事者同士として話をしても、互いに増悪させられるだけで、正しい認識は一切共有されない。

論理的な考えをするときに知識よりも自分の周りの情報と紐づけて考えるタイプの人間が多いんだろうと思う。自分のベースでしか考えられない。
相手の環境を考慮しない。因果関係を認めない。
個々の論理的飛躍や例外や一方的な観点で、その際、まったくその相手となる人物のもつ主観や問題などは一切考慮せずに話が進む。
だから、自分の持っている方向性でしか最初から語れないし考える能力や知識といった判断材料なども乏しい。
           
マウスオーバーした際に張り付けた画像の上でマウスポインターを変える　OK

画像、取得する

画面縮小時、画像を等倍で縮小するかどうか？　

一つの画像に対して、大きさの異なるメディアでの画像貼り付け位置が選択できる。
貼り付ける画像に対して、大本の画像の状態を取得すること
その状態において表示非表示ができること。

画像を指し示すフォームの値が変わるたびに、その要素を辿って、重ね掛けする。

マウスオーバーで画像が変わる

canvas要素にクラスを追加できるようにする。


*/

/*
正義というのは、ある意味では一番負担させてはいけない人間に全力で負担させる行為だ。



神様はね。俺たちの命のことなんか食卓に並んでる動物と同様、どうでもいいんだよ。だから自分の生きるためだけに死んでくれた他者に感謝しなければならないんだ。だから頂きますは必要なんだ。そのことをいつでも念頭に入れて自分たちの社会でそういうことが起こらないようにするためにも。



最近思ったこと、何かことが起こったときにその都度その都度で考えてる人は少ないということ。大抵の人間は最初から何をするか、考えというのは決まり切っていて、相手が自分の考えや尊厳を配慮することなんて稀であるというとに気づいた。相手がそれを無理強いしてきても、相手自身はそのことについて考えもせずに踏みにじる人間は多い。

それは生まれや育ちなんか関係なく発生するというのがわかった。相手が自分のことを考えることなんてないんだ。自分も相手のことなんか考えてないというのは認めなければならないし、何言っても相手にとっては無駄だということはそういうことだ。

そして確率論的にありえなくても、その前提は正しいとは限らない。1パーセントそれかありえて、確率的に有り得なくても、

その1パーセントが現実であればそれは100パーセントなんだ。

そういうのは結果論であって今でわかるとは限らない。

今起こっていることはもっと前から起きていて、その時に発生してるわけじゃない。



兄が自分の思い込みや主張、高圧的に取るたびに自分はそれを遠慮したり、避けたりしなければならなかった。

態度や言動を改めることなんかなかった。

自分が悪者にされ続けた。



心身の状況が悪くてもそれを環境的にそれらをどうにか工夫して避けることが出来なかった。



自閉症の人間が自分が想定できない状態になることを恐れて、暴れ出してしまうことや境界知能の子供が性的虐待を受けてからストレス発散のために自慰をして解消するのと同様に、俺は恐れて自分はそれが最悪の事態になるとわかりながらも、吹聴された罪を自分の口からやったと自白した。信頼関係を結べない、周りから危害を加えられた自分にはそれしか出来なかった。



大学生になってからも、母親から小学生の頃の服を着るように強要されていた。



よく他人に吹聴する人間が、暴言を吐きかけながらおびえて隠れている俺の顔写真を撮って、まったく関係ない第三者に暴力をふらせた。



縄を梁にかけて定例の結びをしたときに、ほっと安堵してしまった。自分自身で終わらせることが出来るのだと。



相手への被害がないようにすることが念頭になって、毅然とした対応が取れない人というのは、傍から見てやましいと思われるんだろう。毅然とした対応を取れない人間に対して取れるはずだ、それをしないということはやましい、何か悪いことがあるからだ。そうやって簡単に攻撃的な感情を募り、加担するし、字面と声のでかい人間に対しても、被害にあっている人間対しても、等身大のものを見ようとする人はいない。



母親からダンベルを持つことを頑なに禁じられた。自分が細身の男性が好きだかららしい。

そのせいで嘗められて俺は周りから危害を加え続けられたし、周りは俺がダンベルを持ってわいけない家庭だと考えもしないし、却って親に甘やかされているのだと思われた。



親が転勤族で、相手の言い分に対して反抗できなかった。その場で何も言ってもいないにもかかわらず、現実で起こりうることに対して被害を受けたと勘違いを執拗に行い、それを正そうとしない、そして何かにかこつけて因縁をつけてくる相手のなすがままで、相手から一方的に危害を加えてくる。「自分が納得すれば、それでいいんだ」そういうふうに思い込むことしか出来なかった。



思えば、周りから危害を加えられるばかりで、信頼関係のある友人は一人もいなかった。相手の要求を飲ませられることが常だった。



ロシア兵がウクライナ兵の家屋に入ったとき、自分たちには手に入らなかった家電製品や生活必需品を見て、ウクライナ人を許せなくなったそうだ。自分たちでは必要だったのに手に入らなかったものを見てそう思ったのだろうか。その人間に対して必要なものを与えないっていうのは、周囲に対しての憎悪たぎらせる有効な手段なんだろう。





親は周囲の人間の当然の感覚を教えなかった。というよりも世間知らずで庇護されてきた側の人間だった。それがどこへ行っても付き纏ったし、それを是正する環境からは物理的に切り離されてきた。



自分が一言言って周りの人間のことは通らせない。注意を払わず払いのける。無視する。



自分が周りの人間と一緒になって散々貶めてきた人間に対して、何を期待してたんだ?

見当違いもいいところだ。



大半の人の人生の目的ってさ、ある物事に対して直面しないことなんじゃないかと思う。人生ってさ直面してそれをどうにかすることが大半じゃない。それがいくつもあって、直面しない不幸のほうが少ないのだから、ある問題に直面しないようにするために別の問題や変わらぬ日常でいるために直面するんじゃないかな。



人を殺した人っていうのは、それに及ぶ感情を相手や周りから顕著にそれを突きつけられる存在である。大抵はそれを無視するように生きていくが、向けられる感情から起こる障害はそれが直接的で現実的であればあるほど、悪い意味でそれを無視する事は周囲の普通の感性を持っている者であれば、それを知った際、その人が悪い意味で浮世離れした存在に思われるのだろう。自分は生きているということが一種のサディズムである。そして理由はなんであれ、突きつけた人間は突きつけられる事もあるだろう。

それから逃れる事はある意味では無いだろう。それが因果応報なのか、自業自得であるかは定かでは無い。

人を殺して罰せられるかのように死んだ彼は動物であるより人間らしいと思える。罰せられずに生きるというのは動物的だ。自分の解釈で生きて他人にはそれを認めない。第三者から見たらどれだけ悍ましいのだろう。自分の主観でしかあり得ない話なのだろうが。



子供を生き物であるという認識ではなかったと言ってもいい。事実関係を自分の自己愛によって改変させるので、婉曲した認識に対して、対処しなければならない。そのたびに自分が駄々をこねる子供の様に周囲から悪者にされなければならなかったし、母親は子供を悪者にするのに躊躇がなかった。

実費で買ったものやそもそも買ってもいないものに対して「買ってやった」と言って周囲にあるはずもない正当性を錯覚させた。母親と父親の興味や関心だけに引っ張られる陳腐な世界でしかなかったと思う。



反抗すれば、僅かながらの希望さえ、手放さなければいけない。

そしてその権限は自分には一切認められなかった。



「みんなそう言っているからそうなんだ」は机の上や学校の話だろう?そんなことは現実世界で有りえない。



自分がもし、命を投げ売っても、その悪を肯定することにしか繋がらない。



真面目にやっている人間には申し訳ないが、

女性は組織にむいてない説というのがあってな。例えば、女性だけの街があったとして、ゴミの処理を誰かがしないといけないときに、例えば無償で誰かが引き受けたとする。そうすると勝手に相手はその人をやりたがる人間だと思いこんで「これもやっておいてね」と他のこともやらせようとする。有償でやっても、「いいよね、ゴミを処理するだけでお金がもらえるんだから」と自分自身の行動について社会的な正当性について所在がない人間が多い。それでいて自分の勝手な想定をする人間というのが許せないのが女性だし、尚且、自分も勝手な想定をするのがやめられないから、成り立たない。上下関係のない体育会系みたいなもんだし。



加害者と手を繋いだやつが偉そうに言うな。



大抵、神様っていうときは、自分の主観と客観があって、相手の主観というものが自分が許さない時に、そういうんだよ。

それで客観的な事実を知らない限り人間は客観的にはならない



そうでないという反証が出ない限り、周りの加害は続いて、追い詰められて、それで取り返しのつかないことをしたときに、それは負の脚光を浴びて、周りもやっぱり悪い奴だというふうに定義づける。自分の加害に対して因果関係を認めない。



一度、孤立して病的になる。そうすると誰も近づかないし、敵対者の言うことを真に受ける。



母親は頭がおかしい。そもそも実費で自分で買ったものに関しても、「私が買ってあげた」などとぬかす。



前後のやり取りを抜いて、他人を論理の飛躍のあるように晒し上げる人間は多い。



離人症は現実で起きてしまっていることに対して、無抵抗になってしまう。統合失調症が自分の間違った認識を振りまいて現実から乖離してしまうのに対し、離人症は現実で起こっていることに対して、事実と異なる他人に認識そのものを現実であるかのように錯覚し、自分の認識というのがない。だから何の抵抗もなく他人の誤った認識を増悪させ、現実と事実を解離させる。



社会を巻き込んで、会話の成立ができないようにする人間も多い。



自己肯定感には語弊があって、社会的にこれは正しいからこれをやろうというのも自己肯定感である。それがないと極端な話、誰かに「あいつを殺してこい」と言われたらそれをする。



自分が例えば他人を排除して正義を成し得るなどといった、

自分の大凡の考えで行動したとしても、

結局、その周りの集団に従属しているだけの人間は多い。

集団に対して問うものがないので言葉とは裏腹でしかない。



子供の自主性は絶対に許さない親だった。兄に対しては無関心だった分、自由があっただろうと思う。

そうでない俺が犠牲になったとも思う。



自分の兄弟は俺の精神病を許さなかったし、母親もそのことで責められたとき、遠回りに兄弟の前で自分をその場限りで甘えさせてるように見せた。「何もそんなこと言わなくてもいいじゃない」と。母は相手に対し精神病になった経緯や本質的な理解を得ようともしなかったし、自分の考えを崩されるのは嫌だったのでそのことを理解することすら拒んだ。自分の思い通りであることを崩さなかった。



自分の親は客観的に物事を正す能力が極端に欠如した人間だった。



日常的に解離する時点というのがあり、まず相手との会話が無い。そもそもその相手がわからない。知らない。元々、それ以外の素養で排除されるような人間であり、寛解しない。それがあったとしても出てくる不適切な症状を抑えるまでである。

普通の人間ならば、その場において適切な物の範疇に全て抑えるのにそれが出来ない。



集団リンチを受けた時、それがキッカケになった自身の不合理な病理について周りに示すために自分はどうしても障がい者手帳が必要だった。でも親はそれを許さなかった。自分の病理や環境について普通じゃ無いからと説明しても、「普通って何よ。」と言って突っぱねた。その癖、自分が障がい者として手帳を得るなり、治療を受けようとしたら、それについては「普通じゃ無いから」と言って突っぱねた。

ダンベルを得ようとした時もそうだった。小学校時代から虐められている事は分かっているくせに、「私は細身の男性が好きだから」とか、「そのまま毅然とした対応を取りなさい」と言って突き放した。誰も助けてくれないし、先生に言ってもまた危害を加えられるのは目に見えてた。

数年後に母を殴った。そしたら母は家事を手伝っている姉に向かって「家族の雰囲気が暗くなるのが分からないの？」と言って姉に当たり散らした。

自分の兄弟は俺の精神病を許さなかったし、母親もそのことで責められたとき、遠回りに兄弟の前で自分をその場限りで甘えさせてるように見せた。「何もそんなこと言わなくてもいいじゃない」と。母は相手に対し精神病になった経緯や本質的な理解を得ようともしなかったし、自分の考えを崩されるのは嫌だったのでそのことを理解することすら拒んだ。自分の思い通りであることを崩さなかった。

自分の病理は強要や企図されることがトリガーだった。特に幸せな家族を演じなければならない時が多かった。その度に幼児性の強い人格が表出した。





マインドコントロールというのは、その人間の行き着く先だと思った方が理解が進む。



例えば、自分のいるその閉鎖的な社会に寄った考えで人に賛成したり批判したり、その場にいる人間のように自分が本質的な理解がいらない言葉を投げかける存在になったとする。自分の家庭など人には言えない部分が起因して、トラブルになり、その社会から排除されたとして人はどうすべきなのか？

やはり、別の社会に行くんだろう。しかし、それまで社会やそれが下す評価に対して周りの社会が同様に依存していた場合、そこから抜け出すすべなどないだろうと思う。



その事を前提にさせて周りからもその人間を叩かせる。相手に議論できないようにして、それを前提にしてしまうと洗脳されてしまう。



精神病にかかって、自分の不合理でしかない行動に関して周りは納得しないだろうという強迫観念もまた、自身の孤立を深めた。



集団でいると何がいいかと言ったら、自分について大凡、どういう汚い人間なのかを俯瞰しなくて済むということだと思う。



ニヤついた顔で自分がレイプされたとか言ってるくせに誰もそっちの方を見なかった。



今この状況において自分が死なせてもらえるのは嬉しいことだ。

状況の推移として、自分が犯罪者に仕立て上げられ、殺されて、その後も口のなくなった死人はそのまま何も言えず犯罪者のままにされる。そして自分が危害によって障害を負わさせられたり、加害者に障害を負わせたとしても、自分の罪は晴れず、相手が言葉を強めるだけだった。



親も含め、自分を心配してくれるような人間は一人もおらず、周りの自分に対する加害だけしか関わりを持たなかった。



強要されることがトリガーだった。特に幸せな家族を演じなければならない時が多かった。その度に幼児性の強い人格が表出した。



自分が精神疾患で社会生活の障害になっていて、それができない状態なのは分かりきっているのに、何時ぞや話した世間一般の恋愛観のついて話を持ち出して、女性と付き合っても良いけど、婚前交渉はするなと要求してきて、怒りを通り越して呆れた。コイツは今まで俺の何を見てきたのかと？



男性と女性と絶対的に違うと思ったのは、女性は冤罪を許すということだ。



追い込まれていくうちに、それまでの自分を外に対して求めるようになる。そこでまた他人に突き放されたり、追い込まれたりする。



疚しいところの有る人間ほど、人には批判的なものだ。本人にその自覚が無くても。



他人が自分とおんなじ立場ならこうする。なぜならそれが社会的に正しいからだと言う存在が身近には存在せず、そうあるべき関係のない第三者が自分と比べても相対的にも理由なく容易に暴力に加担した。それでわからなくなってしまった。



間違った認識のもとで育まれた人間が絶対的な価値観により善悪を下されたときに、その人間を取り巻く周りの人間の全員が間違った認識のまま加害したときにどう対処するべきなのか?その人間に対して、ある意味では自分より正しい判断を求めることに違和感はないのか?

だから持続的に可能な形で誰に対しても復讐ができる人間にならなければならないが、そういう人は最初からそこに落ち込むことがない。



女性は真実を嫌う。



どうであれ孤立した人間が一番最初に助けを求めた人間に、「自分でなんとかしろ」と突き放されるんだよな。

俺の親は周りの人間から暴力を受ける環境を俺に強要し続けた。

結局、人間がそうなったときどうするかって言ったら誰も知らない土地に移り住むってことしか解決出来ないんだよな。



「DVとレイプの冤罪で問題なのは、冤罪の被害者が実際の犯罪を犯した人間と殆ど同じ体験をするということなんだよな。」



俺の経験則だけど女性は社会性を失ったとき死ぬほどめんどくさい。で最近わかったのはこれは男性にも言えることだね。で、男性が今までやってきたことを女性が成り変わるっていうのも無理だね。そんな歴史が存在しないから。理想的な男性というのがどの社会でも求められてきたから、女性はホルモンバランス的にもそれは無理だ。言っている事が軒並み変わる女の言うことなんか聞いてたら、誰の目にも無責任にしか映らない。

後、男性社会は完全に物量の社会で人権を無視してるから、人権意識に目の行く女性にはそもそも向いてないし、物量が少ないので男性と比較した場合に個別の評価がない限り、どうしても見劣りするし、連携が取れない。女性はナンバーワンでなくオンリーワンなのだが、オンリーワンになりたい女性は少ない。

成るとしても悪い意味だ。



アイツが追ってくる。途中までで追いかけてるのがわかるから、踵を返して向かい合わせようとする。

すると、あいつも踵を返して今度はこちらが追う形になる。途中で引き返そうと思う前にアイツの仲間が都合よく居合わせて、俺がストーカーということになる。

俺が思うに、男性の偉人が多いのは女性による被害者がそれだけ多いに違いないということだ。



新宿二丁目は排除された人間の受け皿である。



君が肩を持つ人間は自分から事を起こす人間だ。

君は誰よりも崇高なんだろうが、周りの人間はそうじゃなかった。状況が違えばどれにだってつくような無責任で暴力的な連中だ。



人間というのは、それまで普遍的に抱いていたものの反例であり続けるではないか。



なればなるだろうという思想だったんだろう。

そうなっていなから精神疾患に掛かっているのにそれを正しく認識しようとは親は思わなかった。

親の考えにすべて則っても自分は孤立した。それは絶対だし、親はいつだって肝心なときに責任を放棄する人間だった。一度、問題が起きて許容しないとなれば、排除し続ける。俺は一体何回排除されれば気が済むんだ?親は子供が痛い目を見ようとどうでもいい人間だった。



ゲイバー「アダム＆アダム」の店長

元々は駄菓子屋だったが事故物件となり、それを借り入れる形で開業した。



中庭の住人たち　イスラム教　キリスト教　ユダヤ教の三つがある

後は社会から追い出されてヤクザになった人など



人間性の根拠



天国に対して、地獄の数というのは計り知れない。

人間の業というものが数えられるだけなものか。

その一つ一つを知らなければならないものも中にはいるに違いない。



なんでカダフィ大佐のような無血革命を実現して民主政治を行っていた聖人が、正義の名のもとに残虐な行為を続けるアメリカ兵士に殺されなければいけなかったのか？



こと精神病の事柄においては

親の言動に左右されて生きてきた。ある時また、それまで言ったことをすべて覆して、言うことを聞かなかったお前が悪いと、それまで認識合わせでやってきたことをなかったかのように振る舞う。完全に孤立してしまうことを恐れる自分には、自分を取り巻く全てから適切な距離を取ることができず、それらに支配された。



加害者にふと、普通に生きているだけでも鉢合わせてしまい。危害を加えられた。

自分が周りの人間から消費され続けるために生まれてきたんだと思った。家族や加害者から。「自分が」となるとそれ以外の他人について、個々を認めなくなった。

実際、川端は人生が充実していたし、私と違って身の回りにまるで不幸が無い人間だった。

交際相手による暴力。



何がイヤかってさ、自分の死に際の話でさ、死んでからもやってないことの罪を着せられて悪く言われ続けるんだろうなって。きっと世の中じゅうに溢れてるよ。



女の子の子宮が何故トロフィーの形をしているかわかるか?トロフィーだからだよ。だから一等賞を取らないとだめなんだ。



神様というか、何でもに人のやった通りにしかならないよ。罰を与えるというのは最初から罰が下っているんだよ。最初から、そこに混じれない人間はどうしようもないわけ。だって何も考える必要もなく安心してきた人間が平穏に暮らしてるじゃない。そこにいる全員が唾を付けられるような存在であっても。



まあ犯罪者の家庭が、変哲のないまともな家庭だったって話は、要するに犯罪レベルになるまで家庭が問題を認知しなかったってことだからな。普通は誰にでもあるべきものがそもそも無い。叱らない育て方っていうのも言い出した本人が本当に叱らないか、そもそも環境や子供の素養が良くて叱らずに済んだのか分からないからな。非暴力、不服従のガンジーだって、家族には暴力振るってたみたいだし。

なんか実態の伴わないワードに踊らされてる人間は多いみたいだね。



結局さ、被害の大きさなんて実在的にも感情的にも客観視なんかできないんだよ。

他人にそれを求めたとしても求め方っていうのがあるよ



軽口を叩く。演技なのか本気なのかは知らない。



諏訪部珠樹



「やっぱさ、....哲学や倫理とかさ、他人がどうとかではなくて、ある種、自分の方に向いてくるものを考えること自体がさ、...一種の鬱状態だよね。人間さ、一人で追い詰められたときに、周りの協力を得て打開するか。それとも、首に縄を括るかの二択しかないのにね。法執行機関による冤罪は問題だけど、そうでない私刑による冤罪の方が打開できないよね。」



やっぱ人間何が重要かって言ったら自分のやっていることに関して論理の飛躍が無いことでしょ



「自分では自分のことを男性だと思っているし、実際、体の性別は男性のはずだよ。女性に対して性欲もある。それなのに幼少から婦人科にかからないといけないような症状が体には出たり、女性特有の失語症になったり、解離性障害になったりする。しまいには多発性硬化症だ。別に女性と男性に違いがあるとは思わない。誰がやったって一緒だと思う。でも、自分は女性が無理やり男性になっただけなんじゃないかって気がしてくるんだ。だから、何もかもうまくいかなかったんじゃないかって。男性として男性のやり方を強要されてきたから、うまくいかなかったんじゃないかって思うんだ。女性だって男性に対してこうあるべきだとか、こうに違いないっていうのはあるだろう？でも、それが一切自分は果たせなかったんだ。」



多発性硬化症



親が教師で、ストレスで認知力が落ちてるのかもしれないね。



「俺も、自分の子供を両手に抱いて喜べる未来があったのなら、そうしてたさ……」



失語症を発症していたときに、大学の教員に例の噂を流された。



その人のことも実は恨んでる。俺が加害者であるという論理からズレずに周囲の人間とそれを積み重ねたから。



「人間は仕事量としてのその人の苦労は評価するけど、より心に近い、人格形成に直結する苦労に関しては見ようとはしないんだ。それに本人もそうとは語らないし、語れないんだ。」



ニヤついた顔で「(俺が何かを)隠しているぞ。」といったり、自白や悪事を俺に企図したりしてきた。





髙梁　志瑞　目黒　川内

---------------------------------

鍋の中のブルーベリーはふつふつと鍋がそれまでは鮮やかな自然の色が黒に近い一色になった。

「神様っていうのは銀行の金貸しと同じなんだよ。」



明らかに前後の文脈を無視して、書かれている内容を捻じ曲げて、登場人物の主張を捏造したりして、自分の主張を通そうとした。



「一連の不幸というものを経験してたら分かるよ。少なくとも見れば分かる人間には一発で分かるもんさ。」



人間の二面性ほど恐ろしいものはないよ。もう一方の面は正されることがあっても、もう片方の面は正されることが無いから。正されることのない部分が出てきてしまうよ。



清水はハッキリと自分が俺に対する悪事に加担したうえで、大橋の言っていることに対して俺に正しい主張を求めてきた。



誰一人として俺の現実に向き合ったことがないし、この人もきっと例外ではない。その現実に直面するのが怖くて言えなかった。



結局、自分の父と母も外の世界が温室だった人間だ。



「昔に女がコロンブスを教科書通りに偉人だと思ってるやつに女取られてな。」

「どうせ付き合ってもなかったんでしょ。」



アイツらはそれを後で気づいたとしても、どうせろくに反省しない。自分の人生を生きるだけだ。

だったら、そんなはずのなかった他人の人生を強制的に歩まされればいい。



うちは明らかにどの家庭よりも貧しかったが、そう思うことも禁じられていた。うちは貧しいと聞くと母は自己愛故にウチは貧しくない。あなたのためにやってる、あなたのせいでそうなっていると、遠回しに責めてくる。自分としては貧しいなら、それを認めてほしかった。だったら協力的になれた。それが全部自分のせいにされた。



子供がまるで外飼の犬のような感じだった。

家でいくら家に向かって吠えても、うるさいだけ。

たまに行く散歩で犬が好きな子供がいれば、その者の前ではそれはまるで愛情があるように振る舞う。

子供の存在よりも自分が思い描いた美しい世界のほうが勝ってたような人間だった。

だから本心が育まなかった。



人前ではニコニコしている分、弱い人間に対して強く出続けることに関して、何の敷居もない。加減もわからない。



子供に親の要求を呑ませるようにしか教育しなかった。性的暴行を受けた時でさえ子供の話をよく聞こうとせず、否定したり、話を逸らした。



「死にたくない。長生きしたいって言っている人間はさ、そもそも死の恐怖に対してそれを言っているだけで、あの世が天国ならそんな風には言わないっていうのがあると思うんだよな。」



「俺が昔の頃は条件付きの愛の親なんて言ったけど、俺の親はそもそも愛が得られる条件もなかった。できても貶すだけ。自分が愛されたいまま大人になった親だった。自分が周囲から愛されるためなら子供をないがしろにする親だった。」

人としての尊厳を奪われてたし、自分のいに沿わないものに対してそれを認めない人間だった。親じゃなくて他人だったら良かったのにと思う。親だったからすべての意に沿わなければいけなかった。それでもはけ口にしかされなかった。



幾ら物質的に恵まれていなかろうが、そういうものはあるものだ。

ソマリアで子供が病気にかかったら、母親が子供を抱えて何百キロにも歩いて救命のキャンプに赴く。そういうのがちゃんと普通の人間ならある。

俺の親だったらそんなことしない。自分が苦しいのが嫌だから。そうやって手当たり次第に自分ですら欺けそうな嘘をついて自分を守るだろう。親は親自身を守るために簡単な嘘しかつかなかった。



「結局さ、世の中には自分が求めているものなんて一つもなくて、その当人による他罰や自罰の連続でしかないんだよね。神様っていうのはこの世の中のことなんだよ。きっと。」



「他人からの正しいフィードバックにさらされなかった人間は、健常者であっても精神疾患の人間と変わらないと思う。精神疾患でも他人の正しいフィードバックにさらされていたら健常者だと俺は思う。」



「そうかもしれないけど……俺は神様っていうのはその裏返しだと思うんだよ。こういう世界になってしまってるけど、何処か一つに方向があって、それに向けて頑張ろうとするのが神様なんだと思う。」



自分の父や母は物事について全体像というものを知ろうとする人間ではなかった。「自分さえよければいい」と口で同じことを言う人間でも普段から、少なくとも普通の人間なら人として一つや二つはちゃんと何か知ろうとするきっかけというものがあって、それを取っ掛かりにするが、そういうものが全くない人たちだった。だから、それを言っていけない人間に対して、それを言うし、やっていけない人間に対しやりもする。



親のオナニーに付き合わされた子。



「心の拠り所っていうのはさ……結局相手と適切な距離離れてることなんだよ。寄り添うったってそうはいかないんだよ。」



自分の話を聞いてくれるという誰にとっても当たり前な存在というのが俺には信じられなかった。

何も言えない自分に対して、親を含めほぼすべての人間は「お前はアイツよりも苦労していない」と言って、その立場を強いてきた。行く先々で俺は悪者にされ続けた。



　自分の死に際の記憶の走馬灯の中で2014年の映画のアメリカンスナイパーを思い出す。



　痛みや苦しみは体の防衛本能だ。もう何も守れない。....私のおいては最初から何も守っていない本能だ。ただ私を苦しめるだけで。いつだって助からないのに意味もなく苦しい。....だからこそ死んで俺は助かったんだ。

自分の死に対して前向きな気持ちは少し前からあった。



確かに人よりだいぶ能力なかったし、その癖、他の人間より特殊な環境を代わるがわる求められたんだよな。



危険信号があって

俺は外で働くことができなかった。吹聴されたら、その内容を認めてしまうから



それでいて働くことができなかったし、親はその一点を責めて、それに関係する問題まで責めてきた



親は俺が甘ったれているとか嘘を付いているんだと思ってた



他人の認識がそうであることをそれが事実であるかのように言ってしまうか、それを照らし合わせるかのように行動してしまう







「今日の晩ご飯は何が良いの？」に対して「なんでも良いよ」ではなくて、「何が食べたいの？」と聞き返さないと駄目である。



苗字が特殊な人間は特殊だと俺は思う。



自分自身にもあったな。自分自身の全てを否定されて虐げられ、そこから這い上がろうとしても、またそこで否定されて虐げられて、そうやって自分が不幸になるのを恐れて何もできなくなった。直面して不幸になったとしても、本当に不幸になる必要なんてない。社会や自然の素晴らしいところに目を向けて自分にそれを取り入れて自分自身を正すしか方法なんかない。それで自分の不幸についてやはり重要なら社会的に正しい方法と範囲内でそれをしようとすれば、それでいい。

少なくとも自分の立場からは彼にそう言いたい。それに彼は解離性障害を抱えてるようではなかった。



周りの人間より相対的に自由を抱えている人間はその自由をできうる限り悪用する。と思う。



口では自分さえ良ければ良いとは言ってなくても、本人を取り巻くものがそれを語っている。



自分がこれから先、その対処を迫られる。肉体的に精神的に社会的にも追いつめられて、おかしくなってしまう社会は無くなってしまえばいい。その追及が今までの人生でなかった人間が簡単に人にそれらの全てを内包した暴力を行う。

オママゴトのような社会になればいいと思う。



彼は強い言葉で他人を定義したがるが、私はあまりその事に関心がない。人としてどうあるべきなのかはともかく、

結局自分が人生で起こりうる局面に立たされた時にそれに対処するのは自分で、だから自己について社会的に正しい注意が向いて、それに相応しい言葉選びが必要だから。だから強い言葉を選ぶ彼のことは心のどこかで倦厭している。



「育ちが悪い」と揶揄してたら、



自分がいくら不幸でも孤独を感じなければ、それ程、人間は不幸には感じないのかもしれない。



純水であるならば純水であることを望み続けなければならない。



国というのは、神を超越した存在だと俺は思う。神が国を超越したときに内紛が起きる。



風俗で働かなければいけない女性は庇護が無い。

そういう庇護のない人間に対して自分たちと



物事をハッキリ言わないのが許せない人というのは、そもそも自分の言いたいのことを人に言わせたい。もしくは態度を取らせたいのか。それだけである。



鬱病ですら、鬱は甘えだと言われるよりもはるか前の時代だったんだ。そんなものがあるとは誰も知らなかったんだろう。



相手が間違えていても追求できなくなっている。だから結局は暴力が雌雄を決するようになってる。こちらが追求しなくても、相手からわざわざ近づいてきて半ば決めつけに近い追求をしてくる。



そもそも根本的に自分の居所を気にするのが人間であって、他人の存在などについて相手に向けた配慮等は想像できない。その人にとって何が本当にためになるのかなんて他人には想像できない。だから自分が生きて存在するために必要な情報を第三者を介して周りに伝達することはできない。そういうのとは別に誰にとっても自分の居所を抱くために他人の都合の付かず、悪者に仕立て上げるような情報は容易に第三者を通じて流れる。



いくら周りを理解しても、周りは自分を理解せずに危害を加えられ続ける。



口でそう言うということは歯車が嚙み合えば、容易にそっちに注意が行くということだ。



父親は勘違いしたと思う。能力はあってもそれは環境を選ぶのはどこでも一緒で、

何かに対して見えにくい部分や評価されない能力があるのは駄目で他人から見えた全てについて平均以上を求められる。



その場にいる人間においては、完全に自分の存在や論理は飛躍した存在であるのに、他人を攻撃することについて理解を得ようとしたり、思い込みで嘘をついたりする。



男性や女性にもありゆることなのに勝手に女性でしかありえないとか男性でしかあり得ないというふうに分ける。



自分の中で勝手に前提を作り上げるような人間だった。



普通の子供は親が手を尽くして協力的で日常的にお互いの近況を報告するのが普通だと思う。自分が自分の子供が欲しいという年代になってからそう思い始めた。だから子供が悪いことをした時にはちゃんと悪いという感情が芽生えると思う。それとは自分は逆だった。自分にとって「悪い」というのは自分の感情から遠く離れていた。親は協力的でなかったし、会話があるのは親の要求に子供が沿わなかったときだ。近況などもお互いに伝え合うわけではなかった。自分ではどうしようもない問題に関しても、ただただ「悪い」。

子供の要求は突き放され、逆に責め立てられたことしかない。ただただ卑屈になった。それらの何が問題だったかというと、自分の中で正しいか論理性というものが芽生えなかったことだ。



ガンであっても認知しなければ、良い。要するに問題があっても認知しなければ問題にならないというふうに、そういう踏み込んだ事まで平気で言う。自分は認識しないし、認識しない。そういうことなのだ。



ふとした時に母に聞いたことがある。

無限に枝分かれしたものについて考えなければいけなくなる。無駄だからしない。

お前らは気遣わないという。程度の問題であるのにもかかわらず、自分が不利になる論理を出せば、自分が完璧な被害者であるという事例を持ち出す。してやったことに関して感謝の気持ちがないのはお互い様だし、何の責任も取らないくせに子を産んだのも、親の責任だろう。それまでの全てを放り出す。

女性に多い印象がある。そういう論理の人間は女性で知能の低い（勉強ができないとかそういう意味でない）は男性が殴らないと思っているのではなく、そもそも何も考えていないのだ。こちらを排除できればそれでいい。相手が何を言ったところで自分が納得できる答えなど、ハナから期待していない。期待していないことにすら気づかない。



因果関係や前にそういうことがあったから気をつけるなどという考えは一切ない。お前そういうことがあったくせに、よくそんな人のことを悪く言えるなと思うようなことさえある。

女性で知能が低いものに関して思うのは、それらが自分自身は生活習慣を繰り返し、それに則り生活している。だから意図していないで起きた部分のことについては責任はなく、自分は被害者だ。自分がそこでした意図した或いは意図せずともした行動を含め、自分に責任は一切ない。そういうところが朧げにでも見えてくる。



理由や正当性が何であれ、自分自身が原因になっていることを認めたがらない。



自分たちがやっていることや相手の状況によってそれが生じるという判断が全く理解できない、

言葉で言われている内容そのままでしか理解できない人間が大多数である。

だから女性そのものに対する興味はその時点でなくなった。全般的に女性が小学校までは同性よりも大人びてると感じたのは初潮で生活習慣が変わらざる負えず、それによって規則正しい生活を送らなければならないから、そう見えるだけであって内面的であれ客観的であれ物事に対する理解などが優れてるわけではない。

俺は男と女の全てを理解した。だからもうこの世は不要だ。



母親はわかられーことを知って居ながら執拗に自分の要求を押し付けてくる。



自分が話せる分だけを話す。自分が被害にあった分だけを。先に危害を加えたにもかかわらず。本来ニュートラルであったものを敵と味方へと二分させる。女性は多弁な分だけその傾向が強くなる。



ドラァグクイーン

パリコレのモデルのような出で立ちや整然とした立ち振舞、彼の手足の長さがより一層強調させることは、まさに歩く芸術であった。

匂いを嗅いだだけでラベンダーの紫の色鮮やかなグラデーションが連想されるようなパフュームブーケによって、彼の身長は頭一個分、私よりも背が低いにも関わらず、その存在感は際立たせるものがあった。



何であれ、普段から悪者に仕立て上げられてる人間は、自分から事を起こす、その程度を気にしなくなる。実際はそれによって怒りを抱くのだが、周りの人間が普段から悪人にしている事に気をしなくなることに関して怒りを感じているからだ。



ある意味では、自分自身の不幸であっても、直面しないようにするのが、正しいんだろうなと思う。

でも、経験論としては、そう言った直面しなかった人間ほど、個人では理解できないような不幸やそれを振り撒く人物諸々に直面したら、それを絶対に許さないのが私個人の経験論的には感じた。



そういう人間は一番最初の初歩的な問題に突っかかる。それが今まで説明できない状態にある人間に対して説明を求め、自分が理解できなければ、それを突き返す。そもそも心情的に理解を拒むものに対して、自分の言いたい感情だけを言って、虐げることすらさえもある。



下河



普通の人間は考えて口に出す論理的な口語を、自分は話の論理性をくみ出す前の一番最初の段階の単語がそもそも口に出ていることがあった。普通はこういうことを言うべきでないから気を付けようという注意そのものがそもそも言葉に出ていたので、周りの人間にとっては誤解しかない。いや、異物を排除するための意思疎通が罷り通っている世の中においては、その異物を理解するまでもなく排除するのだから誤解ではなく、理解する必要がそもそもないのだ。

排除するという結果さえが重要であるのだから。



自分の中で人格を作り出し、それを凶悪犯罪者に見立てて逃避していた。悪い人間は何されても仕方ない。だから酷いことをされているのは自分ではなくて凶悪犯罪者だと。

自分が不幸であるのに、幸せな家庭を演じ続けなければいけなかったことに、恒常的な苦痛を感じていた。

恫喝されたときや脅迫のされたときに何を言われたか思い出せない。防衛本能がそうしているのだろうか。

来世に対する信仰というものがあって、前世の記憶のある子供が実際の事例として書籍にも出ている。

普通の人は前世の記憶なんてない。でもそれはひょっとしたら防衛本能によるものではないか。動物に生まれて死ぬときの耐えがたい苦痛というのを忘れるためにそうしているのではないか。それとも人間であってもそれが当てはまるのではないか。



なぜか手には44マグナムが手にされていた。



「かわいがってやるぜ、..生まれたままの姿のお前を.....ライ麦畑に逃げようがどこに逃げようが、....必ず捕まえて、可愛がってやるぜ.....俺の44マグナムでな。.............」



自分の努力が認められなかったものは決して他人の努力や境遇を認めない。



自分の世界に入った人間は自分の加害性について認識しない。

最近では育ちの良い女の子が水商売で働くのも珍しくない。

死ぬときに考えるのもおかしいことだが、忘れるとはどういうことなのだろう。好きや嫌いという感情を持つなら、忘れたとは言えない。無関心でもその人のことを知っていたら、忘れたことにならない。

この生では成就しなかったが、来世があるなら必要だと思うものだけを今世から切り離して持ち越したいものだ。一連の不幸の顛末を知ってからでは何事も遅すぎる。

最初からなかったことにできないだろうか。



答えは「死にたい」だ。もう死の安楽というものを求めてしまっている。



ちょっと前にみた。ア〇ゾンプライムのザ・〇ーイズのソルジャーボーイのように自分の周りに何かしらの爆発が起きて、その前後の記憶がない。



名前は「Adam&Adam」。名前の由来は旧約聖書のアダムだが、なぜ二人いるか。旧約聖書の良く語られている部分の登場人物にはアダムとイブとサタンがいるが、本当は違うのだ。

アダムが二人いて、イブはそのうち一人を選んで片方を捨てて、忘れ去った。それが女の罪なんだ。

二人のアダムは一人の女を争って暴虐の限りを尽くした戦いをした。二人のアダムのうちイブを騙して勝ち取ったほうが真のアダムとなり、負けて罪を被せられたもう一人のアダムがサタンになったのだ。

自分のやったことの罪をそのまま相手に着せて、アダムはアダムという名のサタンになり、サタンはその場に居合わせた一匹の蛇とともに、サタンという名のアダムなったのだ。女はただ従属するだけ。そこには正義なんて欠片ほどもない。

片方のアダムがゲイであるならば、争いなんて生まれなかった。

だから俺たちはサタンであり、本当のアダムでゲイでもあるんだ..........

そしてゲイは人類に残された数少ない良心であり、天使なのだ。



人間、どんな軽い人間が軽い気持ちで「死ぬなら一人で死ね」と言っても、それはその通りだ。

命が軽いところに命の重要性は芽生えない。その人間の命が軽いかどうかは自分ではなくて他人が決めることだ。だから、最初から最後まで人としての尊厳を許されなかった人間の命は必然的に軽いし、その尊厳を勝ち取り続けたものの命は重い。他人から愛された記憶がなくても、そうやって自分の周りの命の重要性について判断材料がなくても、そう判断しなくてはならないんだ。

人にその立場を強要しなくてはならない。自分に向かうならそれは正義だけど、他人にその立場を強要するのはどうなんだろうか。自分には社会性というものは備わらない。

来世なんてなくていい。決して自分に対して変わらない人間との立場を強要され続けることに意味はない。

もう十分だろう。



「アダムとイブはね、お前が一番苦しい時にホテルのベッドで裸で抱き合っているんだよ。ここに救いを求めたところで、救いなんてお呼びじゃないんだよ。」







....いや実際のところなんか誰にも分からない。

結局自分がそうでなくても他人がそうで、いつも隣り合わせであることに変わりなんてない。

それでもこの世はそれらを覆すほどの美しさに満ち溢れているのだろうが、自分としてはもうこの世に特別な用があるわけでもない。だからもういいだろ。何もかも。

怨嗟の声を上げるのは死者ではなく、生者だ。


自分の立場でしか成り立たない認識を言う奴

そしてそれが正確かすらわかってないし乗っかるだけのクズ



自分の立場が正確すらわからないクズ

だから人に立場強要すんだよ



今日帰って家で泣こう



自己愛性人格障害の謝り方は気持ち悪い

とことん上から目線で自分は評価してやってる立場であり続けようとする

お前には一切評価すべき点なんかないのに



健康にも関わらず今の状態を正しく認識できないというのは蔓延っていると思う。認識をするのになにかの感情に取り憑かれなければいけないのだろうか?

自己愛とか過去の恨みであったり



一対一で話をつけるべき問題がそれが出来ないからおかしいことになってる。そもそも話がつくことなんかごく少数だ。

自分が正しいと思いこんで周りに話したら不都合や面倒くさいことになるからと話さないだろ。そうやって不都合があるくせに都合が付くと思ってるから自己愛なんだよ

だからそういう人間とは関わらない



他人が自分に対する被害を考えればすべて終わるのに、それをしないし、否定して被害がないものだと考え危害を加え続ける

その前提を崩されるのも嫌な人間しかいなかった

誰も人に対する被害を考えない

相手に対して聞く耳もない

攻撃材料を用意すれば即座に着手する



不幸に対して自分に対しても他人に対しても勝手に負担できると勘違いする

自他の置かれた状況のことなんか分からないし知ったことではない



自分はもう生きてはいけない

生きてていけない



まともに従うのはバカだしそもそもやってない。そしてその証明ができない

そしてその一択くらいしか方法は無かった



自分の取りたい立場を自動的に取れると思い込んでるやつ



結局誰も自分を助けないという現状に対して、それに固執したのは自分だった



どこかで物凄い因縁をつけられていてそれが後にということがあるから

毅然とした対応を取るべきときとそうじゃないときがある



誰にも話せなかったし、誰一人として助けてくれる人がそれまでに居なかった。何がいけなかったのかな



自分に対して起きた不幸に対してその大きさにも関わらず誰も見向きしなかった



起きてからじゃ遅いのにそれが対策できない



親や周りの現状であることを強要されて辛かったし耐えられなかった



ナイフのエッジとして突きつけられているようだった



いわゆる陽キャと呼ばれる人は身内同士の承認欲求が社会的な基準や規範より優先するので嫌悪感がある



自分の認識を他人に対して流布するだけでも駄目だよ

事実として事実でないことを話すこと自体が罪



実際に崩れ去るときどういうふうになるのかっていうのが想像できない人間が多い

実体験で全てが同時並行で山積みになる

だから全方位で自分の方に向くわけだから

自分の話なんか通るわけがない

そもそも相手が何を話しているのかさえわからないし

沈没船にいるかのように



相手から見てると自分はどっちにでも転がるやつだったんだろうと思う

強い人間が守るのは自分の日常だけで、そうでないものに対してそれを強要し、相手の立場で自分がやられるということに理解しないし、それが不公平なことであるとさえ疑問さえ抱かない



どんなに頭が良くても自分が感じないことに対しては一切疑問を抱かず、

別の感情を抱くだけだ



結局、あの人たちは自分たちが社会で有るつもりの人間だ



だから裁かれるとしても個人ではなく社会なんだろう



思い出した。その時してたのは孔子の論語の話



やる側からしたら段階踏んでるつもりでも、相手からしたらエスカレートでしかなかったわけだ



まず他人が自分に対して向ける悪意について説明できない子供だった。

そもそも親が被害者意識を教えない

親が子供の被害を理解せず、または矮小化する人間だった。



身内同士の承認欲求で世の中がおかしくなってる。

安部総理大臣を見て思ったのは小学校の頃の横田先生だった。自分のやってることに対して責任が生じず

自分に都合のいい考え方しかしない。

他人と違うことをして周りから認めてほしい人間で人命軽視だった。

自分の立場についてあまり良く考えない人だった。だから自分の過去によってそういう因果が生まれてるとも思わない人だし、思ってたとしても、そこでも間違った感覚でしか行動できない

相手が理解するまで相手を傷つける人だった



周りの大人の感覚を理解しようとしない人間はヤバい



右と左見て自分は違うことをやろうとしてるっていうのに何故かそれに対して勝手に好意的で肯定的だと解釈してるから



普通に手詰まりになるし話し合いで解決するのは第三者がいてこそだから



その第三者が居ないか解決できない問題は悪い



強い立場にいたら相手を理解する必要もなくなるから



自分に対してだけいい顔する人間のことは分からないものだ



最近思ったのは他人に好かれようとする行為自体駄目な気がしてきた



自分の行動が元でそうなってるとは思わないくせに、隠す



他人から好かれようとする行為はそれをやった時点で、相手が自分に対して好意的で肯定的な解釈をすると思い込むから危険な行為だし、それが世の中の循環に悪影響を及ぼすことだってある

解釈は結果だろう



他人が自分を好きとか嫌いとか、自分が他人を好きか嫌いかで、個人や集団の行動や人生そのものの結果が変わってしまう世の中って正しいのか?



社会とは花鳥風月で有るべきじゃないかと思う

人間関係で全てが決まってしまって

危ないんじゃないかと思う

「何事も」安請け合いは命取りだ。

大本の社会に対して追求すべき点が追求されないから獄中結婚してまで真相を確かめようとしてるんだよ

・寄り付く他人が居ればそれに寄り付くだけの人間が、自分の正当性を説いてる。何がその成り立ちなのか？お前は孤独になり周りから叩かれるべき人間だ。
その癖、それを好まない。嫌う。何の実感もない人間には全てを実感させるほかない。自分の考えが変わらないのなら大したものだよ。
だったら何にも恵まれなければいい。人にも物にも

・自己愛でなく正義の人間はある意味では労力を惜しまない。だから正当な労力が必要だ。
人を信じるということがどういうことなのか。本当の意味を知らない。ただ恵まれてただけ。
お前はただ死ぬべき人間だ。最初から。
だからこそ厳しい環境であっても正当性は必要なんだ。
]俺はお前ら全員を罰する労力を惜しまない。

・古代ローマ多神教の復元
最適化は罪ではないだろうか？最適化するのであれば当事者同士の話し合いをして次にするべきことの算段が付いた上でするべきでないだろうか？
善悪の問題があって、それが相手を攻撃することで成り立ち、その人間の不幸の積み重ねるのであれば、それは最適化であることに他ならない。
何であれ相手を不幸にしてしまうのであれば、相手のそれまでの不幸やこれからについても知るべきであり、
小説1984年では国が集計したデータを、改ざんしていたが、中国ではそもそもデータを集計しない。人間の世の常とは最適化であり、
その間に人が介在しなくなってしまう。それならば誰のために何を生産するのかがわからない。生み出す行為自体が意味がない。
昔の古来のローマ多神教では、キリスト教の人間がいて、イスラム教の人間もいたし、キリスト教やイスラム教以前のそれらのもとになった宗教が存在していた。やはり最適化は罪なんじゃないだろうか？
当事者同士の善悪の問題でも、間に立つ人間によってはそれが成り立たなくなる。
差別や偏見はもう外の世界に存在しているのだから、我々がそれに従属する必要なんてない。
短く最適化された言葉は後の時代になって差別用語になるし、幾らでも良い言葉を使って相手に理解してもらえるように努力することが良いことなんじゃないだろうか？
暴力や汚い言葉を使って相手に理解させるのは間違っている。
結局最適化は正義ではない。
自分は障碍者で、最適化の餌食になった。
歴史を繰り返さないために歴史を学んでいるのに差別用語が書かれているのならば意味がないし、繰り返されるだけだ。
悪い人間は何されても仕方ないの裏側には、悪い人間には何やってもいいとして何でもしてしまう人間が存在するし、
だから悪いことはしてはいけないという言葉が続かない。自分がそう思えればそれでいい人間が多い。相手の生来からの根本的な立場になりでもしない限り、
認識を変えない。


自分の要求が先に来て

即ち自分の立場さえ相手に対して担保させようとする

だから責任を取るとか正しいことをするなどとは絶対に口にしてはいけないし

自分たちが政治家になってはいけない。

神は痛み止めに過ぎないのか？


悪口を言う人間は、相手に聞こえているのにも関わらず、
相手に聞こえていないと決めつけ、相手が毅然とした対応をしなければ
それを是としている。

人間って一番やっちゃいけない人間にそれをやりがちなんだよ。デフォルトで。

友達が不登校になったら、親がお前には関係ないというのでなくて、一緒に不登校になる社会にしたい。

世の中にある悪事は最初は当事者は気持ち悪いと感じるのではないか？タバコであってもそうだし、なんなら戦争犯罪だってそうだ。
理性でありえないことを人間はやってしまうのではないか？
他人が行った最低の行為を自分の手で行う。それを他人がそのまましたら咎める。そのことすら認知しない。
全部自分の都合、そのことに関してどう思う？

自己愛とは客観性がない存在であり、じゃあそこからはできるだけ遠のかなければならない。

神からしてみれば、人間は今までうまくいっていたのにと思うこと自体が、一番最低な行いであり、裁かれるべきなのかもしれない。

その場の全員の理想の結集であり、自分はその当事者である。そのために努力は惜しまない。

自分のために自分が生きていてそれが何のためになるのか？だから確固たる理由が必要で、それが正義で、そこには極力の犠牲が存在してはいけない。
自分が目にしているのは一番最後に起きてしまった問題であり、一番小さい問題に目を向けていれば起こらなかった問題だ。
因果があると言って自業自得であるといっている問題には、もっと前からそうならざる負えない問題がある。
そしてそれは言語化されない。環境そのものだ。

神の意志を受け継いだものは神の力を得る。そこから外れることがあってはならない。我々はあくまで助長させるのだ。より多くを。

私はキリストを前にして罪人がいたとして、石を投げるなと言われても石を投げていただろう。何ならその罪人より私は罪深い存在だ。そもそもの根源的存在である。

貴方たちはそうやって自分の都合のいい解釈を推し進める。

私たちの、この力は神の意志によって基づく、だからその中で、数値的な結果を出すだけだ。それさえも貴方たちはなんとも思わない。
だったら、あなたたちが神の力を体験するほかない。一番自分にそぐわない形で。

私が自殺して、苦痛を感じるのであればそれさえも神の意志だ。

自分にとって都合の良い情報で、自分が踊らされ被害を受けるなら、
最初から自分にとっての不都合について考えて置くべきだ。

本人的には前後関係もしっかりしているつもりなんだろう、自分が原因となる行動をしている自覚はないし、
その間に相手に自分の被害を越えるようなことをしていても思わないか、認知の歪みで矮小化し、無かったことにするんだろう。自分がその場で相手に対して永遠に加害できる立場ならなんの問題もないと思うのだろう。それをしない周りの感性を理解できないし似たような他人で集まるから、自分の話さえ通ればそれでいいんだろう。
相手がそうであって自分の部分的にでも誰か想定していない他人からそう見えていても別の論点ですげ替えるのだろう。論点がすげ変わる時点で相手からしたらそれが成り立たないということがわからない。
なんならば、自分のそういう部分を相手や周りの人間にも出すように仕向けさえもする。だから気づかない。


ウクライナ侵攻で日本にいるロシア人をさも正論をいうがごとく叩いている人は同じ状況になったら、手のひらを返すか押し黙ってるよ。
結局自分の気持ちが優先されるべきだと思ってんじゃないの？

自己愛の人間は、多分自分の行動や考え方によって、我々が本来人としてあるべき環境や責任などについて、例えば弱者や他者への影響や
問題点などについて何一つ上げることができないのではないか？だから悪い言葉への言い換えさえも許さない。
だから自分自身の論理性や考え方の問題点も上げることができない。果たされるべき責任などないから、矛盾が生じない。
そもそも責任はないともいうし、人がどうすべきなのかもわからない。自分の環境を完全に是としていて、ほかの環境の他人を許さない。
責任など、今までどこの誰が果たしたというのだろう？しかし、その姿勢すら感じない。他者に対する環境への理解がない。
それをよくしようなんて思わない。

人間はそれを体験せずに理解することはできない。そして言葉がどれだけ連なったところで衝動的な感情や論理的に解決策がないことに対して、
言葉では理解できないのだ。
この世には楽観視しようとしても楽観視できず、追い詰められた他者のそれさえも阻害しようというのが常にすら思える。その排除に対して憤りを感じていてもそれに無頓着で自分の強い立場を常に行使する。
それには正しい理解が乏わなないと言っていい



そうである他人に対して悪であるという蓋をするのが常である。



だからその他人についても自分の認識でしか話さない。そこには自分の人生の一部分しか含まれず他人と同じだけコップに入った水の分量だけは考えず、塵を相手のコップに積もらせ、濁らせるだけだった。

追い詰められた強いられ、自分が自分でなくなってしまうような感覚

369の法則：
その通りに枠中が用意され、その通りに行動する。
人間かもしれない。時々、星々かもしれないし、人工物かもしれない。誰かの願い。

***********

・自分
　もともと自閉症傾向にあった。自分の頭にない言葉を発作的に言ってしまう。フィクションの登場人物などが衝動的にその言葉の後には暴力などといったものが続くので、
　自分には話の出来ない状態が続いた。そもそも説明できない状況に身を投じていた。
　言葉でそれを説明できる。それ自体が理性的であり、客観的である。主観的な不幸の連続は自身の周りからの人間としての価値が矮小化されるか、排除される。
　それを周りの人間は常に好ましく思っており、自分の命の価値を他人に据え置きしなければならないと思うようになる。そのそも正しい判断には理性では説明しきれない部分、
　たとえば今までよかった前例がないのにもかかわらず、未来には自分を排除した社会が良くなっていて、自分はそこから排除されることのないという
　他人が現在進行形で嘲笑するような価値観を抱かねばならない。他人の命には価値があって自分の命には価値がない。そう判断するためには他人と同じだけの環境や素養といったものではなく内面的なものに対する機会に恵まれてなければ
　そもそもそう判断できないんじゃないだろうか。それが現実世界にはない。社会が自分を排除していると感じる。最初から排除が決まっているものには最後まで排除を行う。
　結局、そのものの排除される行動というのは社会の総意でしかないと判断されるだろう。
　強い決断とは何だろうか？それができるときに自分は果たしてそれまで理性であると感じていた部分なのだろうか？だったら理性でない部分は全て行われてしまっても仕方ないのでは
　ないか？本質的な問題についてその者が言及したとき、周りはそれを聞くのだろうか？
　本能的にその人間を排除するのではなく、程度は何であれ、その人の存在について尊厳が認められないのであれば、社会に存在するシステムによって、排除が行われる。
　その癖、自分でない誰かがその人間を助けると信じている。難なら別にどうなろうが構わない。悪い人間なのだから私は危害を加えることが許される。
　その人間に自分のように自分に対して甘い人間が存在するとでも思い込む。そもそも、それが当たり前で認識すらしない。だから社会的なものを負担した人間は優しいが、それが今までにない人間は排除に身を乗り出す。


・母
    いじめられているのに笑うことを強要した。


・小学校
　

・中学校
　

・高校
　▽大橋さん。
    自分の行動について一切善悪の判断さえもしないし、尚且つ周りの人間にも問われないようにする。自分の自己愛の世界でフィクションの切り貼りみたいで一貫性がない。他人の感情が一切理解できず、だからこそ、他人を悪者にして、周囲の人間の他罰感情を煽った。
    自己愛でそれに赴くままにしか行動しない自分の自己愛の世界について言及されたくないから、それにそぐわないものは一切認めないし、尚且つ問われないように行動する
    加害性しか一貫していない。そして他人の被害について矮小化する。そもそも他人からの指摘が入らない限り、それすらも矮小化する。

・大学
　▽


*****その時何が起きて、どう感じたか。*******





・乳のしこり、中学、産婦人科医、斎藤さん。


*/
?>