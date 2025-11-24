# Discord Slash Commands - Guia Simples

Execute comandos diretamente no Discord usando **slash commands** (mais simples que bot WebSocket).

## 🚀 Como Funciona

1. Você digita `/vps ls -la` no Discord
2. Discord chama seu servidor
3. Comando é executado
4. Resultado aparece no Discord

**Muito mais simples que bot WebSocket!**

## ⚙️ Configuração Rápida

### 1. Criar Bot no Discord

1. Acesse [Discord Developer Portal](https://discord.com/developers/applications)
2. Crie uma nova aplicação
3. Vá em **"Bot"** → Copie o **Token**
4. Vá em **"General"** → Copie o **Application ID**

### 2. Configurar .env

```env
# Token do bot (obrigatório)
DISCORD_BOT_TOKEN=seu_token_aqui

# Application ID (obrigatório)
DISCORD_APPLICATION_ID=seu_application_id_aqui

# URL pública do seu servidor (obrigatório)
APP_URL=https://seu-dominio.com
```

### 3. Registrar Comandos

```bash
# Para comandos globais (aparecem em todos os servidores - pode demorar 1 hora)
php artisan discord:register-commands

# Para comandos do servidor (aparecem imediatamente)
php artisan discord:register-commands --guild-id=SEU_GUILD_ID
```

### 4. Adicionar Bot ao Servidor

1. No Discord Developer Portal → **OAuth2** → **URL Generator**
2. Selecione: `bot` e `applications.commands`
3. Selecione permissões: `Send Messages`, `Use Slash Commands`
4. Copie a URL e abra no navegador
5. Selecione o servidor e autorize

## 💻 Como Usar

### No Discord, digite:

**VPS:**
```
/vps command:ls -la
/vps command:df -h
/vps command:docker ps
```

**SQL:**
```
/sql query:SELECT * FROM users LIMIT 10
/sql query:SELECT COUNT(*) FROM vessels
```

**Tinker:**
```
/tinker code:User::count()
/tinker code:DB::table('users')->count()
```

O resultado aparece imediatamente no Discord!

## 🔒 Segurança

### Verificação de Assinatura

O endpoint verifica a assinatura do Discord. Para produção, configure:

```env
DISCORD_PUBLIC_KEY=seu_public_key_aqui
```

**Onde encontrar:** Discord Developer Portal → **General** → **Public Key**

### Restrições de Ambiente

Os comandos respeitam as mesmas restrições:
- `VPS_ONLY_ON_PRODUCTION=true` → Só funciona em produção
- `SQL_DISCORD_ONLY_ON_PRODUCTION=true` → Só funciona em produção
- `TINKER_ONLY_ON_PRODUCTION=true` → Só funciona em produção

## 🚀 Deploy

### 1. Configure as variáveis no .env de produção:

```env
DISCORD_BOT_TOKEN=seu_token
DISCORD_APPLICATION_ID=seu_app_id
APP_URL=https://seu-dominio.com
DISCORD_PUBLIC_KEY=seu_public_key
```

### 2. Registre os comandos:

```bash
php artisan discord:register-commands --guild-id=SEU_GUILD_ID
```

### 3. Pronto!

Agora você pode usar `/vps`, `/sql`, `/tinker` no Discord!

## 📝 Vantagens sobre Bot WebSocket

✅ **Mais simples** - Não precisa manter conexão WebSocket aberta  
✅ **Mais confiável** - HTTP é mais estável  
✅ **Mais fácil de debugar** - Logs HTTP normais  
✅ **Menos recursos** - Não precisa processo rodando 24/7  
✅ **Escalável** - Funciona com múltiplos servidores  

## 🐛 Troubleshooting

### Comandos não aparecem

1. Verifique se registrou: `php artisan discord:register-commands`
2. Comandos globais podem demorar até 1 hora
3. Use `--guild-id` para aparecer imediatamente

### Erro "Unauthorized"

1. Verifique se `DISCORD_PUBLIC_KEY` está configurado
2. Em desenvolvimento, a verificação é desabilitada automaticamente

### Comandos não funcionam

1. Verifique se `APP_URL` está correto e acessível publicamente
2. Verifique se o endpoint `/discord/interactions` está acessível
3. Verifique os logs: `storage/logs/laravel.log`

## 📚 Comparação

| Recurso | Slash Commands | Bot WebSocket |
|---------|---------------|---------------|
| Complexidade | ⭐ Simples | ⭐⭐⭐ Complexo |
| Configuração | ⭐⭐ Fácil | ⭐⭐⭐ Difícil |
| Recursos | ⭐⭐ Médio | ⭐⭐⭐ Alto |
| Confiabilidade | ⭐⭐⭐ Alta | ⭐⭐ Média |
| Escalabilidade | ⭐⭐⭐ Alta | ⭐⭐ Média |

**Recomendação:** Use Slash Commands (mais simples e confiável)!

---

**Precisa de ajuda?** Verifique os logs em `storage/logs/laravel.log`

