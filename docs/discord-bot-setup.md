# Discord Bot Setup Guide

Este guia explica como configurar um bot do Discord que escuta mensagens nos canais e executa comandos automaticamente (VPS, SQL, Tinker).

## 📑 Índice

- [Visão Geral](#-visão-geral)
- [Configuração Rápida](#-configuração-rápida)
- [Passo a Passo Detalhado](#-passo-a-passo-detalhado)
- [Uso](#-uso)
- [Segurança](#-segurança)
- [Troubleshooting](#-troubleshooting)

## 📋 Visão Geral

O bot do Discord permite que você:
- Escreva comandos diretamente nos canais do Discord
- Execute comandos VPS, SQL e Tinker sem precisar usar o terminal
- Receba resultados diretamente no Discord
- Monitore execuções em tempo real

**Como funciona:**
1. Você escreve uma mensagem em um canal específico do Discord
2. O bot detecta a mensagem e identifica o tipo de comando baseado no canal
3. O bot executa o comando no servidor
4. O bot envia o resultado de volta para o Discord

## 🚀 Configuração Rápida

### 1. Criar Bot no Discord

1. Acesse [Discord Developer Portal](https://discord.com/developers/applications)
2. Clique em **"New Application"**
3. Dê um nome para sua aplicação (ex: "Vessel Management Bot")
4. Vá em **"Bot"** no menu lateral
5. Clique em **"Add Bot"**
6. Em **"Token"**, clique em **"Reset Token"** e copie o token
7. Em **"Privileged Gateway Intents"**, ative:
   - ✅ **MESSAGE CONTENT INTENT** (obrigatório para ler conteúdo das mensagens)
8. Salve as alterações

### 2. Adicionar Bot ao Servidor

1. No menu lateral, vá em **"OAuth2"** → **"URL Generator"**
2. Em **"Scopes"**, selecione:
   - ✅ `bot`
3. Em **"Bot Permissions"**, selecione:
   - ✅ `Send Messages`
   - ✅ `Read Message History`
   - ✅ `View Channels`
4. Copie a URL gerada e abra no navegador
5. Selecione o servidor e autorize o bot

### 3. Obter IDs dos Canais

1. No Discord, ative o **Modo Desenvolvedor**:
   - Configurações → Avançado → Modo Desenvolvedor
2. Clique com botão direito no canal desejado → **"Copiar ID"**
3. Repita para cada canal (VPS, SQL, Tinker)

### 4. Configurar .env

Adicione as seguintes variáveis ao seu `.env`:

```env
# ============================================
# Discord Bot Configuration
# ============================================

# Token do bot (obrigatório)
DISCORD_BOT_TOKEN=seu_token_do_bot_aqui

# ID do servidor (guild) - opcional
DISCORD_GUILD_ID=seu_guild_id_aqui

# IDs dos canais onde o bot vai escutar
DISCORD_VPS_CHANNEL_ID=id_do_canal_vps
DISCORD_SQL_CHANNEL_ID=id_do_canal_sql
DISCORD_TINKER_CHANNEL_ID=id_do_canal_tinker
```

**Nota:** Você pode configurar apenas os canais que deseja usar. Se um canal não estiver configurado, o bot simplesmente ignorará mensagens daquele canal.

## 📖 Passo a Passo Detalhado

### Passo 1: Criar Aplicação no Discord

1. Acesse https://discord.com/developers/applications
2. Faça login com sua conta Discord
3. Clique em **"New Application"**
4. Digite um nome (ex: "Vessel Management Bot")
5. Clique em **"Create"**

### Passo 2: Configurar o Bot

1. No menu lateral, clique em **"Bot"**
2. Clique em **"Add Bot"** e confirme
3. Em **"Token"**, clique em **"Reset Token"** e copie o token
   - ⚠️ **IMPORTANTE:** Guarde este token em local seguro. Não compartilhe publicamente.
4. Em **"Privileged Gateway Intents"**, ative:
   - ✅ **MESSAGE CONTENT INTENT** (obrigatório)
5. Clique em **"Save Changes"**

### Passo 3: Adicionar Bot ao Servidor

1. No menu lateral, clique em **"OAuth2"** → **"URL Generator"**
2. Em **"Scopes"**, marque:
   - ✅ `bot`
3. Em **"Bot Permissions"**, marque:
   - ✅ `Send Messages`
   - ✅ `Read Message History`
   - ✅ `View Channels`
4. Copie a URL gerada (aparece no final da página)
5. Cole a URL no navegador e abra
6. Selecione o servidor onde quer adicionar o bot
7. Clique em **"Authorize"**

### Passo 4: Obter IDs dos Canais

1. No Discord, vá em **Configurações do Usuário** → **Avançado**
2. Ative **"Modo Desenvolvedor"**
3. No servidor, clique com botão direito no canal onde quer executar comandos VPS
4. Clique em **"Copiar ID"**
5. Repita para os canais SQL e Tinker (se desejar usar)

### Passo 5: Configurar Variáveis de Ambiente

Adicione ao seu `.env`:

```env
# Token do bot (obrigatório)
DISCORD_BOT_TOKEN=MTIzNDU2Nzg5MDEyMzQ1Njc4OQ.abcdefghijklmnopqrstuvwxyz.1234567890abcdefghijklmnopqrstuvwxyz

# IDs dos canais (opcional - apenas os que você quer usar)
DISCORD_VPS_CHANNEL_ID=1234567890123456789
DISCORD_SQL_CHANNEL_ID=9876543210987654321
DISCORD_TINKER_CHANNEL_ID=1122334455667788990
```

### Passo 6: Limpar Cache e Iniciar Bot

```bash
php artisan config:clear
php artisan discord:bot
```

## 💻 Uso

### Executar Comandos

Simplesmente escreva o comando no canal correspondente:

**Canal VPS:**
```
ls -la
df -h
docker ps
```

**Canal SQL:**
```
SELECT * FROM users LIMIT 10
SELECT COUNT(*) FROM vessels
SHOW TABLES
```

**Canal Tinker:**
```
User::count()
DB::table('users')->count()
Log::info('Test')
```

### Respostas do Bot

O bot responderá com:
- ✅ **Status** (sucesso ou erro)
- ⏱️ **Tempo de execução**
- 📊 **Output/Resultado** do comando

**Exemplo de resposta:**
```
**Comando:** `ls -la`
**Status:** ✅ (Exit Code: 0)
**Tempo:** 0.05s

**Output:**
```
total 48
drwxr-xr-x  5 user user  4096 Nov 24 19:00 .
drwxr-xr-x  3 user user  4096 Nov 24 18:00 ..
-rw-r--r--  1 user user  1234 Nov 24 19:00 file.txt
```
```

## 🔒 Segurança

### Recomendações Importantes

1. **Token do Bot:**
   - ⚠️ **NUNCA** compartilhe o token publicamente
   - ⚠️ **NUNCA** commite o token no Git
   - ✅ Use variáveis de ambiente
   - ✅ Mantenha o token seguro

2. **Permissões:**
   - ✅ Dê apenas as permissões necessárias ao bot
   - ✅ Use canais privados para comandos sensíveis
   - ✅ Limite quem pode escrever nos canais

3. **Ambiente:**
   - ✅ O bot respeita as mesmas restrições de ambiente dos comandos
   - ✅ VPS/SQL/Tinker só funcionam em produção (por padrão)
   - ✅ Configure `*_ONLY_ON_PRODUCTION=false` apenas em desenvolvimento

4. **Comandos:**
   - ✅ SQL: Apenas SELECT por padrão (seguro)
   - ✅ VPS: Apenas comandos permitidos (whitelist)
   - ✅ Tinker: Código PHP completo (use com cuidado)

## 🐛 Troubleshooting

### Bot não conecta

**Problema:** Bot não consegue conectar ao Discord

**Soluções:**
1. Verifique se o token está correto no `.env`
2. Verifique se o bot foi adicionado ao servidor
3. Verifique se o **MESSAGE CONTENT INTENT** está ativado
4. Execute `php artisan config:clear`

### Bot não responde

**Problema:** Bot está online mas não responde aos comandos

**Soluções:**
1. Verifique se os IDs dos canais estão corretos
2. Verifique se o bot tem permissão para ler/escrever no canal
3. Verifique se o **MESSAGE CONTENT INTENT** está ativado
4. Verifique os logs: `storage/logs/laravel.log`

### Comandos não executam

**Problema:** Bot recebe mensagem mas não executa o comando

**Soluções:**
1. Verifique se está em ambiente de produção (ou configure `*_ONLY_ON_PRODUCTION=false`)
2. Verifique se os webhooks estão configurados
3. Verifique os logs para erros específicos

### Erro de permissão

**Problema:** Bot não consegue enviar mensagens

**Soluções:**
1. Verifique se o bot tem permissão "Send Messages" no canal
2. Verifique se o bot tem permissão "View Channels"
3. Verifique se o bot está no servidor correto

## 📋 Checklist de Configuração

- [ ] Bot criado no Discord Developer Portal
- [ ] Token do bot copiado e adicionado ao `.env`
- [ ] MESSAGE CONTENT INTENT ativado
- [ ] Bot adicionado ao servidor Discord
- [ ] IDs dos canais copiados e adicionados ao `.env`
- [ ] Permissões do bot configuradas corretamente
- [ ] Variáveis de ambiente configuradas
- [ ] Cache limpo (`php artisan config:clear`)
- [ ] Bot iniciado (`php artisan discord:bot`)
- [ ] Testado com comando simples

## 🚀 Executar Bot em Produção

Para executar o bot em produção como daemon:

```bash
# Usando supervisor (recomendado)
# Adicione ao /etc/supervisor/conf.d/discord-bot.conf:

[program:discord-bot]
command=php /caminho/para/artisan discord:bot
directory=/caminho/para/projeto
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/discord-bot.log
```

Ou usando systemd:

```bash
# Crie /etc/systemd/system/discord-bot.service:

[Unit]
Description=Discord Bot
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/caminho/para/projeto
ExecStart=/usr/bin/php artisan discord:bot
Restart=always

[Install]
WantedBy=multi-user.target

# Então:
sudo systemctl enable discord-bot
sudo systemctl start discord-bot
```

## 📚 Referências

- [Discord Developer Portal](https://discord.com/developers/applications)
- [Discord Gateway Documentation](https://discord.com/developers/docs/topics/gateway)
- [Discord API Documentation](https://discord.com/developers/docs/intro)

---

**Precisa de ajuda?** Verifique os logs em `storage/logs/laravel.log` para mais detalhes sobre erros.

