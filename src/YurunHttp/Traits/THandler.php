<?php
namespace Yurun\Util\YurunHttp\Traits;

use Yurun\Util\YurunHttp\Http\Psr7\Uri;

trait THandler
{
    /**
     * 处理重定向的 location
     *
     * @param string $location
     * @param \Yurun\Util\YurunHttp\Http\Psr7\Uri $currentUri
     * @return \Yurun\Util\YurunHttp\Http\Psr7\Uri
     */
    public function parseRedirectLocation($location, $currentUri)
    {
        $locationUri = new Uri($location);
        if('' === $locationUri->getHost())
        {
            if(!isset($location[0]))
            {
                return;
            }
            if('/' === $location[0])
            {
                $uri = $currentUri->withQuery('')->withPath($location);
            }
            else
            {
                $path = dirname($currentUri);
                if('\\' === DIRECTORY_SEPARATOR && false !== strpos($path, DIRECTORY_SEPARATOR))
                {
                    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
                }
                $uri = new Uri($path . '/' . $location);
            }
        }
        else
        {
            $uri = $locationUri;
        }
        return $uri;
    }
}
