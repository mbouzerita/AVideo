<?php
global $global, $config;
if(!empty($_GET['session_id'])){
    session_id($_GET['session_id']);
    error_log("HLS.php: session_id changed to ".  $_GET['session_id']);
}
if(!isset($global['systemRootPath'])){
    require_once '../videos/configuration.php';
}
_error_log("HLS.php: session_id = ".  session_id()." IP = ".  getRealIpAddr().
        " URL = ".($actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"));
if(empty($_GET['videoDirectory'])){
    die("No directory set");
}

$video = Video::getVideoFromFileName($_GET['videoDirectory'], true);
$filename =  "{$global['systemRootPath']}videos/{$_GET['videoDirectory']}/index.m3u8";
$_GET['file'] = "{$global['systemRootPath']}videos/{$_GET['videoDirectory']}/index.m3u8";
//var_dump($_GET['file']);exit;
$cachedPath = explode("/", $_GET['videoDirectory']);
if(empty($_SESSION['hls'][$cachedPath[0]])){
    AVideoPlugin::xsendfilePreVideoPlay();
    $_SESSION['hls'][$cachedPath[0]] = 1;
}

$tokenIsValid = false;
if(!empty($_GET['token'])){
    $secure = AVideoPlugin::loadPluginIfEnabled('SecureVideosDirectory');
    if($secure){
        $tokenIsValid = $secure->isTokenValid($_GET['token'], $_GET['videoDirectory'], $_GET['videoDirectory']);
    }
}

// if is using a CDN I can not check if the user is logged
if($tokenIsValid || !empty($advancedCustom->videosCDN) || User::canWatchVideo($video['id'])){
    $content = file_get_contents($filename);
    $newContent = str_replace('{$pathToVideo}',  "{$global['webSiteRootURL']}videos/{$_GET['videoDirectory']}/../", $content);
    if(!empty($_GET['token'])){
        $newContent = str_replace('/index.m3u8',  "/index.m3u8?token={$_GET['token']}", $newContent);
    }
}else{
    $newContent = "Can not see video [{$video['id']}] ({$_GET['videoDirectory']}) ";
}
header("Content-Type: text/plain");
echo $newContent;