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
 * Store for background server info.
 * Each item: ['proc' => resource|null, 'pid' => int, 'phpFile' => string, 'name' => string]
 */
$GLOBALS['_server_handles'] = [];

// --- Register cleanup EARLY so it runs on fatal errors too ---
register_shutdown_function(function () {
    echo \PHP_EOL, '=== Cleaning up servers ===', \PHP_EOL;

    // Stop WSS server (Swoole)
    if (isset($GLOBALS['_wss_pid']) && $GLOBALS['_wss_pid'])
    {
        try {
            echo 'Stopping WSS server...', \PHP_EOL;
            if (!IS_WIN)
            {
                `kill -15 {$GLOBALS['_wss_pid']} 2>/dev/null`;
            }
            echo 'WSS server stopped!', \PHP_EOL;
        }
        catch (\Throwable $e)
        {
            echo 'WSS server stop failed: ', $e->getMessage(), \PHP_EOL;
        }
    }

    // Stop Http2 server
    if (SWOOLE_ON && !IS_WIN)
    {
        try {
            $cmd = __DIR__ . '/server/Http2/stop-server.sh';
            echo 'Stopping Http2 server...', \PHP_EOL;
            echo `{$cmd}`, \PHP_EOL;
            echo 'Http2 server stopped!', \PHP_EOL;
        }
        catch (\Throwable $e)
        {
            echo 'Http2 server stop failed: ', $e->getMessage(), \PHP_EOL;
        }
    }

    // Kill Workerman servers
    foreach ($GLOBALS['_server_handles'] as $item)
    {
        try {
            $name = $item['name'];
            $pid = $item['pid'];
            $proc = $item['proc'];
            $phpFile = $item['phpFile'];

            echo 'Stopping ', $name, '...', \PHP_EOL;

            if (IS_WIN)
            {
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
            }
            else
            {
                // On Linux/Mac, use Workerman's built-in stop command
                if ($phpFile)
                {
                    `php {$phpFile} stop 2>/dev/null`;
                }
            }

            echo $name, ' stopped!', \PHP_EOL;
        }
        catch (\Throwable $e)
        {
            echo $name, ' stop failed: ', $e->getMessage(), \PHP_EOL;
        }
    }

    // Final safety net: kill any remaining php.exe on our test ports
    if (IS_WIN)
    {
        try {
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

    echo '=== Cleanup complete ===', \PHP_EOL;
});

/**
 * Start a PHP server process in background.
 *
 * On Windows: uses proc_open, records PID for taskkill /T cleanup later.
 * On Linux/Mac: calls start-server.sh shell script (Workerman daemon mode).
 *
 * @param string $phpFile Absolute path to the PHP file
 * @param string $name    Human-readable name for logging
 *
 * @return void
 */
function startServerProcess($phpFile, $name)
{
    if (IS_WIN)
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
    else
    {
        $cmd = dirname($phpFile) . '/start-server.sh';
        echo `{$cmd}`, \PHP_EOL;
        $GLOBALS['_server_handles'][] = [
            'proc'    => null,
            'pid'     => 0,
            'phpFile' => $phpFile,
            'name'    => $name,
        ];
    }
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

// --- WSS Server (Swoole-based, only on non-Windows with Swoole) ---
if (SWOOLE_ON && !IS_WIN)
{
    echo 'Starting WSS server (Swoole)...', \PHP_EOL;
    $cmd = 'nohup php ' . escapeshellarg(__DIR__ . '/server/WebSocket/wss-server.php') . ' > /dev/null 2>&1 & echo $!';
    $wssPid = trim(`{$cmd}`);
    if ($wssPid)
    {
        $GLOBALS['_wss_pid'] = $wssPid;
    }
    $serverStarted = false;
    for ($i = 0; $i < 10; ++$i)
    {
        $context = stream_context_create(['http' => ['timeout' => 1]]);
        @file_get_contents('http://127.0.0.1:8902/', false, $context);
        if (isset($http_response_header[0]) && str_contains($http_response_header[0], '400'))
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

// --- Http2 Server (Swoole-only, not available on Windows) ---
if (SWOOLE_ON && !IS_WIN)
{
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
}
elseif (!SWOOLE_ON)
{
    echo 'Http2 server skipped (Swoole not available)', \PHP_EOL;
}
else
{
    echo 'Http2 server skipped (not supported on Windows)', \PHP_EOL;
}
