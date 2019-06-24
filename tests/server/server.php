<?php
$action = isset($_GET['a']) ? $_GET['a'] : null;
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
    case 'redirect301':
        header('HTTP/1.1 301 Moved Permanently');
        header('Location:/?a=info');
        exit;
        break;
    case 'redirect302':
        header('HTTP/1.1 302 Found');
        header('Location:/?a=info');
        exit;
        break;
    case 'redirect307':
        header('HTTP/1.1 307 Temporary Redirect');
        header('Location:/?a=info');
        exit;
        break;
    case 'redirect308':
        header('HTTP/1.1 308 Permanent Redirect');
        header('Location:/?a=info');
        exit;
    default:
        // 默认
        echo 'YurunHttp';
}
