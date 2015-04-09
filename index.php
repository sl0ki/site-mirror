<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);

include("Proxy.php");

$proxy = new Proxy();

$proxy->setRequestHook(function(&$header, &$body) {
    // Modify some header or body
    // array_push($header, ['Origin' => $_SERVER["HTTP_HOST"]]);
    // array_push($header, ['X-Requested-With' => 'XMLHttpRequest']);
});

$proxy->setResponseHook(function(&$header, &$body) {
    // Modify some header or body
});

$proxy->render('http://stackoverflow.com/' . $_GET['url']);
