<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servidor de Mídias - LB-Phone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 50px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 10px 25px rgba(0,0,0,0.05);
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        
        .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        
        .icon i {
            font-size: 32px;
            color: white;
        }
        
        h1 {
            color: #1a202c;
            margin-bottom: 8px;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        
        p {
            color: #718096;
            margin-bottom: 32px;
            line-height: 1.6;
            font-size: 15px;
        }
        
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0fdf4;
            color: #166534;
            padding: 8px 16px;
            border-radius: 100px;
            font-weight: 500;
            margin-bottom: 32px;
            font-size: 14px;
            border: 1px solid #bbf7d0;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.95); }
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn i {
            font-size: 16px;
        }
        
        .info {
            margin-top: 40px;
            padding: 24px;
            background: #f8fafc;
            border-radius: 12px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        
        .info h3 {
            color: #1a202c;
            margin-bottom: 16px;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info h3 i {
            color: #667eea;
            font-size: 16px;
        }
        
        .info ul {
            list-style: none;
            padding: 0;
        }
        
        .info li {
            color: #64748b;
            padding: 8px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info li i {
            color: #22c55e;
            font-size: 12px;
            width: 16px;
        }
        
        .footer {
            margin-top: 32px;
            color: #94a3b8;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-cloud-upload-alt"></i>
        </div>
        
        <h1>Servidor de Mídias</h1>
        <p>Sistema de upload de imagens, vídeos e áudios para LB-Phone</p>
        
        <div class="status">
            <div class="status-dot"></div>
            Servidor Online
        </div>

        <a href="admin.php" class="btn">
            <i class="fas fa-shield-alt"></i>
            Acessar Painel Admin
        </a>

        <div class="info">
            <h3>
                <i class="fas fa-info-circle"></i>
                Informações do Sistema
            </h3>
            <ul>
                <li>
                    <i class="fas fa-check"></i>
                    Upload via API protegida
                </li>
                <li>
                    <i class="fas fa-check"></i>
                    Suporte para imagens, vídeos e áudios
                </li>
                <li>
                    <i class="fas fa-check"></i>
                    Sistema de segurança avançado
                </li>
                <li>
                    <i class="fas fa-check"></i>
                    Rate limiting ativo
                </li>
                <li>
                    <i class="fas fa-check"></i>
                    Painel de administração completo
                </li>
            </ul>
        </div>

        <div class="footer">
            © <script>document.write(new Date().getFullYear())</script> - Sistema de Mídias LB-Phone
        </div>
    </div>
</body>
</html>