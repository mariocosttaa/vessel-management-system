# Discord Bot - Guia Rápido de Deploy

## ✅ Checklist Antes do Deploy

### 1. Configurar Bot no Discord
- [ ] Criar bot no [Discord Developer Portal](https://discord.com/developers/applications)
- [ ] Copiar o **Token do Bot**
- [ ] Ativar **MESSAGE CONTENT INTENT** (obrigatório)
- [ ] Adicionar bot ao servidor Discord

### 2. Obter IDs dos Canais
- [ ] Ativar Modo Desenvolvedor no Discord
- [ ] Copiar ID do canal VPS (se usar)
- [ ] Copiar ID do canal SQL (se usar)
- [ ] Copiar ID do canal Tinker (se usar)

### 3. Configurar Variáveis de Ambiente

Adicione ao seu `.env` em produção:

```env
# Token do bot (OBRIGATÓRIO)
DISCORD_BOT_TOKEN=seu_token_do_bot_aqui

# IDs dos canais (OPCIONAL - apenas os que você quer usar)
DISCORD_VPS_CHANNEL_ID=1234567890123456789
DISCORD_SQL_CHANNEL_ID=9876543210987654321
DISCORD_TINKER_CHANNEL_ID=1122334455667788990
```

## 🚀 Deploy

### Deploy Automático (Recomendado)

O bot está configurado para iniciar **automaticamente** apenas se o token estiver configurado.

**Apenas faça:**
1. ✅ Configure `DISCORD_BOT_TOKEN` no `.env` (ou no painel do Coolify)
2. ✅ Configure IDs dos canais (opcional)
3. ✅ Faça commit e push
4. ✅ Deploy no Coolify

**Pronto!** O bot iniciará automaticamente junto com os outros serviços.

**Nota:** Se o token **não** estiver configurado, o bot simplesmente não será iniciado (sem erros).

### Opção 2: Deploy Manual (Se necessário)

Se por algum motivo o bot não iniciar automaticamente, você pode iniciar manualmente:

```bash
# No servidor/container
php artisan discord:bot
```

## 🔍 Verificar se Está Funcionando

### 1. Verificar Logs

```bash
# Logs do bot
tail -f storage/logs/discord-bot.log

# Ou no Coolify, vá em Logs do container
```

### 2. Testar no Discord

Escreva um comando simples no canal configurado:

**Canal VPS:**
```
ls
```

**Canal SQL:**
```
SELECT 1
```

**Canal Tinker:**
```
echo 'test'
```

O bot deve responder com o resultado.

### 3. Verificar Status do Supervisor

Se estiver usando supervisor diretamente:

```bash
supervisorctl status
```

Você deve ver `discord-bot` como `RUNNING`.

## ⚠️ Importante

1. **Token do Bot:**
   - ⚠️ **NUNCA** commite o token no Git
   - ✅ Use variáveis de ambiente
   - ✅ Configure no painel do Coolify (se usar)

2. **MESSAGE CONTENT INTENT:**
   - ✅ **DEVE** estar ativado no Discord Developer Portal
   - Sem isso, o bot não consegue ler o conteúdo das mensagens

3. **Permissões:**
   - ✅ Bot precisa de permissão para ler e escrever nos canais
   - ✅ Verifique as permissões do bot no servidor Discord

4. **Ambiente:**
   - ✅ O bot respeita `*_ONLY_ON_PRODUCTION=true`
   - ✅ Em produção, os comandos só funcionam se `APP_ENV=production`

## 🐛 Problemas Comuns

### Bot não inicia

**Solução:**
1. Verifique se `DISCORD_BOT_TOKEN` está configurado
2. Verifique os logs: `storage/logs/discord-bot.log`
3. Verifique se o supervisor está rodando: `supervisorctl status`

### Bot não responde

**Solução:**
1. Verifique se o **MESSAGE CONTENT INTENT** está ativado
2. Verifique se os IDs dos canais estão corretos
3. Verifique se o bot tem permissão no canal
4. Verifique os logs para erros

### Bot desconecta frequentemente

**Solução:**
1. Verifique a conexão de rede
2. Verifique os logs para erros de conexão
3. O bot tem reconexão automática, mas pode levar alguns segundos

## 📝 Resumo

**Para fazer deploy:**

1. ✅ Configure `DISCORD_BOT_TOKEN` no `.env`
2. ✅ Configure IDs dos canais (opcional)
3. ✅ Faça commit e push
4. ✅ Deploy no Coolify

**Pronto!** O bot iniciará automaticamente e ficará rodando continuamente.

---

**Documentação completa:** Veja `docs/discord-bot-setup.md` para mais detalhes.

