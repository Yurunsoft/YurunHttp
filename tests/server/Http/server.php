<?php
$action = isset($_GET['a']) ? $_GET['a'] : null;
switch($action)
{
    case 'info':
        header('Content-Type: application/json');
        header('Yurun-Http: one suo');
        $files = $_FILES;
        foreach($files as &$file)
        {
            $file['hash'] = md5(file_get_contents($file['tmp_name']));
        }
        echo json_encode([
            'get'       =>  $_GET,
            'post'      =>  $_POST,
            'cookie'    =>  $_COOKIE,
            'server'    =>  $_SERVER,
            'files'     =>  $files,
        ]);
        break;
    case 'setCookie':
        setcookie('a', '1');
        setcookie('b', '2', time() + 1);
        setcookie('c', '3', 0, '/');
        setcookie('d', '4', 0, '/a');
        setcookie('e', '5', 0, '/', 'localhost');
        setcookie('f', '6', 0, '/', '', true);
        setcookie('g', '7', 0, '/', '', true, true);
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
    case 'redirectOther':
        header('HTTP/1.1 302 Found');
        header('Location:https://www.httpbin.org/get?id=1');
        exit;
    case 'download1':
        if('nb' === (isset($_POST['yurunhttp']) ? $_POST['yurunhttp'] : null) && 'POST' === $_SERVER['REQUEST_METHOD'])
        {
            echo 'YurunHttp Hello World';
        }
        break;
    case 'download2':
        if('nb' === (isset($_POST['yurunhttp']) ? $_POST['yurunhttp'] : null) && 'POST' === $_SERVER['REQUEST_METHOD'])
        {
            echo '<h1>YurunHttp Hello World</h1>';
        }
        break;
    case 'body':
        echo file_get_contents('php://input');
        break;
    case '304':
        header('HTTP/1.1 304 Not Modified');
        break;
    case 'auth':
        echo isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        break;
    default:
        // 默认
        echo 'YurunHttp';
}
