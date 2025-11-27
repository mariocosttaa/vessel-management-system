# Bot não responde? Verifique isso!

## ⚠️ Problema Mais Comum: MESSAGE_CONTENT_INTENT

**O bot conecta mas não lê mensagens?** Provavelmente o **MESSAGE_CONTENT_INTENT** não está ativado!

### Como Ativar (2 minutos):

1. **Acesse:** https://discord.com/developers/applications
2. **Selecione sua aplicação**
3. **Vá em "Bot"** (menu lateral)
4. **Role até "Privileged Gateway Intents"**
5. **Ative:** ✅ **MESSAGE CONTENT INTENT**
6. **Clique em "Save Changes"**
7. **Reinicie o bot:** Pare e inicie novamente `php artisan discord:bot`

### Verificar se está ativado:

- ✅ Você deve ver um checkbox marcado em "MESSAGE CONTENT INTENT"
- ✅ Deve estar na seção "Privileged Gateway Intents"

## 🔍 Outros Problemas Comuns

### 1. Bot não está no servidor
- Adicione o bot usando OAuth2 → URL Generator
- Selecione `bot` e `applications.commands`
- Autorize no servidor

### 2. Bot não tem permissão
- Verifique se o bot tem "Read Message History" no canal
- Verifique se o bot pode "Send Messages" no canal

### 3. IDs dos canais errados
- Use Modo Desenvolvedor no Discord
- Copie o ID correto do canal
- Verifique no `.env`

## 🧪 Teste com Logs

Agora o bot mostra logs quando recebe mensagens. Quando você escrever no canal, deve ver no terminal:

```
📨 Message received in channel 1442569802878029915 from username: ls -la
✅ Message processed successfully
```

**Se você NÃO vê essa mensagem:**
- ❌ MESSAGE_CONTENT_INTENT não está ativado
- ❌ Bot não está no servidor
- ❌ Bot não tem permissão

**Se você VÊ a mensagem mas não há resposta:**
- Verifique os logs: `tail -f storage/logs/laravel.log`
- Pode ser erro na execução do comando

## ✅ Checklist Rápido

- [ ] MESSAGE_CONTENT_INTENT ativado no Discord Developer Portal
- [ ] Bot adicionado ao servidor
- [ ] Bot tem permissão "Read Message History"
- [ ] IDs dos canais corretos no `.env`
- [ ] Bot reiniciado após ativar MESSAGE_CONTENT_INTENT

---

**Depois de ativar MESSAGE_CONTENT_INTENT, reinicie o bot!**

