<?php
/*
  Plugin Name: CustomHeaderPhotoLinker
  Plugin URI:
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


document.body.clientWidth;

register_activation_hook(__FILE__, 'chpla_install');// 有効化の際に一度だけ処理
register_uninstall_hook ( __FILE__, 'chpla_delete_data' );// 無効化の際に一度だけ処理


//add_action('init', 'CustomHeaderPhotoLinker::init');
add_action(
    'wp_loaded', 
     //create_function('', 'return register_widget("CustomHeaderPhotoLinker");')   
     new CustomHeaderPhotoLinker()
);
add_action( 'customize_register', function ( $wp_customize ){
    //ここにカスタマイザー登録処理

}


/* 初回読み込み時にテーブル作成 */
function chpla_install(){
    global $wpdb;
    
    $table = $wpdb->prefix.'locs_info';
    $charset_collate = $wpdb->get_charset_collate();

    // 画像位置情報
    if ($wpdb->get_var("show tables like '$table'") != $table){
        
        $sql = "CREATE TABLE  {$table} (
            ID int(11) not null auto_increment,
            FILE_PATH VARCHAR(400),
            LOC_OF_CANVAS text,
            LINK text,
            MEDIA_TYPE VARCHAR(400),
            CUR_PHOTO_SIZE VARCHAR(400),
            ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // 写真情報
    $table = $wpdb->prefix.'photo_info';
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
            add_action( 'wp_head', 'hiddenInformations' );
        }
    }

    function action(){
      // アクションに対するコールバック関数その物
    }

    function filter(){
      // コールバック関数に付随する処理
    }

    function customizerAddon(){

    }

    ///////////////////////////////////////
    // テーマカスタマイザーにロゴアップロード設定機能追加
    ///////////////////////////////////////
    function theme_customizer( $wp_customize ) {
        
        $wp_customize->add_panel( 'my_panel_setting', array(
            'priority' => 1,
            'title' => '画像リンクの追加',
          ));

        $wp_customize->add_section( 'my_theme_setting', array(
            'title'    => '設定', 
            'priority' => 1,
            'panel'    => 'my_panel_setting'    
        ));

        // 選択画像

        $wp_customize->add_section( 'my_theme_text', array(
            'title'    => 'テキスト', 
            'priority' => 2,
            'panel'    => 'my_panel_setting'    
        ));
        //　対象のCANVAS画像
        $wp_customize->add_setting( 'my_text', array(
            'type'      => 'option',
            'sanitize_callback' => 'wp_filter_nohtml_kses'
         ));
    
        $wp_customize->add_control( 'my_text', array(
            'label'       => 'text', 
            'type'        => 'text',
            'section'     => 'my_theme_text', //大本
            'settings'    => 'my_text', 
            'description' => 'textを設定してください。', 
        ));
        // 画像の今の大きさ
        $wp_customize->add_setting( 'my_text', array(
            'type'      => 'option',
            'sanitize_callback' => 'wp_filter_nohtml_kses'
         ));
    
        $wp_customize->add_control( 'my_text', array(
            'label'       => 'text', 
            'type'        => 'text',
            'section'     => 'my_theme_text', //大本
            'settings'    => 'my_text', 
            'description' => 'textを設定してください。', 
        ));


        // 貼り付ける画像と座標
        for(){
            add_photo_uploader();
            add_info_form();
        }

    }

    function add_photo_uploader($num){
        $wp_customize->add_section( LOGO_SECTION , array(
            'title' => 'ロゴ画像' + num, //セクション名
            'priority' => 30 + num, //カスタマイザー項目の表示順
            'description' => 'サイトのロゴ設定。その' + num, //セクションの説明
        ) );

        $wp_customize->add_setting( LOGO_IMAGE_URL );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, LOGO_IMAGE_URL, array(
            'label' => 'ロゴ', //設定ラベル
            'section' => LOGO_SECTION + num, //セクションID
            'settings' => LOGO_IMAGE_URL + num, //セッティングID
            'description' => '画像をアップロードすると画像を追加できます。',
        ) ) );
    }

    function add_info_form($num, $wp_customize){

        // 座標

        $wp_customize->add_section( 'my_theme_text', array(
            'title'    => 'テキスト', 
            'priority' => 2,
            'panel'    => 'my_panel_setting'    
        ));
    
        $wp_customize->add_setting( 'my_text', array(
            'type'      => 'option',
            'sanitize_callback' => 'wp_filter_nohtml_kses'
         ));
    
        $wp_customize->add_control( 'my_text', array(
            'label'       => 'text', 
            'type'        => 'text',
            'section'     => 'my_theme_text', 
            'settings'    => 'my_text', 
            'description' => 'textを設定してください。', 
        ));

        // URL

        $wp_customize->add_section( 'my_theme_url', array(
            'title'    => 'URL', 
            'priority' => 6,
            'panel'    => 'my_panel_setting'    
        ));
    
        $wp_customize->add_setting( 'my_url', array(
            'type'      => 'option',
            'sanitize_callback' => 'esc_url_raw'
         ));
    
        $wp_customize->add_control( 'my_url', array(
            'label'       => 'url', 
            'type'        => 'url',
            'section'     => 'my_theme_url', 
            'settings'    => 'my_url', 
            'description' => 'urlを設定してください。',
        ));

    }


    add_action( 'customize_register', 'themename_theme_customizer' );//カスタマイザーに登録

    //ロゴイメージURLの取得
    function get_the_logo_image_url(){
        return esc_url( get_theme_mod( LOGO_IMAGE_URL ) );
    }


    function hiddenInformation() {

        // データベースより情報取得
        echo '<span class="photo_locations_information">';

        // 対象画像の選択
        echo '<input type="hidden" name="photo_size" value="{$location}">';
        echo '<input type="hidden" name="element_path" value="{$location}">';

        //if ( current_user_can('administrator') || current_user_can('editor') || current_user_can('author') ):
        global $wpdb;
        $query = "SELECT * FROM $wpdb->wp_locs_info ORDER BY ID LIMIT 10;";
        // $query = "SELECT * FROM $wpdb->wp_locs_info ORDER BY ID LIMIT 10;";
        $results = $wpdb->get_results($query);
        foreach($results as $row) {
            $id = $row->ID;
            $location = $row->LOCATION;

            $photo_path = $row->PATH;
            $link = $row->LINK;
            //<?php echo get_the_logo_image_url(); 
            echo <<<EOF
            <input type="hidden" name="point_loc{$id}" value="{$location}">
            <input type="hidden" name="photo_path{$id}" value="{$photo_path}">
            <input type="hidden" name="hidden_link{$id}" value="{$link}">
            <input type="hidden" name="media_type{$id}" value="{$media_type}">
            EOF;
        }
        // echo "ユーザー名は：" . $results[4]->user_login . nl2br("\n") .
        // "ディスプレイ名は：" . $results[4]->display_name ;
        echo "タイトル：" . $results[37]->post_title . nl2br("\n") . "投稿日時：" . $results[37]->post_date;
        //endif;

        // 場所と画像のファイル、リンク先
        echo '</span>';
    }

    function update_logos_information($id, $location, $photo_path, $link)
    {
        global $wpdb;

        $query = "SELECT * FROM $wpdb->wp_locs_info WHERE ID = $id";

        $results = $wpdb->get_results($query);


        if($wpdb->num_rows)// レコードの数
        {
            $query = "DELETE FROM $wpdb->wp_locs_info WHERE ID = $id";
            $results = $wpdb->get_results($query);
        }
        $query = "INSERT INTO $wpdb->wp_locs_info (ID, FILE_PATH, LOC_OF_CANVAS, LINK) VALUES ($id, $location, $photo_path, $link)";

        $results = $wpdb->get_results($query);
    }

    function update_photo_infomation($element_path, $photo_size)
    {
        global $wpdb;

        $query = "SELECT * FROM $wpdb->wp_photo_info";

        $results = $wpdb->get_results($query);

        $row_first = array_shift($results);

        $id = $row_first->ID;

        if($wpdb->num_rows)// レコードの数
        {
            $query = "DELETE FROM $wpdb->wp_photo_info ORDER BY ID ASC LIMIT 1";
            $results = $wpdb->get_results($query);
        }
        $query = "INSERT INTO $wpdb->wp_photo_info (ID, PHOTO_SIZE, ELEMENT_PATH) VALUES (, $element_path, $photo_size)";

        $results = $wpdb->get_results($query);
    }

    function widget(){
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
            background-color: 
            ;
            //opacity: 0.5;
            display:inline-block;
        }
        .active_pre_process{
            opacity: 0.5;
            display: block;
        }
        </style>
        <script type="text/javascript">
        var url = location.href;

        //画面がカスタマイザーによる処理なのかでモード変更 urlではなく　bodyタグのクラスによる認識に変えること
        var custom_mode = false;
    
        if(url.includes('customize'))
        {
            custom_mode = true;
    
        }
        var blockElems = ['ADDRESS', 'BLOCKQUOTE', 'CENTER', 'DIR', 'DIV', 'DL', 'FIELDSET', 'FORM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'HR', 'ISINDEX', 'MENU', 'NOFRAMES', 'NOSCRIPT', 'OL', 'P', 'PRE', 'TABLE', 'UL', 'LI', 'OL', 'ARTICLE', 'FIGURE'];
    
        var targetImage = document.getElementById('main-feat-img');// 配列に変えなければならない
        //var targetImages = [];//　貼り付け先の画像Id 文字列から要素を呼び起こして配列に格納する
        var idCanvas = document.getElementById('maps');// 配列に変えなければならない
        //var idCanvasArr = [];//　上記のtargetImagesに設定するCanvasを格納 文字列から要素を呼び起こして配列に格納する
        var style = window.getComputedStyle(idCanvas);// 配列に変えなければならない
        //var canvasStyles = [];//　上記のidCanvasに格納されている各配列のスタイルを取得 文字列から要素を呼び起こして配列に格納する
    
        var pointX = -1;
        var pointY = -1;
        var indexNum = 20;
        var arrTField = new Array(indexNum);
        var arrShapes = new Array(indexNum);
        var canvasNum = 5;
        //var arrCanvas = new Array(canvasNum);// 画像までのパスを示す文字列　非対応
        var oneCanvas = "";
        var arrElemCanvas = new Array(canvasNum);
        var arrPathCanvas = [];// パスを保存する2次配列
    
        // 対象画像指定
        var arrImgField = new Array(5);
        var testContext;
        //　最後にアクセスしたカスタマイザー文字Input
        var lastFocusField;
        // 対象画像領域指定
        var lastFocusImgField;
        //　最後にアクセスした対象パネル画像の幅と高さ
        var lastWidth = -1;
        var lastHeight = -1;
        var width;
        var height;
        var lnk_elems;
        // 対象画像領域指定オンオフ
        var imgFieldOnOff = false;
    
        // 2022追加 追加canvasについてid取得用
        var icons_map = null;
        // CANVAS範囲配列初期設定
        //for(var i = 0 ; i < canvasNum; i++){
    
        //}
        // デフォルトのマウスポインター値
        var defMousePointer = body.style.cursor;
        var mouseDownOnCanvasIconBool = false;
    
        // CANVASの初期設定の写経　全部のCANVASに対して
        if (idCanvas.getContext && idCanvas.getContext('2d').createImageData) {
            testContext = idCanvas.getContext('2d');
        }
        // img要素からCanvasに画像を転送(ロード時＆リサイズ時)
        function resizePhoto(targetImage)
        {
            targetImage = document.getElementById('main-feat-img');
            if ( targetImage.complete ) {
                width = targetImage.naturalWidth ;
                height = targetImage.naturalHeight ;
                //var width = targetImage.width();
                //var height = targetImage.height();
                if(lastWidth > 0 || lastHeight > 0)
                {
                    pointX = pointX * ( parseFloat(style.width.replace("px","")) / lastWidth );
                    
                    pointY = pointY * ( parseFloat(style.height.replace("px","")) / lastHeight);
                    //alert('resize:' + (pointX) + ',' + (pointY));
                    if(lastFocusField)
                    {
                        // カスタマイザーの対象の文字フィールドに座標入力
                        lastFocusField.value = pointX / parseFloat(style.width.replace("px","")) + ',' + pointY / parseFloat(style.height.replace("px",""));
                        var arrIndex = (parseFloat(lastFocusField.id.replace('_customize-input-my_theme_options_origin_text_XY', ''), 10) - 1);
                        arrShapes[arrIndex] = lastFocusField.value;
                    }
                }
                // 対象Canvasの大きさを固定
                idCanvas.width = width;
                idCanvas.height = height;
                //testContext = idCanvas.getContext("2d");
                // Canvasの大きさを固定し画像を再度貼り付け治す処理
                putImageToCanvas(width, height);
                // 一番最初に取得した要素の横幅、縦幅を再度取得
                lastWidth = parseFloat(style.width.replace("px",""));
                lastHeight = parseFloat(style.height.replace("px",""));
    
            }
            loadCanvas();
        }
        // リサイズ対応 画像を取得し、再度貼り付け治す（配列に処理を書き換えること）
        function putImageToCanvas(width, height) {
            targetImage = document.getElementById('main-feat-img');
            testContext.drawImage(targetImage, 0, 0, width, height);
        }
        //　リサイズ対応　　一つのキャンバスにつき一回は必要な処理 再描写＆一番最初の描写
        function loadCanvas(){
            for(var i = 0; i < arrShapes.length; i++){
                // テキストフィールドから座標を取得
                if(arrShapes[i] && arrShapes[i].includes(','))
                // 座標に図形を書き込む　画像に書き込みたい
                loadShapePositions(parseFloat(arrShapes[i].split(',')[0]) * parseFloat(style.width.replace("px","")), (parseFloat(arrShapes[i].split(',')[1])*parseFloat(style.height.replace("px",""))));
            }
        }
        //　ユーザー側で必要な処理　クリック時にリンク先に飛ばす
        function mouseDownListner(e) {
            /*
            // 要素の短径を取得し、全体からのマウス位置に減算すると要素内でのマウスクリック位置
            var rect = e.target.getBoundingClientRect();
            //座標取得
            var mouseX1 = e.clientX - rect.left;
            var mouseY1 = e.clientY - rect.top;
            // 押下した座標が図形だった場合、リンク先に飛ぶ　±20は図形の大きさ
            for(var i = 0; i < arrShapes.length; i++){
                if(arrShapes[i] && arrShapes[i].includes(',')){
                    if (mouseX1 > parseFloat(arrShapes[i].split(',')[0]) * parseFloat(style.width.replace("px",""))-20 && mouseX1 < parseFloat(arrShapes[i].split(',')[0]) * parseFloat(style.width.replace("px","")) + 20) {
                        if (mouseY1 > parseFloat(arrShapes[i].split(',')[1]) * parseFloat(style.height.replace("px",""))-20 && mouseY1 < parseFloat(arrShapes[i].split(',')[1]) * parseFloat(style.height.replace("px","")) + 20) {


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
    
        // pointXとpointYにクリックした要素内の座標を格納
        function getCanvasPointXY(e){
            var rect = e.target.getBoundingClientRect();
            pointX = (e.clientX - rect.left);
            pointY = (e.clientY - rect.top);
            //alert((pointX) + ',' + (pointY));
        }
        // 座標を最後にアクセスしたテキストフィールドに反映させる。(カスタマイザー)
        function setXYPointToText(e){
            if(lastFocusField) // 初期値なし
            if((lastFocusField.id.includes('_customize-input-my_theme_options_origin_text_XY'))){
                let e_ch = new Event('change');
                let e_ch2 = new Event('input');
            
                lastWidth = parseFloat(style.width.replace("px",""));
                lastHeight = parseFloat(style.height.replace("px",""));
                lastFocusField.value = pointX / parseFloat(style.width.replace("px","")) + ',' + pointY / parseFloat(style.height.replace("px",""));
                //alert((style.width) + ',' + (style.height));
                lastFocusField.dispatchEvent(e_ch);//カスタマイザーの公開ボタン
                lastFocusField.dispatchEvent(e_ch2);//カスタマイザーの公開ボタン
                var arrIndex = (parseFloat(lastFocusField.id.replace('_customize-input-my_theme_options_origin_text_XY', ''), 10) - 1);
                arrShapes[arrIndex] = lastFocusField.value;
                loadShapePositions(pointX, pointY);
            }
            //alert(typeof lastFocusField);
        }
        // 図形を指定個所に書く
        function loadShapePositions(textContext, width, height , style, posX, posY){
            //alert(posX+','+posY);
            if(testContext){
                testContext.beginPath();
                testContext.fillStyle = "rgba(" + [255, 255, 150, 0.5] + ")";
                //testContext.rect(posX / parseFloat(style.width.replace("px","")) * width, posY / parseFloat(style.height.replace("px","")) * height, 75, 50 ); // canvasで認識しているグリッドの単位がpxとずれているので治す
                testContext.arc(posX / parseFloat(style.width.replace("px","")) * width - 5, posY / parseFloat(style.height.replace("px","")) * height - 5, 20, 0, Math.PI*2, false);
                testContext.fill();
                testContext.closePath(); // サブパス閉じる
            }
        }
        // デフォルト値 初期設定　hidden要素取得
        function loadPointPositions(){
            // リンク情報
            lnk_elems = document.getElementsByClassName("point_link");// widgetに追加するhidden input
            // XY座標
            var loc_points = document.getElementsByClassName("location_point");// widgetに追加するhidden input
            //	Canvas情報(どのキャンバスにするか選択) & CANVASのIDにて要素取得
            //for(var i = 0; i < canvasNum; i++){
            //    "maps_" + i;
            //}
    
            // 此処に追加
            // カスタマイザーのテキストフィールド上から反映
            for(var i = 0; i < arrShapes.length; i++){
                arrShapes[i] = loc_points[i].value;//クラスの集合から取得
                arrTField[i] = lnk_elems[i].value;//クラスの集合から取得　ここ以外(描画以外)で使う
                if(arrShapes[i].includes(','))
                loadShapePositions(parseFloat(arrShapes[i].split(',')[0]) * parseFloat(style.width.replace("px","")) , (parseFloat(arrShapes[i].split(',')[1])*parseFloat(style.height.replace("px",""))));
            }
        }
    
        // 子要素の走査
        function scanChildElems(elem, tagName, path, minMax, layerNum){
            var addedPath = path;
            var result = false;
            var addedTagAttr = "";
    
            //　タグ名追加
            if(elem.nodeName){
                // addedTagAttr += "{\$tag:" + elem.tagName + "}";
                addedTagAttr += "{\$tag:" + elem.nodeName + "}";
            }
            // id名追加
            if(elem.id){
                addedTagAttr += "{\$id:" + elem.id + "}";
            }
            // class名追加
            if(elem.className){
                let doc = document.getElementsByClassName(elem.className);
                let idx = doc.indexOf(elem);
                addedTagAttr += "{\$cls:" + elem.className + "[" + idx + "]" + "}";
            }
            if(addedPath.length > 0)
            addedPath += "=>";
            addedPath += addedTagAttr;
    
            if(minMax.toLowerCase() == "min" && addedPath.indexOf(tagName) >= 0){
                // 指定したタグが入っている場合
                //return addedPath;
            }else{
                if(elem.firstElementChild){
                    addedPath = scanChildElems(elem.firstElementChild, tagName, addedPath, minMax,layerNum++);
                }
                // 指定したタグが入って居なく深堀が必要
                if(addedPath.indexOf(tagName) < 0){
                    if(layerNum > 0){
                        if(elem.nextElementSibling){
                            addedPath = scanChildElems(elem.nextElementSibling, tagName, Path, minMax, layerNum);
                        }
                    }
                    //alert("2:" + elem.nextElementSibling.nodeName);
                }else{
    
                }
            }
            return addedPath;//関数のネスティング(再起処理)で全部の文字列が返る
        }
    
        // 要素から該当タグの要素取得
        function targetTAGChildParser(targetElem, remainTagArr, terminal){
            var elemBox = targetElem;
            if(elemBox.tagName == terminal){
    
                return elemBox;
            }else if(elemBox.tagName == remaintagArr[0]){
                if(remainTagArr.length > 0){
                    elemBox = targetTAGChildParser(targetElem.firstElementChild, remainTagArr.slice(1), terminal);
                }else{
                    return null;
                }
            }else{
                if(elemBox.nextElementSibling){
                    elemBox = targetTAGChildParser(telemBox.nextElementSibling, remainTagArr, terminal);
                }else{
                    return null;
                }
            }
            return elemBox;
        }
    
        // 文字列に入っている該当の要素から要素取得
        // canvas要素を追加する
        function initCanvasField(){
            const regexpID = /\{\$id:(.+)\}/g;
            const regexpCLS = /\{\$cls:(.+)\[?(\d*)\]?\}/g;
            const regexpTAG = /\{\$tag:(.+)\[?(\d*)\]?\}/g;
            //const regexpSEC = /=>/g;
    
            //for(var i = 0; i < arrCanvas.length; i++){
            var u = 0;
            //arrCanvas[i] = window.parent.document.getElementById("_customize-input-my_theme_header_photo_id_class" + (i+1)).value;
            
            oneCanvas = window.parent.document.getElementById();// 修正必要
            // 上記に対してCANVASタグを追加する
            //まず対象を取得する
            //var arrExps = new Array();
            //if(arrCanvas[i].lastIndexOf('=>') > 0)
            //{
            //	while(arrExps=regexpSEC.exec(arrCanvas[i])!=null{
            //		arrPathCanvas[i][u++] = arrExps.index; // インデックスを保存
            //	}	
            //}
            //for(var q = 0; q < arrPathCanvas[i].length; q++){
            //}
            var arrPathCanvas = [];
            //arrPathCanvas[i] = arrCanvas[i].split("=>");
            arrPathCanvas = oneCanvas.split("=>");// 一つ一つの親子要素について配列順に入れなおす
            var targetROOT = arrPathCanvas[0];// 一番最初の親要素
            var targetElem;
            var candyElements;
            for(var q = 0; q < arrPathCanvas[i].length; q++){
                var idMatch = regexpID.test(targetROOT);
                var clsMatch = regexpCLS.test(targetROOT);
                var tagMatch = regexpTAG.test(targetROOT);
                if(idMatch)
                {
                    let idWord = regexpID.exec(targetROOT);
                    targetElem = document.getElementById(idWord[1]);// 正規表現の二つ目の要素が()に入っている値を取得
                }else if(clsMatch){
                    let classWord = regexpCLS.exec(targetROOT);
                    candyElements = document.getElementsByClassName(classWord[1]);
                    if(idWord[2] != "")
                    {
                        targetElem = candyElements[parseInt(idWord[2])];//　IDからとれる場合は、さかのぼってクラスの配列から取得
                    }else{
                        targetElem = candyElements;
                    }
                }else if(tagMatch){
                    var arrTagName = [];
                    var remaingIndex = 0;
                    for(var v = q; v < arrPathCanvas[i].length; v++)
                    { // タグの配列に直す
                        // 正規表現でタグ名取得する
                        arrTagName[remaingIndex] = arrPathCanvas[v];
                        remaingIndex++;
                    }
                    targetElem = targetTAGChildParser(targetElem, arrTagName, "IMG");
                    break;
                }
                targetROOT = arrPathCanvas[i][q+1];// 次の子の要素をセット　この時にtargetElemには目的の子要素までの実際の要素が入っている
            }

            // 上記で取得したIMGタグについてCANVASタグを設定
            var installed = targetElem.parentNode;
            var new_canvas = document.createElement('canvas');
            //new_canvas.id = "maps";
            new_canvas.id = "icons_maps";
            new_canvas.innerHTML = installed.innerHTML;
            targetElem.before(new_canvas);
            targetElem.remove();
            //}
        }
    
        // マウスオーバー処理
        document.addEventListener("mouseover", function(event) {
            console.log(event.target); //event.targetの部分がマウスオーバーされている要素になっています
            
            if(icons_map == event.target)
            {
                // 該当座標でマウスポインターの変換

                // mouseDownListenerと同じ処理 そっちを削除するべきかもしれない
                // 要素の短径を取得し、全体からのマウス位置に減算すると要素内でのマウスクリック位置
                var rect = e.target.getBoundingClientRect();
                //座標取得
                var mouseX1 = e.clientX - rect.left;
                var mouseY1 = e.clientY - rect.top;
                // 押下した座標が図形だった場合、リンク先に飛ぶ　±20は図形の大きさ
                for(var i = 0; i < arrShapes.length; i++){
                    if(arrShapes[i] && arrShapes[i].includes(',')){
                        if (mouseX1 > parseFloat(arrShapes[i].split(',')[0]) * parseFloat(style.width.replace("px",""))-20 && mouseX1 < parseFloat(arrShapes[i].split(',')[0]) * parseFloat(style.width.replace("px","")) + 20) {
                            if (mouseY1 > parseFloat(arrShapes[i].split(',')[1]) * parseFloat(style.height.replace("px",""))-20 && mouseY1 < parseFloat(arrShapes[i].split(',')[1]) * parseFloat(style.height.replace("px","")) + 20) {
                                
                                mouseDownOnCanvasIconBool = true;

                                if(icons_map != null)
                                {
                                    icons_map.style.cursor = "pointer";
                                }
    
                                // リンクに飛ばす処理
                                //if(lnk_elems[i].value)
                                //location.href = lnk_elems[i].value;// canvas内の図形のリンクに飛ばす
                            }else{ // 図形を指していない場合はマウスポインターを元に戻す
                                icons_map.style.cursor = defMousePointer;
                                mouseDownOnCanvasIconBool = false;
                            }
                        }
                    }
                }
            }
        });    
    
        idCanvas.addEventListener('load', function(){
            resizePhoto(targetImage);
            //loadPointPositions();
            // 上記のマウスオーバー関数
            icons_map = document.getElementById("icons_maps");

            if (icons_map === null){
                // 要素が存在しない場合の処理
            } else {
                // 要素が存在する場合の処理
            }


            loadCanvas();
        }, false);
    
        idCanvas.addEventListener('click', function(e){
            //resizePhoto();
            if(custom_mode){
                getCanvasPointXY(e);
                setXYPointToText(e);
            }
        }, false);
    
        window.addEventListener('resize', function(){
            resizePhoto(targetImage);
            //loadPointPositions();
    
            //これは場所の座標のテキストフィールド　全てに実施
            for(var i = 0; i < arrShapes.length; i++){
                if(arrShapes[i].includes(','))
                loadShapePositions(parseFloat(arrShapes[i].split(',')[0]) * parseFloat(style.width.replace("px","")), (parseFloat(arrShapes[i].split(',')[1])*parseFloat(style.height.replace("px",""))));
            }
        }, false);
        
        var intervalId = setInterval( function(){
            resizePhoto();
            clearInterval( intervalId ) ;
        }, 500 );
    
    
    
        document.addEventListener("DOMContentLoaded", function(){
            // 画像要素キャンパスの追加　一番最初に必要
            initCanvasField();
    
    /*　複数の画像に対して実装する予定だった。カールセル対応
            const regexpSEC = /=>/g;
    
            for(var i = 0; i < arrCanvas.length; i++){
                var u = 0;
                arrCanvas[i] = window.parent.document.getElementById("_customize-input-my_theme_header_photo_id_class" + (i+1)).value;// 修正必要
                // 上記に対してCANVASタグを追加する
                //まず対象を取得する
                var arrExps = new Array();
                if(arrCanvas[i].lastIndexOf('=>') > 0)
                {
                    while(arrExps=regexpSEC.exec(arrCanvas[i])!=null){
                        arrPathCanvas[i][u++] = arrExps.index; // インデックスを保存
                    }
                }
            }
    */
    
            testContext = idCanvas.getContext("2d");
            //testContext.beginPath();
            if(custom_mode)
            {
                var prev_page = document.getElementById('page');// 上部に要素追加に必要
                var prev_added = document.getElementById('masthead');// 上部に要素追加に必要
                var added_elem = document.createElement('div');
                added_elem.id = 'option_menu';
                added_elem.innerHTML = '<button id="addPoint">AddPoint</button>';
                added_elem.style = "width:100%; height:50px;"
                prev_page.insertBefore(added_elem,prev_added);
            }
    
            //var titleBtn = document.getElementById('customize-controls');//document.getElementById('accordion-section-my_theme_origin_scheme');
            var titleBtn = document.getElementById('addPoint');
            //var textFieldsContainer = document.getElementById('sub-accordion-section-my_theme_origin_scheme');
            if(titleBtn){
                titleBtn.addEventListener("click", function(){
                    //if(!textFieldsContainer){ return false;}
                    //textFieldsContainer.addEventListener("load", function(){
                        for(var i = 0; i < arrTField.length; i++){
                            arrTField[i] = window.parent.document.getElementById('_customize-input-my_theme_options_origin_text_XY' + (i+1)); // iframeしているときは外側に走査走らないためwindow指定
                            //if(!arrTField[i]){ return false;}
                            //alert(arrTField[i].nodeName);
                            arrTField[i].addEventListener('focus', function(e) {
                                lastFocusField = e.currentTarget;
                            }, false);
                        }
                    //}, false);
                }, false);
            }
    
            //指定する(画像領域)
            // カスタマイザー要素に該当の座標を記述する(Canvasをクリックした際に最後にフォーカスを当てたテキストボックスに記述されるためのモノ)
            for(var i = 0; i < arrImgField.length; i++){
                arrImgField[i] = window.parent.document.getElementById('_customize-input-my_theme_header_photo_id_class' + (i+1)); // iframeしているときは外側に走査走らないためwindow指定
                arrImgField[i].addEventListener('focus', function(e) {
                    lastFocusImgField = e.currentTarget;
                    console.log(lastFocusImgField.id);
                    imgFieldOnOff = true;
                }, false);
            }
            // 残処理
    
            resizePhoto();
            loadPointPositions();
            loadCanvas();
            if(!custom_mode)
            idCanvas.addEventListener("mousedown", mouseDownListner, false);// canvas内のリンクに飛ばす
        }, false);
    
        //https://note.com/fuminon3745/n/n33184d12ce30
        // 指定した要素に対して場所をフィールドに記載し、色付けてわかるようにする。
        document.body.onclick = (e) => {
            if(imgFieldOnOff){
                // デフォルトのイベントをキャンセル
                e.preventDefault();
                
                //var pageX = e.pageX;
                //var pageY = e.pageY;

                //他で指定したものを削除する処理
                var preset_processed_jav = document.getElementsByClassName('test');
                classList.remove();


                var preset_processed_css = document.getElementsByClassName('test');

                //他で指定したものを削除する処理　ここまで
                
                var rect = e.target.getBoundingClientRect();
                //var elementUnderMouse = document.elementFromPoint(pageX, pageY);
                var elementUnderMouse = document.elementFromPoint(rect.left, rect.top);
                
                //console.log(pageX + ", " + pageY + " :" + elementUnderMouse);
                console.log(rect.left + ", " + rect.top + " :" + elementUnderMouse);
                //const range = document.createRange();
                //range.selectNodeContents(elementUnderMouse);
                
                //const selection = window.getSelection();
                //selection.removeAllRanges();
                //selection.addRange(range);
    
                //var tagName = elementUnderMouse.tagName;
                //if(tagName != 'IMG'){
                //	alert(tagName);	
                //}
                var nodeChain = elementUnderMouse;
                //elementUnderMouse.style.backgroundColor = "#FFFF00";
                // 透明にして背景色
                //elementUnderMouse.style.opacity = "0.5";
                //elementUnderMouse.style.display = "block";
                elementUnderMouse.classList.add(active_pre_process);


                lastFocusImgField.value = "";
                while(true){
    
                    if(lastFocusImgField.value.length > 0){
                        lastFocusImgField.value += "=>";
                    }
                    if(nodeChain.tagName){
                        //lastFocusImgField.value += "{\$tag:" + nodeChain.tagName + "}";
                        lastFocusImgField.value = "{\$tag:" + nodeChain.nodeName + "}" + lastFocusImgField.value;
                        //　要素を繰り上がる
                    }
                    if(nodeChain.id !== null && nodeChain.id !== undefined && nodeChain.id)
                    {
                        lastFocusImgField.value = "{\$id:" + nodeChain.id + "}" + lastFocusImgField.value;
                        break;
                    }
                    if(nodeChain.className !== null && nodeChain.className !== undefined && nodeChain.className){
                        let doc = document.getElementsByClassName(nodeChain.className);
                        doc = [].slice.call(doc);
                        //let idx = doc.indexOf(e.target);
                        let idx = doc.indexOf(elementUnderMouse);
                        lastFocusImgField.value = "{\$cls:" + nodeChain.className + "[" + idx + "]" + "}" + lastFocusImgField.value;
                        break;
                    }
                    nodeChain = nodeChain.parentNode;
    
                }
                //対象のタグが上記で取得できないときは子要素を走査し、該当タグを探す
                if(lastFocusImgField.value.indexOf("IMG") < 0){
                    lastFocusImgField.value = scanChildElems(elementUnderMouse, "IMG", "", "min", 0);
                }
    
                nodeChain = elementUnderMouse;
                // ブロック要素走査しクラス追加 追加される画像の要素を塗りつぶし、わかるようにする
                for(var i = 0; i < 30; i++){
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
                }
                imgFieldOnOff = false;
    
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


マウスオーバーした際に張り付けた画像の上でマウスポインターを変える　OK

画像、取得する

画面縮小時、画像を等倍で縮小するかどうか？　

一つの画像に対して、大きさの異なるメディアでの画像貼り付け位置が選択できる。
貼り付ける画像に対して、大本の画像の状態を取得すること
その状態において表示非表示ができること。

画像を指し示すフォームの値が変わるたびに、その要素を辿って、重ね掛けする。


*/
?>