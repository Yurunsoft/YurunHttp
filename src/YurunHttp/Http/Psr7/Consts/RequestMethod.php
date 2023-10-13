<?php

namespace Yurun\Util\YurunHttp\Http\Psr7\Consts;

/**
 * 常见的http请求方法.
 */
abstract class RequestMethod
{
    public const GET = 'GET';

    public const POST = 'POST';

    public const HEAD = 'HEAD';

    public const PUT = 'PUT';

    public const PATCH = 'PATCH';

    public const DELETE = 'DELETE';

    public const OPTIONS = 'OPTIONS';

    public const TRACE = 'TRACE';
}
