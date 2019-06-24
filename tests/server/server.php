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
    case 'setCookie':
        setcookie('a', '1');
        setcookie('b', '2', time() + 1);
        setcookie('c', '3', 0, '/');
        setcookie('d', '4', 0, '/a');
        setcookie('e', '5', 0, '/', 'localhost');
        setcookie('f', '6', 0, '/', '', true);
        break;
    default:
        // 默认
        echo 'YurunHttp';
}
