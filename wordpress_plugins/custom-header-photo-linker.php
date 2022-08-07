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

register_activation_hook(__FILE__, 'chpla_install');// 有効化の際に一度だけ処理
register_uninstall_hook ( __FILE__, 'chpla_delete_data' );// 無効化の際に一度だけ処理


//add_action('init', 'CustomHeaderPhotoLinker::init');
add_action(
    'wp_loaded', 
     //create_function('', 'return register_widget("CustomHeaderPhotoLinker");')   
     new CustomHeaderPhotoLinker()
);


add_action('widgets_init',  function(){register_widget('CustomHeaderPhotoLinkerWidget');});

/* 初回読み込み時にテーブル作成 */
function dejimono_install(){
    global $wpdb;
    
    $table = $wpdb->prefix.'dejimono_test';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("show tables like '$table'") != $table) {
        
        $sql = "CREATE TABLE  {$table} (
            query_num int, 
            file_name VARCHAR(400),
            item1 VARCHAR(30),
            item2 VARCHAR(30),
            item3 VARCHAR(30)
            ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

/* プラグイン削除時にはテーブルを削除 */
function dejimono_delete_data()
{
        global $wpdb;
        $table_name = $wpdb->prefix . 'dejimono_test';
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query($sql);
}

class CustomHeaderPhotoLinkerAdmin{
    /* 管理画面表示 */
    function chpla_CategoryCreatorMenu()
    {
        add_menu_page('CustomHeaderPhotoLinker Plugin','PhotoLinker','administrator', __FILE__, 'chpla_CategorySettingsPage' , 'dashicons-buddicons-replies');
    }
    
    /* 管理画面表示 */
    function chpla_CategorySettingsPage() {
        echo 'hello world';
        echo plugin_dir_path( __FILE__ );
    }
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
        }
    }

    function action(){
      // アクションに対するコールバック関数その物
    }

    function filter(){
      // コールバック関数に付随する処理
    }

    function widget(){
        $javascript_EOL = <<<CANVAS
        <style>
        /* 選択された画像を塗りつぶして分かるようにする */
        .active{
            /*content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;*/
            background-color: #3399FF;
            //opacity: 0.5;
            display:inline-block;
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
        //var idCanvasArr = [];// 上記のtargetImagesに設定するCanvasを格納 文字列から要素を呼び起こして配列に格納する
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
            
            oneCanvas = window.parent.document.getElementById();
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
                arrCanvas[i] = window.parent.document.getElementById("_customize-input-my_theme_header_photo_id_class" + (i+1)).value;
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
        // 
        document.body.onclick = (e) => {
            if(imgFieldOnOff){
                // デフォルトのイベントをキャンセル
                e.preventDefault();
                
                //var pageX = e.pageX;
                //var pageY = e.pageY;
                
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
                elementUnderMouse.style.opacity = "0.5";
                elementUnderMouse.style.display = "block";
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
                // ブロック要素走査しクラス追加
                for(var i = 0; i < 30; i++){
                    if(nodeChain.tagName){
                        console.log(nodeChain.tagName);
                        if(blockElems.indexOf(nodeChain.tagName) >= 0)
                        {
                            nodeChain.classList.add('active');
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


class CustomHeaderPhotoLinkerWidget extends WP_Widget{

} 
?>