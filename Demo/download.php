<?php
namespace Yurun\Until;
require dirname(__DIR__) . '/vendor/autoload.php';
try{
	$download = new Download('http://dldir1.qq.com/weixin/Windows/WeChatSetup.exe');
	$download->blockSize = 1048576; // 每一块数据的大小，可以不设置，默认为1M
	// 绑定每一块数据下载完成事件
	$download->on('progressChanged', function($e){
		var_dump($e);
	});
	// 获取文件大小
	echo $download->getFileSize(), PHP_EOL;
	// 下载
	$download->download(__DIR__ . '/1.exe');
	// 获取是否使用断点续传分块下载
	var_dump($download->isBreakContinue);
}catch(Exception $e)
{
	var_dump($e->getMessage());
}