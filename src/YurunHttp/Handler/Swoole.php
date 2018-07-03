<?php
namespace Yurun\Util\YurunHttp\Handler;

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Yurun\Util\YurunHttp\Http\Response;

class Swoole implements IHandler
{
    /**
     * Swoole 协程客户端对象
     *
     * @var \Swoole\Coroutine\Http\Client
     */
    private $handler;

	/**
	 * 请求结果
	 *
	 * @var \Yurun\Util\YurunHttp\Http\Response
	 */
    private $result;
    
    /**
     * 发送请求
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    public function send($request)
    {
        // 解析IP
        $ip = Coroutine::gethostbyname($request->getUri()->getHost());
        // 实例化
        $this->handler = new Client($ip, $request->getUri()->getPort(), 'https' === $request->getUri()->getScheme());
        $this->handler->setDefer();
        // method
        $this->handler->setMethod($request->getMethod());
        // headers
        $headers = [
            'Host'  =>  $request->getUri()->getHost(),
        ];
        foreach($request->getHeaders() as $name => $values)
        {
            $headers[$name] = implode(',', $values);
        }
        $this->handler->setHeaders($headers);
        // cookie
        $this->handler->setCookies($request->getCookieParams());
        // 发送
        $path = $request->getUri()->getPath();
        $this->handler->execute('' === $path ? '/' : $path);
    }

    /**
     * 接收请求
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    public function recv()
    {
        $success = $this->handler->recv();
        $this->result = new Response($this->handler->body, $this->handler->statusCode);

        // headers
        foreach($this->handler->headers as $name => $value)
        {
            $this->result = $this->result->withHeader($name, $value);
        }

        // cookies
		$cookies = [];
        foreach($this->handler->set_cookie_headers as $value)
        {
            $list = explode(';', $value);
			$count2 = count($list);
			if(isset($list[0]))
			{
				list($cookieName, $value) = explode('=', $list[0], 2);
				$cookieName = trim($cookieName);
				$cookies[$cookieName] = array('value'=>$value);
				for($j = 1; $j < $count2; ++$j)
				{
					$kv = explode('=', $list[$j], 2);
					$cookies[$cookieName][trim($kv[0])] = isset($kv[1]) ? $kv[1] : true;
				}
			}
        }
		$this->result = $this->result->withCookieOriginParams($cookies)
									->withError($this->getErrorString($this->handler->errCode))
                                    ->withErrno($this->handler->errCode);
                                    
        $this->handler->close();

        return $this->result;
    }

    private function getErrorString($errCode)
    {
        static $errors = [
            124 => 'EMEDIUMTYPE Wrong medium type',
            123 => 'ENOMEDIUM No medium found',
            122 => 'EDQUOT Disk quota exceeded',
            121 => 'EREMOTEIO Remote I/O error',
            120 => 'EISNAM Is a named type file',
            119 => 'ENAVAIL No XENIX semaphores available',
            118 => 'ENOTNAM Not a XENIX named type file',
            117 => 'EUCLEAN Structure needs cleaning',
            116 => 'ESTALE Stale NFS file handle',
            115 => 'EINPROGRESS +Operation now in progress',
            114 => 'EALREADY Operation already in progress',
            113 => 'EHOSTUNREACH No route to host',
            112 => 'EHOSTDOWN Host is down',
            111 => 'ECONNREFUSED Connection refused',
            110 => 'ETIMEDOUT +Connection timed out',
            109 => 'ETOOMANYREFS Too many references: cannot splice',
            108 => 'ESHUTDOWN Cannot send after transport endpoint shutdown',
            107 => 'ENOTCONN Transport endpoint is not connected',
            106 => 'EISCONN Transport endpoint is already connected',
            105 => 'ENOBUFS No buffer space available',
            104 => 'ECONNRESET Connection reset by peer',
            103 => 'ECONNABORTED Software caused connection abort',
            102 => 'ENETRESET Network dropped connection on reset',
            101 => 'ENETUNREACH Network is unreachable',
            100 => 'ENETDOWN Network is down',
            99 => 'EADDRNOTAVAIL Cannot assign requested address',
            98 => 'EADDRINUSE Address already in use',
            97 => 'EAFNOSUPPORT Address family not supported by protocol',
            96 => 'EPFNOSUPPORT Protocol family not supported',
            95 => 'EOPNOTSUPP Operation not supported',
            94 => 'ESOCKTNOSUPPORT Socket type not supported',
            93 => 'EPROTONOSUPPORT Protocol not supported',
            92 => 'ENOPROTOOPT Protocol not available',
            91 => 'EPROTOTYPE Protocol wrong type for socket',
            90 => 'EMSGSIZE +Message too long',
            89 => 'EDESTADDRREQ Destination address required',
            88 => 'ENOTSOCK Socket operation on non-socket',
            87 => 'EUSERS Too many users',
            86 => 'ESTRPIPE Streams pipe error',
            85 => 'ERESTART Interrupted system call should be restarted',
            84 => 'EILSEQ Invalid or incomplete multibyte or wide character',
            83 => 'ELIBEXEC Cannot exec a shared library directly',
            82 => 'ELIBMAX Attempting to link in too many shared libraries',
            81 => 'ELIBSCN .lib section in a.out corrupted',
            80 => 'ELIBBAD Accessing a corrupted shared library',
            79 => 'ELIBACC Can not access a needed shared library',
            78 => 'EREMCHG Remote address changed',
            77 => 'EBADFD File descriptor in bad state',
            76 => 'ENOTUNIQ Name not unique on network',
            75 => 'EOVERFLOW Value too large for defined data type',
            74 => 'EBADMSG +Bad message',
            73 => 'EDOTDOT RFS specific error',
            72 => 'EMULTIHOP Multihop attempted',
            71 => 'EPROTO Protocol error',
            70 => 'ECOMM Communication error on send',
            69 => 'ESRMNT Srmount error',
            68 => 'EADV Advertise error',
            67 => 'ENOLINK Link has been severed',
            66 => 'EREMOTE Object is remote',
            65 => 'ENOPKG Package not installed',
            64 => 'ENONET Machine is not on the network',
            63 => 'ENOSR Out of streams resources',
            62 => 'ETIME Timer expired',
            61 => 'ENODATA No data available',
            60 => 'ENOSTR Device not a stream',
            59 => 'EBFONT Bad font file format',
            57 => 'EBADSLT Invalid slot',
            56 => 'EBADRQC Invalid request code',
            55 => 'ENOANO No anode',
            54 => 'EXFULL Exchange full',
            53 => 'EBADR Invalid request descriptor',
            52 => 'EBADE Invalid exchange',
            51 => 'EL2HLT Level 2 halted',
            50 => 'ENOCSI No CSI structure available',
            49 => 'EUNATCH Protocol driver not attached',
            48 => 'ELNRNG Link number out of range',
            47 => 'EL3RST Level 3 reset',
            46 => 'EL3HLT Level 3 halted',
            45 => 'EL2NSYNC Level 2 not synchronized',
            44 => 'ECHRNG Channel number out of range',
            43 => 'EIDRM Identifier removed',
            42 => 'ENOMSG No message of desired type',
            40 => 'ELOOP Too many levels of symbolic links',
            39 => 'ENOTEMPTY +Directory not empty',
            38 => 'ENOSYS +Function not implemented',
            37 => 'ENOLCK +No locks available',
            36 => 'ENAMETOOLONG +File name too long',
            35 => 'EDEADLK +Resource deadlock avoided',
            34 => 'ERANGE +Numerical result out of range',
            33 => 'EDOM +Numerical argument out of domain',
            32 => 'EPIPE +Broken pipe',
            31 => 'EMLINK +Too many links',
            30 => 'EROFS +Read-only file system',
            29 => 'ESPIPE +Illegal seek',
            28 => 'ENOSPC +No space left on device',
            27 => 'EFBIG +File too large',
            26 => 'ETXTBSY Text file busy',
            25 => 'ENOTTY +Inappropriate ioctl for device',
            24 => 'EMFILE +Too many open files',
            23 => 'ENFILE +Too many open files in system',
            22 => 'EINVAL +Invalid argument',
            21 => 'EISDIR +Is a directory',
            20 => 'ENOTDIR +Not a directory',
            19 => 'ENODEV +No such device',
            18 => 'EXDEV +Invalid cross-device link',
            17 => 'EEXIST +File exists',
            16 => 'EBUSY +Device or resource busy',
            15 => 'ENOTBLK Block device required',
            14 => 'EFAULT +Bad address',
            13 => 'EACCES +Permission denied',
            12 => 'ENOMEM +Cannot allocate memory',
            11 => 'EAGAIN +Resource temporarily unavailable',
            10 => 'ECHILD +No child processes',
            9 => 'EBADF +Bad file descriptor',
            8 => 'ENOEXEC +Exec format error',
            7 => 'E2BIG +Argument list too long',
            6 => 'ENXIO +No such device or address',
            5 => 'EIO +Input/output error',
            4 => 'EINTR +Interrupted system call',
            3 => 'ESRCH +No such process',
            2 => 'ENOENT +No such file or directory',
            1 => 'EPERM +Operation not permitted',
        ];
        return isset($errors[$errCode]) ? $errors[$errCode] : '';
    }
}