<?php
// admin.php - Painel de administração para gerenciar mídias
session_start();

// Senha de acesso ao painel (MUDE ISSO!)
define('ADMIN_PASSWORD', 'Senha@aquuii'); // ⚠️ ALTERE ESTA SENHA!

// Verificar login
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin_logged'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Senha incorreta!';
        }
    }
    
    // Mostrar formulário de login
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Painel Admin</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 400px;
                width: 100%;
            }
            h1 { color: #333; margin-bottom: 30px; text-align: center; }
            input[type="password"] {
                width: 100%;
                padding: 15px;
                border: 2px solid #ddd;
                border-radius: 8px;
                font-size: 16px;
                margin-bottom: 20px;
            }
            button {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔒 Painel Admin</h1>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Digite a senha" required autofocus>
                <button type="submit">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Função para obter todos os arquivos
function getMediaFiles($path, $type) {
    $files = [];
    if (!is_dir($path)) {
        return $files;
    }
    
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $path . $item;
        if (is_file($fullPath)) {
            $files[] = [
                'name' => $item,
                'path' => $fullPath,
                'size' => filesize($fullPath),
                'date' => filemtime($fullPath),
                'type' => $type,
                'url' => str_replace(__DIR__ . '/uploads/', BASE_URL, $fullPath)
            ];
        }
    }
    
    return $files;
}

// Ação de deletar
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $fileToDelete = basename($_GET['file']);
    $type = $_GET['type'] ?? '';
    
    $uploadPath = '';
    if ($type === 'image') $uploadPath = UPLOAD_PATH_IMAGES;
    elseif ($type === 'video') $uploadPath = UPLOAD_PATH_VIDEOS;
    elseif ($type === 'audio') $uploadPath = UPLOAD_PATH_AUDIOS;
    
    $filePath = $uploadPath . $fileToDelete;
    
    if (file_exists($filePath)) {
        unlink($filePath);
        $message = 'Arquivo deletado com sucesso!';
        $messageType = 'success';
    } else {
        $message = 'Arquivo não encontrado!';
        $messageType = 'error';
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

require_once 'config.php';

// Coletar todos os arquivos
$images = getMediaFiles(UPLOAD_PATH_IMAGES, 'image');
$videos = getMediaFiles(UPLOAD_PATH_VIDEOS, 'video');
$audios = getMediaFiles(UPLOAD_PATH_AUDIOS, 'audio');

$allFiles = array_merge($images, $videos, $audios);
usort($allFiles, function($a, $b) {
    return $b['date'] - $a['date'];
});

$totalFiles = count($allFiles);
$totalSize = array_sum(array_column($allFiles, 'size'));

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração - Mídias</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 24px; }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .value {
            color: #333;
            font-size: 28px;
            font-weight: bold;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-tabs button {
            padding: 10px 20px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .filter-tabs button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .media-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .media-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .media-preview {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-preview .icon {
            font-size: 48px;
            color: #999;
        }
        .media-info {
            padding: 15px;
        }
        .media-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .media-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .media-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view {
            background: #667eea;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-copy {
            background: #28a745;
            color: white;
        }
        .type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .type-image { background: #e3f2fd; color: #1976d2; }
        .type-video { background: #f3e5f5; color: #7b1fa2; }
        .type-audio { background: #e8f5e9; color: #388e3c; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📁 Gerenciador de Mídias</h1>
            <a href="?logout=1" class="logout-btn">🚪 Sair</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($message)): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <h3>Total de Arquivos</h3>
                <div class="value"><?= $totalFiles ?></div>
            </div>
            <div class="stat-card">
                <h3>Espaço Usado</h3>
                <div class="value"><?= formatBytes($totalSize) ?></div>
            </div>
            <div class="stat-card">
                <h3>Imagens</h3>
                <div class="value"><?= count($images) ?></div>
            </div>
            <div class="stat-card">
                <h3>Vídeos</h3>
                <div class="value"><?= count($videos) ?></div>
            </div>
            <div class="stat-card">
                <h3>Áudios</h3>
                <div class="value"><?= count($audios) ?></div>
            </div>
        </div>

        <div class="filter-tabs">
            <button class="active" onclick="filterMedia('all')">Todos (<?= $totalFiles ?>)</button>
            <button onclick="filterMedia('image')">Imagens (<?= count($images) ?>)</button>
            <button onclick="filterMedia('video')">Vídeos (<?= count($videos) ?>)</button>
            <button onclick="filterMedia('audio')">Áudios (<?= count($audios) ?>)</button>
        </div>

        <?php if (empty($allFiles)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h2>Nenhuma mídia encontrada</h2>
                <p>Faça upload de arquivos para começar</p>
            </div>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($allFiles as $file): ?>
                    <div class="media-card" data-type="<?= $file['type'] ?>">
                        <div class="media-preview">
                            <?php if ($file['type'] === 'image'): ?>
                                <img src="<?= htmlspecialchars($file['url']) ?>" alt="<?= htmlspecialchars($file['name']) ?>">
                            <?php elseif ($file['type'] === 'video'): ?>
                                <video src="<?= htmlspecialchars($file['url']) ?>" muted></video>
                            <?php else: ?>
                                <div class="icon">🎵</div>
                            <?php endif; ?>
                        </div>
                        <div class="media-info">
                            <div class="media-name" title="<?= htmlspecialchars($file['name']) ?>">
                                <?= htmlspecialchars($file['name']) ?>
                            </div>
                            <div class="media-meta">
                                <span class="type-badge type-<?= $file['type'] ?>">
                                    <?= $file['type'] ?>
                                </span>
                                <span><?= formatBytes($file['size']) ?></span>
                            </div>
                            <div class="media-meta">
                                <span><?= date('d/m/Y H:i', $file['date']) ?></span>
                            </div>
                            <div class="media-actions">
                                <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank" class="btn btn-view">👁️ Ver</a>
                                <button onclick="copyUrl('<?= htmlspecialchars($file['url']) ?>')" class="btn btn-copy">📋 URL</button>
                                <a href="?action=delete&type=<?= $file['type'] ?>&file=<?= urlencode($file['name']) ?>" 
                                   onclick="return confirm('Tem certeza que deseja deletar este arquivo?')" 
                                   class="btn btn-delete">🗑️</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterMedia(type) {
            const cards = document.querySelectorAll('.media-card');
            const buttons = document.querySelectorAll('.filter-tabs button');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                if (type === 'all' || card.dataset.type === type) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('✅ URL copiada para a área de transferência!');
            }).catch(err => {
                prompt('Copie a URL:', url);
            });
        }

        // Preview de vídeo ao passar o mouse
        document.querySelectorAll('video').forEach(video => {
            video.parentElement.addEventListener('mouseenter', () => {
                video.play();
            });
            video.parentElement.addEventListener('mouseleave', () => {
                video.pause();
                video.currentTime = 0;
            });
        });
    </script>
</body>
</html>