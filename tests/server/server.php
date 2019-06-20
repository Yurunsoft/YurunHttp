<?php
$action = isset($_GET['a']) ? $_GET['a'] : null;
// sleep(1);
switch($action)
{
    case 'info':
        header('Content-Type: application/json');
        header('Yurun-Http: one suo');
        echo json_encode([
            'get'       =>  $_GET,
            'post'      =>  $_POST,
            'cookie'    =>  $_COOKIE,
            'server'    =>  $_SERVER,
        ]);
        break;
    default:
        // 默认
        echo 'YurunHttp';
}
