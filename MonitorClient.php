<?php
namespace DevQuick\MonitorClient;

use Firebase\JWT\JWT;
use WebSocket\Client as WebSocketClient;
use GuzzleHttp\Client;

class MonitorClient
{
    private $serverUrl;
    private $jwtSecret;
    private $http;
    private $rateLimit = 10; // Máximo 10 logs por minuto
    private $logCount = 0;
    private $webSocketClient;
    private $logDirectory;

    public function __construct()
    {
        // Configuración básica
        $this->serverUrl = 'http://localhost/logs';
        $this->jwtSecret = getenv('ACCESS_KEY_DQMONITOR'); // Obtener la clave del entorno
        $this->logDirectory = __DIR__ . '/../../storage/logs'; // Ajusta la ruta a tu directorio de logs
        
        // Configuración del cliente HTTP para el rate limiting
        $this->http = new Client();

        // Implementar WebSocket
        $this->webSocketClient = new WebSocketClient('ws://localhost:8081', [
            'Authorization' => 'Bearer ' . $this->generateJWT()
        ]);

        // Leer logs existentes y enviar
        $this->processExistingLogs();
    }

    private function generateJWT()
    {
        $payload = [
            'iss' => 'devquick-monitor-client',
            'aud' => 'centralized-log-server',
            'iat' => time(),
            'exp' => time() + 3600
        ];
        
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
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
        if ($this->webSocketClient) {
            $this->webSocketClient->send(json_encode([
                'level' => $level,
                'message' => $message
            ]));
        } else {
            $this->http->post($this->serverUrl, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'level' => $level,
                    'message' => $message,
                    'jwt' => $this->generateJWT()
                ]
            ]);
        }
    }

    private function processExistingLogs()
    {
        $logFiles = glob($this->logDirectory . '/*.log');
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
