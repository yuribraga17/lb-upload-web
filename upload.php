<?php
// upload.php - Versão corrigida com detecção correta de tipo de arquivo
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'rate-limit.php';

$rateLimiter = new RateLimiter(10, 60);
$clientIp = getClientIp();
$limit = $rateLimiter->checkLimit($clientIp);

if (!$limit['allowed']) {
    logSecurityEvent('RATE_LIMIT_EXCEEDED', ['ip' => $clientIp]);
    jsonResponse([
        'success' => false,
        'error' => 'Muitas requisições. Tente novamente em ' . $limit['reset_in'] . ' segundos.'
    ], 429);
}

// Headers de segurança
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, X-Server-Key, Content-Type');

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validação do método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logSecurityEvent('INVALID_METHOD', ['method' => $_SERVER['REQUEST_METHOD']]);
    jsonResponse(['success' => false, 'error' => 'Método não permitido.'], 405);
}

// Validação de IP
if (!checkIpWhitelist()) {
    logSecurityEvent('IP_BLOCKED', ['ip' => getClientIp()]);
    jsonResponse(['success' => false, 'error' => 'Acesso negado.'], 403);
}

// Capturar headers
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === 'HTTP_') {
            $header = str_replace('_', '-', substr($key, 5));
            $headers[$header] = $value;
        }
    }
}

// Função auxiliar para pegar header (case-insensitive)
function getHeader($headers, $name) {
    foreach ($headers as $key => $value) {
        if (strtolower($key) === strtolower($name)) {
            return $value;
        }
    }
    return '';
}

// Validação da Server Key
$serverKey = getHeader($headers, 'X-Server-Key');

if (!validateServerKey($serverKey)) {
    logSecurityEvent('INVALID_SERVER_KEY', ['provided' => substr($serverKey, 0, 10)]);
    jsonResponse(['success' => false, 'error' => 'Server Key inválida.'], 401);
}

// Validação da API Key
if (!validateApiKey($headers)) {
    logSecurityEvent('INVALID_API_KEY', ['ip' => $clientIp]);
    jsonResponse(['success' => false, 'error' => 'API Key inválida.'], 401);
}

// Validação do arquivo ANTES de detectar o tipo
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Nenhum arquivo foi enviado.';
    
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'Arquivo muito grande.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = 'Upload incompleto.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'Nenhum arquivo enviado.';
                break;
        }
    }
    
    logSecurityEvent('UPLOAD_ERROR', ['error' => $errorMsg]);
    jsonResponse(['success' => false, 'error' => $errorMsg], 400);
}

$file = $_FILES['file'];

// Proteção contra path traversal
$originalFilename = basename($file['name']);
$safeName = sanitizeFilename($originalFilename);

if ($safeName !== $originalFilename) {
    logSecurityEvent('PATH_TRAVERSAL_ATTEMPT', [
        'original' => $originalFilename,
        'sanitized' => $safeName
    ]);
    jsonResponse(['success' => false, 'error' => 'Nome de arquivo inválido.'], 400);
}

// ==========================================
// DETECÇÃO DO TIPO DE ARQUIVO (CORRIGIDA)
// ==========================================

// Pegar extensão do arquivo
$filename = strtolower($file['name']);
$extension = pathinfo($filename, PATHINFO_EXTENSION);

// Detectar tipo pela extensão primeiro (mais confiável)
$type = null;

if (in_array($extension, ALLOWED_IMAGES)) {
    $type = 'image';
} elseif (in_array($extension, ALLOWED_VIDEOS)) {
    $type = 'video';
} elseif (in_array($extension, ALLOWED_AUDIOS)) {
    $type = 'audio';
}

// Se não detectou pela extensão, tenta pelo MIME type
if ($type === null && isset($file['type'])) {
    $mimeType = $file['type'];
    if (strpos($mimeType, 'image/') === 0) {
        $type = 'image';
    } elseif (strpos($mimeType, 'video/') === 0) {
        $type = 'video';
    } elseif (strpos($mimeType, 'audio/') === 0) {
        $type = 'audio';
    }
}

