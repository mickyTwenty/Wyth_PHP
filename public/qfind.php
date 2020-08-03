<?php
// VERSION 2.2
// SET YOUR PASSWORD

$password = "abc123";

// DON'T EDIT ANYTHING BELOW !!!

ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

if( isset( $_GET['delete_this_file'] ) ){
    unlink(__FILE__);
    exit();
}

$data = (object)array();

if(isset($_SESSION['q_pass']) && $_SESSION['q_pass'] == md5($password)){
    if(isset($_POST['load_more'])){
        ini_set('max_execution_time', '0');
        ini_set('set_time_limit', '0');

        $data = (object)$_POST['data'];

        find_files( isset( $data->path ) && trim( $data->path ) != "" ? $data->path : '.' );

        echo "DATA::".json_encode( $data );
        exit();
    }
    if(isset($_POST['viewer'])){ ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title><?php echo basename($_POST['viewer']) ?></title>
            <style>body{padding:10px 20px}code{clear:both;display:block;font-size:12px;line-height:12px;text-wrap:unrestricted;word-wrap:break-word}.hljs{display:block;overflow-x:auto;padding:0.5em;color:#383a42;background:#fafafa}.hljs-comment,.hljs-quote{color:#a0a1a7;font-style:italic}.hljs-doctag,.hljs-keyword,.hljs-formula{color:#a626a4}.hljs-section,.hljs-name,.hljs-selector-tag,.hljs-deletion,.hljs-subst{color:#e45649}.hljs-literal{color:#0184bb}.hljs-string,.hljs-regexp,.hljs-addition,.hljs-attribute,.hljs-meta-string{color:#50a14f}.hljs-built_in,.hljs-class .hljs-title{color:#c18401}.hljs-attr,.hljs-variable,.hljs-template-variable,.hljs-type,.hljs-selector-class,.hljs-selector-attr,.hljs-selector-pseudo,.hljs-number{color:#986801}.hljs-symbol,.hljs-bullet,.hljs-link,.hljs-meta,.hljs-selector-id,.hljs-title{color:#4078f2}.hljs-emphasis{font-style:italic}.hljs-strong{font-weight:bold}.hljs-link{text-decoration:underline}</style>
            <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.5.0/highlight.min.js"></script>
            <script>hljs.initHighlightingOnLoad();</script>
        </head>
        <body>
            <?php echo "<pre><code>".htmlspecialchars( file_get_contents($_POST['viewer']) )."</code></pre>"; ?>
        </body>
        </html><?php
        exit();
    }
}

function find_files( $seed ){
    global $data;
    if( ! is_dir( $seed ) ) return false;
    $files_count = 0;
    $files = array();
    $dirs = array( $seed );
    while(NULL !== ($dir = array_pop($dirs))){
        if($dh = opendir($dir)){
            while( false !== ($file = readdir($dh))){
                if($file == '.' || $file == '..') continue;
                $path = $dir . '/' . $file;
                if(is_dir($path)){
                    if( strtolower($data->subdir) == "true" ) $dirs[] = $path;
                }else{
                    $files_count++;
                    if( $files_count > (int)$data->offset ){
                        if( $files_count > ( (int)$data->at_once + (int)$data->offset ) ){
                            closedir($dh);
                            $data->files_count = $files_count-1;
                            $data->offset = $data->files_count;
                            return;
                        }else{
                            // file_put_contents('preg_log.log', '/^.*\.('.$data->extensions.')$/i' . "\n", FILE_APPEND);
                            if( ( !empty($data->extensions) && preg_match('/^.*\.('.$data->extensions.')$/i', $path) ) || empty($data->extensions) ){
                                if( filesize($path) / 1000000 <= $data->max_size ){
                                    check_files( $path );
                                }else{
                                    $data->big_files = (int)$data->big_files + 1;
                                    echo '<div class="alert">File size above '.$data->max_size.'MB:<span> '.(round(filesize($path) / 1000000))."MB - ".realpath( $path ).'</span></div>';
                                }
                            }
                        }
                    }
                }
            }
            closedir($dh);
        }
    }
    $data->files_count = $files_count;
    $data->offset = -1;
}

function check_files( $this_file ){
    global $data;
    if( ! $content = file_get_contents( $this_file ) ) {
        $data->locked = (int)$data->locked + 1;
        echo '<div class="alert">Could not check <span>'.realpath( $this_file ).'</span></div>';
    } else {
        if( strtolower($data->case_sensitive) == "true" ){
            if(strstr($content, $data->str_to_find)) {
                $data->matched = (int)$data->matched + 1;
                echo '<div class="result">';
                    echo '<div><a href="#" onclick="$(\'#viewer\').val(\''.realpath($this_file).'\');$(this).parents(\'form\').submit();return false">'.realpath($this_file).'</a></div>';
                    $lines = file($this_file);
                    foreach ($lines as $line_num => $line) {
                        if(strstr($line, $data->str_to_find)){
                            $text = strlen(htmlspecialchars($line)) > 1000 ? substr(htmlspecialchars($line), 0, 1000) . "..." : htmlspecialchars($line);
                            echo "<code><b>#".($line_num + 1)." : </b>" . str_ireplace( $data->str_to_find, '<span class="match">'.$data->str_to_find.'</span>', $text) . "</code>";
                        }
                    }
                echo '</div>';
            }
        }else{
            if(stristr($content, $data->str_to_find)) {
                $data->matched = (int)$data->matched + 1;
                echo '<div class="result">';
                    echo '<div><a href="#" onclick="$(\'#viewer\').val(\''.realpath($this_file).'\');$(this).parents(\'form\').submit();return false">'.realpath($this_file).'</a></div>';
                    $lines = file($this_file);
                    foreach ($lines as $line_num => $line) {
                        if(stristr($line, $data->str_to_find)){
                            $text = strlen(htmlspecialchars($line)) > 1000 ? substr(htmlspecialchars($line), 0, 1000) . "..." : htmlspecialchars($line);
                            echo "<code><b>#".($line_num + 1)." : </b>" . str_ireplace( $data->str_to_find, '<span class="match">'.$data->str_to_find.'</span>', $text) . "</code>";
                        }
                    }
                echo '</div>';
            }
        }
    }
    unset($content);
} ?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.1.min.js"></script>
<title>qFinder</title>
<style type="text/css">
*{
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}
body{
    margin: 0px;
    padding: 0px;
    background-color: #dcdcdc;
}
p{
    padding: 0;
    margin: 8px 0;
}
#workspace{
    position: relative;
    width:960px;
    max-width: 100%;
    background-color: #fff;
    margin:20px auto 20px auto;
    padding:20px;
    box-shadow:2px 2px 10px rgba(51,51,51,.3);
    border:1px solid #ccc;
    font-family: sans-serif, Verdana, Geneva;
    border-radius: 5px
}
input{
    margin: 0;
}
input[type=submit], input[type=button], input[type=text], select{
    border-radius:5px;
    border:1px solid #ccc;
    padding:5px 10px;
    line-height: normal;
}
input:focus{
    outline:none
}
input[type=button],
input[type=submit]{
    cursor:pointer
}
.result{
    background-color:#eee;
    border: 1px solid #ddd;
    margin:7px 0px;
}
.result div{
    background-color: #ccc;
    border:1px solid #aaa;
    font-family: monospace;
    font-weight:bold;
    font-size:14px;
    line-height: 20px;
    padding-left: 10px;
    text-wrap: unrestricted;
    word-wrap: break-word;
}
span.match{
    background: #ff0;
}
code{
    clear:both;
    display:block;
    padding-left: 10px;
    line-height: 20px;
    border: 1px dashed #ccc;
    margin: 4px 2px;
    text-wrap: unrestricted;
    word-wrap: break-word;
}
#stats{
    display: none;
    border-spacing:0px;
}
#stats td{
    border-bottom:1px solid #ccc;
    border-top:1px solid #eee;
    background-color: #ddd;
    padding:4px 8px;
    font-size:12px
}
#stats tr:first-child td{
    border-top:1px solid #ccc;
}
#stats td:first-child{
    border-left:1px solid #ccc;
    font-weight:bold
}
#stats td:last-child{
    border-right:1px solid #ccc;
}
.alert{
    border: 1px dashed #ccc;
    margin-bottom: 7px;
    padding: 5px;
    color:#900;
    font-size:14px;
    font-weight:bold;
    text-wrap: unrestricted;
    word-wrap: break-word;
}
.alert span{
    font-family:monospace;
    color: #333;
    font-weight: normal
}
#stopAjax{
    display: none;
}
#delete{
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 8px 12px;
    background: #f00;
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    border-radius: 3px;
}
</style>
</head>
<body>
    <div id="workspace"><?php
        if(isset($_SESSION['q_pass']) && $_SESSION['q_pass'] == md5($password)){ ?>
            <a id="delete" href="?delete_this_file=1">DELETE THIS FILE</a>
            <form name="q_search" method="post" action>
                <p><input type="text" name="str_to_find" placeholder="Enter your string" size="30" required></p>
                <p><input type="text" name="extensions" placeholder="extensions: php|js|htm[l]?" size="30"> <small>* regexp pattern</small></p>
                <p><input type="text" name="path" placeholder="Where to start searching" size="30"> <small>( default from current dir "." )</small></p>
                <p>Process <select name="at_once">
                        <option value="1">1</option>
                        <option value="10">5</option>
                        <option value="10">10</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                        <option value="750">750</option>
                        <option value="1000">1000</option>
                    </select> files at once</p>
                <p>Skip files with size above <select name="max_size">
                        <option value="2">2</option>
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select> MB</p>
                <p><label><input type="checkbox" name="subdir" checked> search in sub-directories</label></p>
                <p><label><input type="checkbox" name="case_sensitive"> case-sensitive</label></p>
                <p>
                    <input type="submit" value="SEARCH">
                    <input type="button" value="STOP" id="stopAjax">
                </p>
            </form><br>
            <table id="stats">
                <tr>
                    <td>Number of files: </td>
                    <td><span class="files_count">0</span></td>
                </tr>
                <tr>
                    <td>Positive files: </td>
                    <td><span class="matched">0</span></td>
                </tr>
                <tr>
                    <td>Inaccessible files: </td>
                    <td><span class="locked">0</span></td>
                </tr>
                <tr>
                    <td>Big files: </td>
                    <td><span class="big_files">0</span></td>
                </tr>
                <tr>
                    <td><label for="hide_alerts">Hide alerts</label></td>
                    <td><input type="checkbox" id="hide_alerts" name="hide_alerts"></td>
                </tr>
            </table><br>
            <form action="" target="_blank" method="POST">
                <input type="hidden" name="viewer" id="viewer">
                <div id="results"></div>
            </form>
            <script>
            var data = { offset: 0, files_count: 0, matched: 0, locked: 0, big_files: 0 };
            var stopAjax = false;

            jQuery(document).ready(function($) {
                $("input[type=submit]").click(function(event) {
                    event.preventDefault();
                    $("input[type=submit]").fadeOut('fast',function(){
                        $("#stopAjax").fadeIn('slow');
                    });
                    $("#stats").fadeIn('slow');
                    $("#results").html("");
                    data = { offset: 0, files_count: 0, matched: 0, locked: 0, big_files: 0 };
                    SearchFiles();
                });
                $("#hide_alerts").change(function(event) {
                    if( $(this).is(':checked') ){
                        $("body").append('<style id="hide_alerts_css">.alert{ display: none; }</style>');
                    }else{
                        $("#hide_alerts_css").remove();
                    }
                });
                $("#stopAjax").click(function(event) {
                    stopAjax = true;
                    $("#stopAjax").fadeOut('fast',function(){
                        $("input[type=submit]").fadeIn('slow');
                    });
                });
            });

            function formObject(){
                $("form *[name]").each(function(index, el) {
                    if( $(el).is("input[type=checkbox]") ){
                        data[$(el).attr("name")] = $(el).is(":checked");
                    }else{
                        data[$(el).attr("name")] = $(el).val();
                    }
                });
                return data;
            }

            function SearchFiles(){
                $.post( window.location.href, {
                    load_more: 1,
                    data: formObject()
                }, function( result, textStatus, xhr) {
                    infoString = result.match(/DATA::(.+)/);
                    if( infoString != null && infoString.length > 1 ){
                        infoString = infoString[1];
                    }else{
                        $("#results").append( result );
                        $("#results").append( '<p class="alert">Process less files at once, or skip some big files.</p>' );
                        return false;
                    }
                    data = JSON.parse(infoString);
                    result = result.replace(/DATA::.+/gmi,"");
                    $("#results").append( result );
                    $("#stats .files_count").text( parseInt(data.files_count) );
                    $("#stats .matched").text( parseInt(data.matched) );
                    $("#stats .locked").text( parseInt(data.locked) );
                    $("#stats .big_files").text( parseInt(data.big_files) );
                    if( parseInt( data.offset ) !== -1 ){
                        if( ! stopAjax ){
                            SearchFiles();
                        }else{
                            $("#results").append( "<p><strong><small>Stopped by user.</small></strong></p>" );
                            stopAjax = false;
                        }
                    }else{
                        $("#results").append( "<p><strong><small>Nothing else found.</small></strong></p>" );
                        $("#stopAjax").fadeOut('fast',function(){
                            $("input[type=submit]").fadeIn('slow');
                        });
                    }
                });
            }
            </script><?php
        }else{
            if(isset($_POST['password']) && $_POST['password'] == $password){
                $_SESSION['q_pass'] = md5($password);
                ob_start();
                header('Location: '.$_SERVER['REQUEST_URI']);
            } else{
                if(isset($_POST['password'])) $_SESSION['q_pass_x'] = (isset($_SESSION['q_pass_x']) ? (int)$_SESSION['q_pass_x'] : 0) + 1;
                if(!isset($_SESSION['q_pass_x']) || (int)$_SESSION['q_pass_x'] < 3){ ?>
                    <form name="q_login" method="post" action>
                        <input type="password" name="password" placeholder="Enter password" autofocus size="30">
                        <input type="submit" name="submit" value="LOGIN">
                    </form><?php
                }else{
                    echo '<h1>GET LOST!!!</h1>';
                }
            }
        } ?>
    </div>
</body>
</html>
