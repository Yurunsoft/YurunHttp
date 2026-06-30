<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Unset system proxy env vars so curl connects directly to 127.0.0.1
// instead of going through a local proxy that would forward based on Host header
foreach (['HTTP_PROXY', 'HTTPS_PROXY', 'http_proxy', 'https_proxy', 'ALL_PROXY', 'all_proxy', 'NO_PROXY', 'no_proxy'] as $key)
{
    putenv($key);
}

define('SWOOLE_ON', extension_loaded('swoole'));
define('IS_WIN', 'WIN' === strtoupper(substr(\PHP_OS, 0, 3)));

/**
 * @param string $name
 * @param mixed  $default
 *
 * @return mixed
 */
function testEnv($name, $default = null)
{
    $result = getenv($name);
    if (false === $result)
    {
        return $default;
    }

    return $result;
}

/**
 * Store for background server info (Windows only).
 * Each item: ['proc' => resource|null, 'pid' => int, 'phpFile' => string, 'name' => string]
 */
$GLOBALS['_server_handles'] = [];

// --- Register cleanup EARLY so it runs on fatal errors too ---
register_shutdown_function(function () {
    echo \PHP_EOL, '=== Cleaning up servers ===', \PHP_EOL;

    if (IS_WIN)
    {
        // Kill Workerman servers started via proc_open
        foreach ($GLOBALS['_server_handles'] as $item)
        {
            try
            {
                $name = $item['name'];
                $pid = $item['pid'];
                $proc = $item['proc'];

                echo 'Stopping ', $name, '...', \PHP_EOL;

                // Kill the ENTIRE process tree (Workerman spawns child workers)
                if ($pid > 0)
                {
                    `taskkill /F /T /PID {$pid} 2>nul`;
                }
                // Also close the proc_open handle
                if (is_resource($proc))
                {
                    proc_close($proc);
                }

                echo $name, ' stopped!', \PHP_EOL;
            }
            catch (\Throwable $e)
            {
                echo $item['name'], ' stop failed: ', $e->getMessage(), \PHP_EOL;
            }
        }

        // Final safety net: kill any remaining php.exe on our test ports
        try
        {
            foreach ([8898, 8900] as $port)
            {
                $output = `netstat -ano 2>nul | findstr ":{$port} " | findstr "LISTENING"`;
                if ($output)
                {
                    $lines = explode("\n", trim($output));
                    foreach ($lines as $line)
                    {
                        $line = trim($line);
                        if (preg_match('/\s+(\d+)$/', $line, $m))
                        {
                            `taskkill /F /T /PID {$m[1]} 2>nul`;
                        }
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            // ignore
        }
    }
    else
    {
        // Linux: stop servers via stop-server.sh (previous version's method)
        $cmd = __DIR__ . '/server/Http/stop-server.sh';
        echo 'Stoping http server...', \PHP_EOL;
        echo `{$cmd}`, \PHP_EOL;
        echo 'Http Server stoped!', \PHP_EOL;

        if (SWOOLE_ON)
        {
            $cmd = __DIR__ . '/server/WebSocket/stop-server.sh';
            echo 'Stoping WebSocket server...', \PHP_EOL;
            echo `{$cmd}`, \PHP_EOL;
            echo 'WebSocket Server stoped!', \PHP_EOL;

            $cmd = __DIR__ . '/server/Http2/stop-server.sh';
            echo 'Stoping Http2 server...', \PHP_EOL;
            echo `{$cmd}`, \PHP_EOL;
            echo 'Http2 Server stoped!', \PHP_EOL;

            $pidFile = __DIR__ . '/server/WebSocket/wss-server.pid';
            if (is_file($pidFile))
            {
                $pid = (int) file_get_contents($pidFile);
                if ($pid > 0)
                {
                    echo 'Stoping WSS server (PID: ' . $pid . ')...', \PHP_EOL;
                    if (function_exists('posix_kill'))
                    {
                        posix_kill($pid, \SIGTERM);
                    }
                    else
                    {
                        `kill -15 {$pid} 2>/dev/null`;
                    }
                }
                @unlink($pidFile);
                echo 'WSS Server stoped!', \PHP_EOL;
            }
        }
    }

    echo '=== Cleanup complete ===', \PHP_EOL;
});

if (IS_WIN)
{
    // ===== Windows: start servers via proc_open (current logic) =====

    /**
     * Start a PHP server process in background.
     *
     * Uses proc_open, records PID for taskkill /T cleanup later.
     *
     * @param string $phpFile Absolute path to the PHP file
     * @param string $name    Human-readable name for logging
     *
     * @return void
     */
    function startServerProcess($phpFile, $name)
    {
        $logFile = dirname($phpFile) . '/log.log';
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['file', $logFile, 'w'],
            2 => ['file', $logFile, 'a'],
        ];
        $process = proc_open('php "' . $phpFile . '"', $descriptorspec, $pipes);
        if (false === $process)
        {
            throw new \RuntimeException('Failed to start ' . $name . ': ' . $phpFile);
        }
        // Close stdin
        fclose($pipes[0]);
        // Get the PID for tree-kill later
        $status = proc_get_status($process);
        $GLOBALS['_server_handles'][] = [
            'proc'    => $process,
            'pid'     => $status['pid'],
            'phpFile' => $phpFile,
            'name'    => $name,
        ];
    }

    /**
     * Wait for HTTP server to be ready.
     */
    function waitForServer($port, $checkBody = 'YurunHttp')
    {
        $url = 'http://127.0.0.1:' . $port . '/';
        for ($i = 0; $i < 30; ++$i)
        {
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            if ($checkBody === @file_get_contents($url, false, $context))
            {
                return;
            }
            sleep(1);
        }
        throw new \RuntimeException('Server start failed on port ' . $port);
    }

    /**
     * Wait for WebSocket server to be ready.
     */
    function waitForWebSocketServer($port)
    {
        for ($i = 0; $i < 30; ++$i)
        {
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            @file_get_contents('http://127.0.0.1:' . $port . '/', false, $context);
            if (isset($http_response_header[0]) && str_contains($http_response_header[0], '400'))
            {
                return;
            }
            sleep(1);
        }
        throw new \RuntimeException('WebSocket server start failed on port ' . $port);
    }

    // --- Http Server (Workerman) ---
    echo 'Starting Http server...', \PHP_EOL;
    startServerProcess(__DIR__ . '/server/Http/server.php', 'Http server');
    waitForServer(8898);
    echo 'Http server started!', \PHP_EOL;

    // --- WebSocket Server (Workerman) ---
    echo 'Starting WebSocket server...', \PHP_EOL;
    startServerProcess(__DIR__ . '/server/WebSocket/server.php', 'WebSocket server');
    waitForWebSocketServer(8900);
    echo 'WebSocket server started!', \PHP_EOL;
}
else
{
    // ===== Linux: start servers via start-server.sh (previous version's method) =====

    // Http Server
    $cmd = __DIR__ . '/server/Http/start-server.sh';
    echo 'Starting Http server...', \PHP_EOL;
    echo `{$cmd}`, \PHP_EOL;
    $serverStarted = false;
    for ($i = 0; $i < 10; ++$i)
    {
        $context = stream_context_create(['http' => ['timeout' => 1]]);
        if ('YurunHttp' === @file_get_contents(testEnv('HTTP_SERVER_HOST', 'http://127.0.0.1:8898/'), false, $context))
        {
            $serverStarted = true;
            break;
        }
        sleep(1);
    }
    if ($serverStarted)
    {
        echo 'Http server started!', \PHP_EOL;
    }
    else
    {
        throw new \RuntimeException('Http server start failed');
    }

    if (SWOOLE_ON)
    {
        // WebSocket Server
        $cmd = __DIR__ . '/server/WebSocket/start-server.sh';
        echo 'Starting WebSocket server...', \PHP_EOL;
        echo `{$cmd}`, \PHP_EOL;
        $serverStarted = false;
        for ($i = 0; $i < 10; ++$i)
        {
            @file_get_contents(str_replace('ws://', 'http://', testEnv('WS_SERVER_HOST', 'ws://127.0.0.1:8900/')));
            if (isset($http_response_header[0]) && false !== stripos($http_response_header[0], '400 Bad Request'))
            {
                $serverStarted = true;
                break;
            }
            sleep(1);
        }
        if ($serverStarted)
        {
            echo 'WebSocekt server started!', \PHP_EOL;
        }
        else
        {
            throw new \RuntimeException('WebSocekt server start failed');
        }

        // Http2 Server
        $cmd = __DIR__ . '/server/Http2/start-server.sh';
        echo 'Starting Http2 server...', \PHP_EOL;
        echo `{$cmd}`, \PHP_EOL;
        $serverStarted = false;
        for ($i = 0; $i < 10; ++$i)
        {
            @file_get_contents(testEnv('HTTP2_SERVER_HOST', 'http://127.0.0.1:8901/'));
            if (isset($http_response_header[0]) && 'HTTP/1.1 200 OK' === $http_response_header[0])
            {
                $serverStarted = true;
                break;
            }
            sleep(1);
        }
        if ($serverStarted)
        {
            echo 'Http2 server started!', \PHP_EOL;
        }
        else
        {
            throw new \RuntimeException('Http2 server start failed');
        }

        // WSS Server (Swoole SSL WebSocket)
        $wssPidFile = __DIR__ . '/server/WebSocket/wss-server.pid';
        $cmd = 'nohup /usr/bin/env php "' . __DIR__ . '/server/WebSocket/wss-server.php" > "' . __DIR__ . '/server/WebSocket/wss-server.log" 2>&1 & echo $! > "' . $wssPidFile . '"';
        echo 'Starting WSS server...', \PHP_EOL;
        echo `{$cmd}`, \PHP_EOL;
        $serverStarted = false;
        for ($i = 0; $i < 10; ++$i)
        {
            $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            @file_get_contents('https://127.0.0.1:8902/', false, $context);
            if (isset($http_response_header[0]) && false !== stripos($http_response_header[0], '400'))
            {
                $serverStarted = true;
                break;
            }
            sleep(1);
        }
        if ($serverStarted)
        {
            echo 'WSS server started!', \PHP_EOL;
        }
        else
        {
            throw new \RuntimeException('WSS server start failed');
        }
    }
}