// Se ainda não detectou, verifica se foi enviado via POST
if ($type === null && isset($_POST['type']) && !empty($_POST['type'])) {
    $typeFromPost = strtolower(trim($_POST['type']));
    if (in_array($typeFromPost, ['image', 'video', 'audio'])) {
        $type = $typeFromPost;
    }
}

// Se AINDA não detectou, retorna erro
if ($type === null) {
    logSecurityEvent('UNABLE_TO_DETECT_TYPE', [
        'filename' => $originalFilename,
        'extension' => $extension,
        'mime' => $file['type'] ?? 'unknown'
    ]);
    jsonResponse(['success' => false, 'error' => 'Não foi possível detectar o tipo do arquivo.'], 400);
}

// Log da detecção para debug
logSecurityEvent('TYPE_DETECTED', [
    'filename' => $originalFilename,
    'extension' => $extension,
    'detected_type' => $type,
    'mime' => $file['type'] ?? 'unknown'
]);

// Validação do tamanho
$maxSize = getMaxSize($type);

if ($file['size'] > $maxSize) {
    logSecurityEvent('FILE_TOO_LARGE', ['size' => $file['size'], 'max' => $maxSize]);
    jsonResponse([
        'success' => false,
        'error' => 'Arquivo muito grande. Máximo: ' . ($maxSize / 1024 / 1024) . ' MB.'
    ], 400);
}

// Validação da extensão (agora mais rigorosa)
$allowedExtensions = getAllowedExtensions($type);

if (!in_array($extension, $allowedExtensions)) {
    logSecurityEvent('INVALID_EXTENSION', [
        'extension' => $extension,
        'type' => $type,
        'allowed' => $allowedExtensions
    ]);
    jsonResponse(['success' => false, 'error' => "Extensão .$extension não permitida para tipo $type."], 400);
}

// Validação do MIME type
if (!validateFileContent($file['tmp_name'], $type)) {
    logSecurityEvent('INVALID_FILE_CONTENT', ['filename' => $originalFilename, 'type' => $type]);
    @unlink($file['tmp_name']);
    jsonResponse(['success' => false, 'error' => 'Arquivo inválido ou corrompido.'], 400);
}

// Verificação anti-malware (apenas para tipos perigosos)
$dangerousExtensions = ['svg', 'html', 'htm', 'xml'];
$needsMalwareCheck = in_array($extension, $dangerousExtensions);

if ($needsMalwareCheck) {
    $fileContent = file_get_contents($file['tmp_name'], false, null, 0, 8192);
    
    $maliciousPatterns = [
        '/<\?php/i',
        '/<?=/i',
        '/<script/i',
        '/eval\s*\(/i',
        '/base64_decode/i',
        '/exec\s*\(/i',
        '/system\s*\(/i',
        '/shell_exec/i'
    ];
    
    foreach ($maliciousPatterns as $pattern) {
        if (preg_match($pattern, $fileContent)) {
            logSecurityEvent('MALWARE_DETECTED', ['filename' => $originalFilename, 'extension' => $extension]);
            @unlink($file['tmp_name']);
            jsonResponse(['success' => false, 'error' => 'Arquivo bloqueado por segurança.'], 403);
        }
    }
}

// Salvar arquivo
$uploadPath = getUploadPath($type);

if (!file_exists($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}

$newFilename = generateUniqueFilename($extension);
$destinationPath = $uploadPath . $newFilename;

if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
    logSecurityEvent('SAVE_ERROR', ['path' => $destinationPath]);
    jsonResponse(['success' => false, 'error' => 'Erro ao salvar o arquivo.'], 500);
}

chmod($destinationPath, 0644);

// Gerar URL pública (CORRIGIDA)
$publicUrl = BASE_URL . $type . 's/' . $newFilename;

logSecurityEvent('UPLOAD_SUCCESS', [
    'filename' => $newFilename,
    'original_name' => $originalFilename,
    'type' => $type,
    'extension' => $extension,
    'size' => $file['size'],
    'url' => $publicUrl
]);

// Resposta de sucesso
jsonResponse([
    'success' => true,
    'url' => $publicUrl,
    'type' => $type,
    'filename' => $newFilename,
    'original_name' => $originalFilename,
    'extension' => $extension,
    'size' => $file['size']
], 200);
?>