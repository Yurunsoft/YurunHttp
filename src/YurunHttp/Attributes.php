<?php

namespace Yurun\Util\YurunHttp;

/**
 * 所有属性的常量定义.
 *
 * PRIVATE_ 开头的为内部属性，请勿使用
 */
abstract class Attributes
{
    /**
     * 客户端参数.
     */
    public const OPTIONS = 'options';

    /**
     * 全局默认 UserAgent.
     */
    public const USER_AGENT = 'userAgent';

    /**
     * 重试次数.
     */
    public const RETRY = 'retry';

    /**
     * 重试回调.
     */
    public const RETRY_CALLBACK = 'retry_callback';

    /**
     * 下载文件保存路径.
     */
    public const SAVE_FILE_PATH = 'saveFilePath';

    /**
     * 保存文件的模型.
     */
    public const SAVE_FILE_MODE = 'saveFileMode';

    /**
     * 允许重定向.
     */
    public const FOLLOW_LOCATION = 'followLocation';

    /**
     * 最大允许重定向次数.
     */
    public const MAX_REDIRECTS = 'maxRedirects';

    /**
     * 是否验证 CA 证书.
     */
    public const IS_VERIFY_CA = 'isVerifyCA';

    /**
     * CA 证书.
     */
    public const CA_CERT = 'caCert';

    /**
     * SSL 证书类型.
     */
    public const CERT_TYPE = 'certType';

    /**
     * SSL 证书路径.
     */
    public const CERT_PATH = 'certPath';

    /**
     * SSL 证书密码
     */
    public const CERT_PASSWORD = 'certPassword';

    /**
     * SSL 密钥类型.
     */
    public const KEY_TYPE = 'keyType';

    /**
     * SSL 密钥路径.
     */
    public const KEY_PATH = 'keyPath';

    /**
     * SSL 密钥密码
     */
    public const KEY_PASSWORD = 'keyPassword';

    /**
     * 使用代理.
     */
    public const USE_PROXY = 'useProxy';

    /**
     * 代理类型.
     */
    public const PROXY_TYPE = 'proxy.type';

    /**
     * 代理服务器地址
     */
    public const PROXY_SERVER = 'proxy.server';

    /**
     * 代理服务器端口.
     */
    public const PROXY_PORT = 'proxy.port';

    /**
     * 代理用户名.
     */
    public const PROXY_USERNAME = 'proxy.username';

    /**
     * 代理密码
     */
    public const PROXY_PASSWORD = 'proxy.password';

    /**
     * 代理的 Basic 认证配置.
     */
    public const PROXY_AUTH = 'proxy.auth';

    /**
     * 认证用户名.
     */
    public const USERNAME = 'username';

    /**
     * 认证密码
     */
    public const PASSWORD = 'password';

    /**
     * 超时时间.
     */
    public const TIMEOUT = 'timeout';

    /**
     * 连接超时.
     */
    public const CONNECT_TIMEOUT = 'connectTimeout';

    /**
     * 保持长连接.
     */
    public const KEEP_ALIVE = 'keep_alive';

    /**
     * 下载限速
     */
    public const DOWNLOAD_SPEED = 'downloadSpeed';

    /**
     * 上传限速
     */
    public const UPLOAD_SPEED = 'uploadSpeed';

    /**
     * 使用自定义重定向操作.
     */
    public const CUSTOM_LOCATION = 'customLocation';

    /**
     * http2 请求不调用 recv().
     */
    public const HTTP2_NOT_RECV = 'http2_not_recv';

    /**
     * 启用 Http2 pipeline.
     */
    public const HTTP2_PIPELINE = 'http2_pipeline';

    /**
     * 启用连接池.
     */
    public const CONNECTION_POOL = 'connection_pool';

    /**
     * 重试计数.
     */
    public const PRIVATE_RETRY_COUNT = '__retryCount';

    /**
     * 重定向计数.
     */
    public const PRIVATE_REDIRECT_COUNT = '__redirectCount';

    /**
     * WebSocket 请求
     */
    public const PRIVATE_WEBSOCKET = '__websocket';

    /**
     * Http2 流ID.
     */
    public const PRIVATE_HTTP2_STREAM_ID = '__http2StreamId';

    /**
     * 是否为 Http2.
     */
    public const PRIVATE_IS_HTTP2 = '__isHttp2';

    /**
     * 是否为 WebSocket.
     */
    public const PRIVATE_IS_WEBSOCKET = '__isWebSocket';

    /**
     * 连接对象
     */
    public const PRIVATE_CONNECTION = '__connection';

    /**
     * 连接池的键.
     */
    public const PRIVATE_POOL_KEY = '__poolKey';
}
