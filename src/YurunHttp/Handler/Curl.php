<?php
namespace Yurun\Util\YurunHttp\Handler;

class Curl implements IHandler
{
    private $result;

    /**
     * 发送请求
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    public function send($request)
    {
        $handler = curl_init();
		// if(!empty($requestBody))
		// {
		// 	if(is_array($requestBody))
		// 	{
		// 		$this->content = http_build_query($requestBody, '', '&');
		// 	}
		// 	else if($requestBody instanceof HttpRequestMultipartBody)
		// 	{
		// 		$this->content = $requestBody->content();
		// 		$this->contentType(sprintf('multipart/form-data; boundary=%s', $requestBody->getBoundary()));
		// 	}
		// 	else
		// 	{
		// 		$this->content = $requestBody;
		// 	}
		// }
		$options = [
			// 请求方法
			CURLOPT_CUSTOMREQUEST	=> $request->getMethod(),
			// 返回内容
			CURLOPT_RETURNTRANSFER	=> true,
			// 返回header
			CURLOPT_HEADER			=> true,
			// 发送内容
			// CURLOPT_POSTFIELDS		=> $this->getBody(),
			// 保存cookie
			// CURLOPT_COOKIEFILE		=> $this->cookieFileName,
			// CURLOPT_COOKIEJAR		=> $this->cookieFileName,
			// 自动重定向
			// CURLOPT_FOLLOWLOCATION	=> self::$customLocation ? false : $this->followLocation,
			// 最大重定向次数
			// CURLOPT_MAXREDIRS		=> $this->maxRedirects,
        ];
		// 自动解压缩支持
		if('' !== $request->getHeaderLine('Accept-Encoding'))
		{
			$options[CURLOPT_ENCODING] = $this->headers['Accept-Encoding'];
		}
		else
		{
			$options[CURLOPT_ENCODING] = '';
		}
		curl_setopt_array($handler, $options);
		// $this->parseSSL();
		// $this->parseOptions();
		// $this->parseProxy();
		// $this->parseHeaders();
		// $this->parseCookies();
		// $this->parseNetwork();
        $count = 0;
        
        curl_setopt($handler, CURLOPT_URL, $request->getUri());
        $this->result = curl_exec($handler);
		// do{
		// 	// for($i = 0; $i <= $this->retry; ++$i)
		// 	// {
		// 	// 	$response = new HttpResponse($this->handler, curl_exec($this->handler));
		// 	// 	$httpCode = $response->httpCode();
		// 	// 	// 状态码为5XX或者0才需要重试
		// 	// 	if(!(0 === $httpCode || (5 === (int)($httpCode/100))))
		// 	// 	{
		// 	// 		break;
		// 	// 	}
		// 	// }
		// 	if(self::$customLocation && (301 === $httpCode || 302 === $httpCode) && ++$count <= $this->maxRedirects)
		// 	{
		// 		$url = $response->headers['Location'];
		// 	}
		// 	else
		// 	{
		// 		break;
		// 	}
		// }while(true);
		// 关闭保存至文件的句柄
		// if(isset($this->saveFileOption['fp']))
		// {
		// 	fclose($this->saveFileOption['fp']);
		// 	$this->saveFileOption['fp'] = null;
		// }
    }

    /**
     * 接收请求
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    public function recv()
    {

        curl_close($handler);
        return $this->result;
    }

    /**
     * 获取Body
     * @param \Yurun\Util\YurunHttp\Http\Psr7\ServerRequest $request
     * @return void
     */
    protected function getBody($request)
    {

    }
}