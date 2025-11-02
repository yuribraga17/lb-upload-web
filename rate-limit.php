<?php
// rate-limit.php - Sistema de rate limiting

class RateLimiter {
    private $storageDir;
    private $maxRequests;
    private $timeWindow;
    
    public function __construct($maxRequests = 10, $timeWindow = 60) {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        $this->storageDir = __DIR__ . '/rate_limit_data/';
        
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    public function checkLimit($identifier) {
        $file = $this->storageDir . md5($identifier) . '.json';
        $now = time();
        
        // Carregar dados existentes
        $data = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true) ?? [];
        }
        
        // Limpar requisições antigas
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });
        
        // Verificar limite
        if (count($data) >= $this->maxRequests) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_in' => $this->timeWindow - ($now - min($data))
            ];
        }
        
        // Adicionar nova requisição
        $data[] = $now;
        file_put_contents($file, json_encode($data));
        
        return [
            'allowed' => true,
            'remaining' => $this->maxRequests - count($data),
            'reset_in' => $this->timeWindow
        ];
    }
    
    public function cleanup() {
        // Limpar arquivos antigos (executar periodicamente)
        $files = glob($this->storageDir . '*.json');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) > $this->timeWindow * 2) {
                unlink($file);
            }
        }
    }
}

// Usar no início do upload.php:

?>