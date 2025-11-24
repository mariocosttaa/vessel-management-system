# Discord Setup - Guia Passo a Passo Simples

Guia completo e simples para configurar comandos no Discord.

## 🔒 Segurança

**Sim, é seguro!** Porque:

1. ✅ **HTTPS**: Seu servidor usa HTTPS (criptografado)
2. ✅ **Assinatura Discord**: Discord assina todas as requisições com Ed25519
3. ✅ **Verificação**: Seu servidor verifica a assinatura antes de executar
4. ✅ **Sem exposição**: O endpoint só aceita requisições do Discord

**É mais seguro que muitos outros métodos!**

## 📋 Passo a Passo Completo

### Passo 1: Criar Bot no Discord (5 minutos)

1. **Acesse:** https://discord.com/developers/applications
2. **Faça login** com sua conta Discord
3. **Clique em:** "New Application" (canto superior direito)
4. **Digite um nome:** Ex: "Vessel Management Bot"
5. **Clique em:** "Create"

### Passo 2: Obter Informações do Bot (2 minutos)

Na página da sua aplicação:

1. **Vá em "General Information"** (menu lateral esquerdo)
   - Copie o **Application ID** (número grande)
   - Copie o **Public Key** (chave longa)

2. **Vá em "Bot"** (menu lateral esquerdo)
   - Clique em **"Add Bot"** e confirme
   - Clique em **"Reset Token"** e copie o **Token**
   - ⚠️ **IMPORTANTE:** Guarde este token! Você não verá ele novamente.

3. **Em "Privileged Gateway Intents":**
   - ✅ Marque **"MESSAGE CONTENT INTENT"** (se aparecer)
   - Clique em **"Save Changes"**

### Passo 3: Adicionar Bot ao Servidor (2 minutos)

1. **Vá em "OAuth2"** → **"URL Generator"** (menu lateral)
2. **Em "Scopes"**, marque:**
   - ✅ `bot`
   - ✅ `applications.commands`
3. **Em "Bot Permissions"**, marque:**
   - ✅ `Send Messages`
   - ✅ `Use Slash Commands`
4. **Copie a URL** que aparece no final da página
5. **Cole a URL no navegador** e abra
6. **Selecione seu servidor** Discord
7. **Clique em "Authorize"**

### Passo 4: Obter ID do Servidor (1 minuto)

1. **No Discord**, vá em **Configurações do Usuário** → **Avançado**
2. **Ative "Modo Desenvolvedor"**
3. **No seu servidor**, clique com botão direito no nome do servidor
4. **Clique em "Copiar ID"**
5. **Guarde este ID** (você vai precisar)

### Passo 5: Configurar .env (2 minutos)

Adicione ao seu `.env`:

```env
# Token do bot (obrigatório)
DISCORD_BOT_TOKEN=cole_o_token_aqui

# Application ID (obrigatório)
DISCORD_APPLICATION_ID=cole_o_application_id_aqui

# Public Key (obrigatório para segurança)
DISCORD_PUBLIC_KEY=cole_o_public_key_aqui

# URL pública do seu servidor (obrigatório)
APP_URL=https://seu-dominio.com
```

### Passo 6: Registrar Comandos (1 minuto)

Execute no terminal:

```bash
php artisan discord:register-commands --guild-id=COLE_O_ID_DO_SERVIDOR_AQUI
```

**Exemplo:**
```bash
php artisan discord:register-commands --guild-id=1234567890123456789
```

Você verá:
```
Registering: /vps
✅ Registered: /vps
Registering: /sql
✅ Registered: /sql
Registering: /tinker
✅ Registered: /tinker

✅ Commands registered successfully!
```

### Passo 7: Testar! (1 minuto)

1. **Abra o Discord**
2. **Vá para qualquer canal** do seu servidor
3. **Digite:** `/vps` e pressione Tab
4. **Você verá:** `command:` aparecer
5. **Digite:** `ls -la` e pressione Enter
6. **Resultado aparece!** 🎉

## ✅ Pronto!

Agora você pode usar:

- `/vps command:ls -la` - Executa comandos do terminal
- `/sql query:SELECT * FROM users` - Executa queries SQL
- `/tinker code:User::count()` - Executa código PHP

## 🔒 Segurança Explicada

### Como funciona a segurança:

1. **Discord assina cada requisição** com Ed25519 (criptografia moderna)
2. **Seu servidor verifica a assinatura** antes de executar qualquer comando
3. **Apenas requisições do Discord** são aceitas
4. **HTTPS criptografa** toda a comunicação

### É seguro porque:

- ✅ Ninguém pode falsificar requisições do Discord
- ✅ A assinatura é verificada em cada requisição
- ✅ HTTPS protege contra interceptação
- ✅ O endpoint só aceita requisições válidas do Discord

## 🐛 Problemas Comuns

### "Comandos não aparecem"

**Solução:**
- Comandos globais podem demorar até 1 hora
- Use `--guild-id` para aparecer imediatamente
- Verifique se o bot foi adicionado ao servidor

### "Unauthorized"

**Solução:**
- Verifique se `DISCORD_PUBLIC_KEY` está correto
- Verifique se o endpoint está acessível publicamente
- Em desenvolvimento local, a verificação é desabilitada

### "Comando não funciona"

**Solução:**
- Verifique se `APP_URL` está correto e acessível
- Verifique se o endpoint `/discord/interactions` está funcionando
- Verifique os logs: `storage/logs/laravel.log`

## 📝 Resumo Rápido

1. ✅ Criar bot no Discord Developer Portal
2. ✅ Copiar Token, Application ID e Public Key
3. ✅ Adicionar bot ao servidor
4. ✅ Configurar .env
5. ✅ Registrar comandos: `php artisan discord:register-commands --guild-id=...`
6. ✅ Usar no Discord: `/vps command:ls -la`

**Tempo total: ~15 minutos**

---

**Precisa de ajuda?** Verifique os logs em `storage/logs/laravel.log`

