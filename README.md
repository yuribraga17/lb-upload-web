# 📱 Servidor de Mídias - LB-Phone

<div align="center">

![Status](https://img.shields.io/badge/status-ativo-success)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![License](https://img.shields.io/badge/license-MIT-green)

**Sistema seguro de upload e gerenciamento de mídias para LB-Phone**

[Características](#-características) • [Instalação](#-instalação) • [Uso](#-uso) • [API](#-api) • [Segurança](#-segurança)

</div>

---

## 📋 Sobre

Sistema completo de upload e gerenciamento de mídias (imagens, vídeos e áudios) com painel administrativo, desenvolvido especificamente para integração com LB-Phone em servidores FiveM/QBCore.

## ✨ Características

### 🔒 Segurança
- Autenticação via Server Key e API Key
- Rate limiting (10 requisições/minuto por IP)
- Validação avançada de arquivos (MIME type, extensão, conteúdo)
- Proteção contra malware e path traversal
- Sistema de logs de segurança
- Whitelist de IPs (opcional)

### 📤 Upload
- **Imagens**: JPG, PNG, GIF, WebP (até 100MB)
- **Vídeos**: MP4, WebM, MOV, AVI (até 200MB)
- **Áudios**: MP3, WAV, OGG, AAC, WebM (até 200MB)
- Detecção automática de tipo de arquivo
- Geração de nomes únicos e seguros

### 🎨 Painel Admin
- Interface moderna e responsiva
- Visualização em grid de todas as mídias
- Filtros por tipo (imagens/vídeos/áudios)
- Preview de vídeos ao passar o mouse
- Estatísticas de uso e espaço
- Copiar URL com um clique
- Sistema de exclusão de arquivos
- Autenticação protegida por senha

## 🚀 Instalação

### Requisitos
- PHP 7.4 ou superior
- Apache/Nginx
- Extensões PHP: `fileinfo`, `json`, `mbstring`

### Passos

1. **Clone ou faça download do repositório**
```bash
git clone https://github.com/yuribraga17/lb-upload-web.git
cd lb-phone-media-server
```

2. **Configure as permissões**
```bash
chmod 755 uploads/
chmod 755 uploads/images/
chmod 755 uploads/videos/
chmod 755 uploads/audios/
chmod 755 logs/
```

3. **Configure o arquivo `config.php`**
```php
// Altere estas chaves para valores únicos e seguros!
define('SERVER_KEY', 'sua_server_key_aqui');
define('API_KEY', 'sua_api_key_aqui');
define('SECURITY_SALT', 'seu_salt_aqui');

// Configure a URL base do seu servidor
define('BASE_URL', 'https://seu-dominio.com/uploads/');
```

4. **Configure a senha do painel admin em `admin.php`**
```php
define('ADMIN_PASSWORD', 'sua_senha_segura');
```

5. **Configure seu servidor web**

#### Apache (.htaccess já incluído)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

## 📖 Uso

### Acessando o Painel Admin
1. Acesse `https://seu-dominio.com/admin.php`
2. Digite a senha configurada
3. Gerencie suas mídias

### Página Inicial
Acesse `https://seu-dominio.com/` para ver o status do servidor

## 🔌 API

### Endpoint de Upload

**URL:** `POST /upload.php`

**Headers Obrigatórios:**
```
X-Server-Key: sua_server_key
Authorization: Bearer sua_api_key
Content-Type: multipart/form-data
```

**Parâmetros:**
- `file` (arquivo) - Arquivo a ser enviado
- `type` (opcional) - Tipo: `image`, `video` ou `audio` (detectado automaticamente se omitido)

### Exemplo de Requisição (JavaScript)

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('type', 'image'); // opcional

const response = await fetch('https://seu-dominio.com/upload.php', {
    method: 'POST',
    headers: {
        'X-Server-Key': 'sua_server_key',
        'Authorization': 'Bearer sua_api_key'
    },
    body: formData
});

const result = await response.json();
console.log(result);
```

### Exemplo de Requisição (cURL)

```bash
curl -X POST https://seu-dominio.com/upload.php \
  -H "X-Server-Key: sua_server_key" \
  -H "Authorization: Bearer sua_api_key" \
  -F "file=@imagem.jpg" \
  -F "type=image"
```

### Resposta de Sucesso

```json
{
    "success": true,
    "url": "https://seu-dominio.com/uploads/images/abc123_1234567890.jpg",
    "type": "image",
    "filename": "abc123_1234567890.jpg",
    "size": 245760
}
```

### Resposta de Erro

```json
{
    "success": false,
    "error": "Arquivo muito grande. Máximo: 100 MB."
}
```

## 🛡️ Segurança

### Recursos de Segurança Implementados

- ✅ **Autenticação Dupla**: Server Key + API Key
- ✅ **Rate Limiting**: 10 requisições por minuto por IP
- ✅ **Validação de Arquivos**: MIME type, extensão e conteúdo
- ✅ **Anti-Malware**: Detecção de padrões maliciosos
- ✅ **Path Traversal Protection**: Sanitização de nomes
- ✅ **Logs de Segurança**: Registro de todas as tentativas
- ✅ **Headers de Segurança**: XSS, Clickjacking, MIME Sniffing
- ✅ **CORS Configurável**: Controle de origens permitidas

### Gerando Chaves Seguras

Use este comando para gerar chaves aleatórias:

```bash
# Linux/Mac
openssl rand -hex 32

# PHP
php -r "echo bin2hex(random_bytes(32));"
```

### Configurando Whitelist de IPs (Opcional)

Em `config.php`:
```php
define('ALLOWED_IPS', [
    '192.168.1.100',
    '10.0.0.50'
]);
```

## 📁 Estrutura de Arquivos

```
├── index.php           # Página inicial
├── upload.php          # Endpoint de upload
├── admin.php          # Painel administrativo
├── config.php         # Configurações
├── rate-limit.php     # Sistema de rate limiting
├── uploads/
│   ├── images/       # Imagens enviadas
│   ├── videos/       # Vídeos enviados
│   └── audios/       # Áudios enviados
├── logs/
│   └── security.log  # Logs de segurança
└── rate_limit_data/  # Dados de rate limiting
```

## 🔧 Manutenção

### Limpeza de Arquivos Antigos

Você pode criar um cron job para limpar arquivos antigos:

```bash
# Limpar arquivos com mais de 30 dias
0 2 * * * find /caminho/para/uploads -type f -mtime +30 -delete
```

### Monitoramento de Logs

Visualizar logs de segurança:
```bash
tail -f logs/security.log
```

### Backup

Recomendado fazer backup regular da pasta `uploads/`:
```bash
tar -czf backup-$(date +%Y%m%d).tar.gz uploads/
```

## ⚠️ Avisos Importantes

1. **Altere TODAS as chaves de segurança** em `config.php`
2. **Altere a senha do admin** em `admin.php`
3. Configure **SSL/HTTPS** no servidor (obrigatório para produção)
4. Mantenha o **PHP atualizado** para correções de segurança
5. Configure **backups regulares** das mídias
6. Monitore o **espaço em disco** regularmente

## 📊 Limites Padrão

| Tipo | Tamanho Máximo | Extensões Permitidas |
|------|----------------|---------------------|
| Imagem | 100 MB | jpg, jpeg, png, gif, webp |
| Vídeo | 200 MB | mp4, webm, mov, avi |
| Áudio | 200 MB | mp3, wav, ogg, aac, webm |

Limites podem ser ajustados em `config.php`

## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para:

1. Fazer fork do projeto
2. Criar uma branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abrir um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 💬 Suporte

- 🐛 **Bugs**: Abra uma [issue](https://github.com/yuribraga17/lb-upload-web/issues)
- 💡 **Sugestões**: Abra uma [discussion](https://github.com/yuribraga17/lb-upload-web/discussions)

## 📞 Contato

- Discord: yuribragaa17
- Email: yuribragasoares@gmail.com

---

<div align="center">

**Desenvolvido com ❤️ para a comunidade FiveM**

⭐ Se este projeto te ajudou, considere dar uma estrela!

</div>
