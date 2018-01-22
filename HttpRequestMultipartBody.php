<?php
/**
 * Created by PhpStorm.
 * User: xiaozhuai
 * Date: 17/4/23
 * Time: 下午2:14
 */

namespace Yurun\Until;

class HttpRequestMultipartBody
{

    /**
     * 类型，键值对
     */
    const TYPE_KV = 0;

    /**
     * 类型，文件
     */
    const TYPE_FILE = 1;

    /**
     * 类型，文件二进制
     */
    const TYPE_FILE_CONTENT = 2;

    /**
     * 列表
     * @var array
     */
    private $list = array();

    /**
     * 边界字符串
     * @var string
     */
    private $boundary;

    /**
     * 添加键值对
     * @param string $key
     * @param string $value
     */
    public function add($key, $value)
    {
        $this->list[] = array(
            'type'          => static::TYPE_KV,
            'key'           => $key,
            'value'         => $value,
        );
    }

    /**
     * 添加文件
     * @param string $key
     * @param string $file 文件路径
     * @param string $fileName 文件名
     */
    public function addFile($key, $file, $fileName)
    {
        $this->list[] = array(
                'type'      => static::TYPE_FILE,
                'key'       => $key,
                'file'      => $file,
                'file_name' => $fileName
        );
    }

    /**
     * 添加文件，直接传入文件内容
     * @param string $key
     * @param mixed $fileContent
     * @param string $fileName
     * @return void
     */
    public function addFileContent($key, $fileContent, $fileName)
    {
        $this->list[] = array(
            'type'          => static::TYPE_FILE_CONTENT,
            'key'           => $key,
            'fileContent'   => $fileContent,
            'file_name'     => $fileName
        );
    }

    /**
     * 移除键值
     * @param string $key
     * @return void
     */
    public function remove($key)
    {
        $count = count($this->list);
        for($i = 0; $i < $count; $i++)
        {
            if($this->list[$i]['key'] === $key)
            {
                array_splice($this->list, $i, 1);
            }
        }
    }

    /**
     * 清除所有键值
     * @return void
     */
    public function clear()
    {
        $this->list = array();
    }

    /**
     * 获取最终构建的body内容
     * @return string
     */
    public function content()
    {
        $this->generateBoundary();
        $content = '';
        foreach ($this->list as $item)
        {
            switch ($item['type'])
            {
                case static::TYPE_KV:
                default :
                    $content .= sprintf("--%s\r\n", $this->boundary);
                    $content .= sprintf("Content-Disposition: form-data; name=\"%s\"\r\n\r\n", $item['key']);
                    $content .= $item['value'] . "\r\n";
                    break;
                case static::TYPE_FILE:
                    $content .= sprintf("--%s\r\n", $this->boundary);
                    $content .= sprintf("Content-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n", $item['key'], $item['file_name']);
                    $content .= sprintf("Content-Type: application/octet-stream\r\n\r\n");
                    $content .= file_get_contents($item['file']) . "\r\n";
                    break;
                case static::TYPE_FILE_CONTENT:
                    $content .= sprintf("--%s\r\n", $this->boundary);
                    $content .= sprintf("Content-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n", $item['key'], $item['file_name']);
                    $content .= sprintf("Content-Type: application/octet-stream\r\n\r\n");
                    $content .= $item['fileContent'] . "\r\n";
                    break;
            }
        }
        $content .= sprintf("--%s--\r\n\r\n", $this->boundary);
        return $content;
    }

    /**
     * 随机生成一个新的boundary
     */
    private function generateBoundary()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randStr = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < 64; $i++)
        {
            $randStr .= $chars[ mt_rand(0, $max) ];
        }

        $this->boundary = '__BOUNDARY__' . $randStr . '__BOUNDARY__';
    }

    /**
     * 获取边界字符串
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

}
