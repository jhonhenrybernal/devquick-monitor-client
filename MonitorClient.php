<?php
namespace DevQuick\MonitorClient;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Firebase\JWT\JWT;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;

class MonitorClient
{
    private $logger;
    private $serverUrl;
    private $jwtSecret;
    private $accessKey;

    private $http;
    private $rateLimit = 10; // Máximo 10 logs por minuto
    private $logCount = 0;

    public function __construct()
    {
        // Configuración básica
        $this->serverUrl = 'https://centralized-log-server.com/logs';
        $this->jwtSecret = 'your_jwt_secret_key';
        $this->accessKey = 'your_access_key';
        $this->http = new Client();
        
        // Configuración del logger
        $this->logger = new Logger('monitor');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/monitor.log'), Logger::DEBUG));
        
        // Implementar WebSocket
        $loop = Factory::create();
        $client = new \Ratchet\Client\Factory($loop);
        $client('ws://centralized-log-server.com:8080', [], ['Authorization' => 'Bearer ' . $this->generateJWT()])
            ->then(function(WebSocket $conn) {
                $conn->on('message', function($msg) use ($conn) {
                    // Handle incoming messages from the server
                    echo "Received: {$msg}\n";
                });

                $this->logger->pushHandler(new WebSocketHandler($conn));
            }, function (\Exception $e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });

        $loop->run();
    }

    private function generateJWT()
    {
        $payload = [
            'iss' => 'devquick-monitor-client',
            'aud' => 'centralized-log-server',
            'iat' => time(),
            'exp' => time() + 3600,
            'access_key' => $this->accessKey
        ];
        
        return JWT::encode($payload, $this->jwtSecret);
    }

    public function log($level, $message)
    {
        if ($this->logCount < $this->rateLimit) {
            $this->logCount++;
            $this->sendLog($level, $message);
        } else {
            $this->logger->warning('Rate limit exceeded');
        }
    }

    private function sendLog($level, $message)
    {
        $this->http->post($this->serverUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->generateJWT(),
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'level' => $level,
                'message' => $message
            ]
        ]);
    }
}
