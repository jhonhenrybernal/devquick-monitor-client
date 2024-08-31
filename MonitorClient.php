<?php
namespace DevQuick\MonitorClient;

use Firebase\JWT\JWT;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use GuzzleHttp\Client;

class MonitorClient
{
    private $serverUrl;
    private $jwtSecret;
    private $accessKey;
    private $http;
    private $rateLimit = 10; // Máximo 10 logs por minuto
    private $logCount = 0;
    private $webSocketConnection;

    public function __construct()
    {
        // Configuración básica
        $this->serverUrl = 'https://centralized-log-server.com/logs';
        $this->jwtSecret = 'your_jwt_secret_key';
        $this->accessKey = 'your_access_key';
        
        // Configuración del cliente HTTP para el rate limiting
        $this->http = new Client();

        // Implementar WebSocket
        $loop = Factory::create();
        $client = new \Ratchet\Client\Factory($loop);
        $client('ws://centralized-log-server.com:8080', [], ['Authorization' => 'Bearer ' . $this->generateJWT()])
            ->then(function(WebSocket $conn) {
                $this->webSocketConnection = $conn;
                $conn->on('message', function($msg) {
                    // Handle incoming messages from the server
                    echo "Received: {$msg}\n";
                });
            }, function (\Exception $e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });

        $loop->run();

        // Leer logs existentes y enviar
        $this->processExistingLogs();
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
            $this->http->post($this->serverUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->generateJWT(),
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'level' => 'warning',
                    'message' => 'Rate limit exceeded'
                ]
            ]);
        }
    }

    private function sendLog($level, $message)
    {
        if ($this->webSocketConnection) {
            $this->webSocketConnection->send(json_encode([
                'level' => $level,
                'message' => $message
            ]));
        } else {
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

    private function processExistingLogs()
    {
        $logFiles = glob(storage_path('logs/*.log'));
        foreach ($logFiles as $file) {
            $logs = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($logs as $log) {
                // Supongamos que el formato del log es: [fecha] nivel mensaje
                $parts = explode(' ', $log, 3);
                if (count($parts) >= 3) {
                    $level = $parts[1];
                    $message = $parts[2];
                    $this->log($level, $message);
                }
            }
        }
    }
}

