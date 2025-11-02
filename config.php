<?php
// config.php - Configuração do servidor de upload

// ============================================
// CONFIGURAÇÕES DE SEGURANÇA
// ============================================

define('SERVER_KEY', 'CHAVE AQUI');
define('API_KEY', 'CHAVE AQUI');
define('SECURITY_SALT', 'CHAVE AQUI');

define('ALLOWED_IPS', []);
define('REQUEST_TIMEOUT', 300);

define('BASE_URL', 'https://midia.site.com.br/uploads/');

define('UPLOAD_PATH_IMAGES', __DIR__ . '/uploads/images/');
define('UPLOAD_PATH_VIDEOS', __DIR__ . '/uploads/videos/');
define('UPLOAD_PATH_AUDIOS', __DIR__ . '/uploads/audios/');

define('LOG_PATH', __DIR__ . '/logs/security.log');

define('MAX_SIZE_IMAGE', 80 * 1024 * 1024);
define('MAX_SIZE_VIDEO', 80 * 1024 * 1024);
define('MAX_SIZE_AUDIO', 50 * 1024 * 1024);

define('ALLOWED_IMAGES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEOS', ['mp4', 'webm', 'mov', 'avi']);
define('ALLOWED_AUDIOS', ['mp3', 'wav', 'ogg', 'aac', 'webm']);

// ============================================
// FUNÇÕES DE SEGURANÇA
// ============================================

function validateServerKey($serverKey) {
    return hash_equals(SERVER_KEY, $serverKey);
}

function validateApiKey($headers) {
    $authHeader = '';
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    } elseif (function_exists('apache_request_headers')) {
        $apacheHeaders = apache_request_headers();
        if (isset($apacheHeaders['Authorization'])) {
            $authHeader = $apacheHeaders['Authorization'];
        }
    }
    
    $token = str_replace('Bearer ', '', $authHeader);
    return hash_equals(API_KEY, $token);
}

function validateRequestSignature($data, $signature, $timestamp) {
    if (abs(time() - $timestamp) > REQUEST_TIMEOUT) {
        return false;
    }
    
    $expectedSignature = hash_hmac('sha256', $data . $timestamp, SECURITY_SALT);
    return hash_equals($expectedSignature, $signature);
}

function checkIpWhitelist() {
    if (empty(ALLOWED_IPS)) {
        return true;
    }
    
    $clientIp = getClientIp();
    return in_array($clientIp, ALLOWED_IPS);
}

function getClientIp() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (isset($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return 'unknown';
}

function logSecurityEvent($event, $details = []) {
    $logDir = dirname(LOG_PATH);
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIp();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logEntry = sprintf(
        "[%s] IP: %s | Event: %s | Details: %s | UserAgent: %s\n",
        $timestamp,
        $ip,
        $event,
        json_encode($details),
        $userAgent
    );
    
    @file_put_contents(LOG_PATH, $logEntry, FILE_APPEND);
}

function generateUploadToken($fileData, $timestamp) {
    return hash_hmac('sha256', $fileData . $timestamp, SERVER_KEY);
}

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

function getUploadPath($type) {
    switch ($type) {
        case 'image':
            return UPLOAD_PATH_IMAGES;
        case 'video':
            return UPLOAD_PATH_VIDEOS;
        case 'audio':
            return UPLOAD_PATH_AUDIOS;
        default:
            return null;
    }
}

function getMaxSize($type) {
    switch ($type) {
        case 'image':
            return MAX_SIZE_IMAGE;
        case 'video':
            return MAX_SIZE_VIDEO;
        case 'audio':
            return MAX_SIZE_AUDIO;
        default:
            return 0;
    }
}

function getAllowedExtensions($type) {
    switch ($type) {
        case 'image':
            return ALLOWED_IMAGES;
        case 'video':
            return ALLOWED_VIDEOS;
        case 'audio':
            return ALLOWED_AUDIOS;
        default:
            return [];
    }
}

function generateUniqueFilename($extension) {
    return bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

function validateFileContent($filePath, $type) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    $validMimeTypes = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'],
        'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac', 'audio/webm']
    ];
    
    if (!isset($validMimeTypes[$type]) || !in_array($mimeType, $validMimeTypes[$type])) {
        return false;
    }
    
    if ($type === 'image') {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }
    }
    
    return true;
}
?>