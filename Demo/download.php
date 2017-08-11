<?php
namespace Yurun\Until;
require dirname(__DIR__) . '/vendor/autoload.php';
try{
	$download = new Download('http://tool.chinaz.com/ChinazSEOTool.zip');
	$download->on('progressChanged', function($e){
		var_dump($e);
	});
	echo $download->getFileSize(), PHP_EOL;
	$download->download(__DIR__ . '/1.zip');
	var_dump($download->isBreakContinue);
}catch(Exception $e)
{
	var_dump($e->getMessage());
}