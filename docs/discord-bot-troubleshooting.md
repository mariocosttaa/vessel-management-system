# Discord Bot Troubleshooting

## Bot não responde às mensagens

### Problema: Bot conecta mas não responde

**Possíveis causas:**

1. **MESSAGE_CONTENT_INTENT não está ativado**
   - ✅ **Solução:** Vá em Discord Developer Portal → Sua Aplicação → Bot
   - ✅ Ative **"MESSAGE CONTENT INTENT"** em "Privileged Gateway Intents"
   - ✅ Clique em **"Save Changes"**
   - ⚠️ **IMPORTANTE:** Sem isso, o bot não consegue ler o conteúdo das mensagens!

2. **Bot não tem permissão para ler mensagens**
   - ✅ Verifique se o bot tem permissão "Read Message History" no canal
   - ✅ Verifique se o bot está no servidor correto

3. **IDs dos canais estão incorretos**
   - ✅ Verifique se os IDs estão corretos no `.env`
   - ✅ Use Modo Desenvolvedor no Discord para copiar os IDs corretos

4. **Bot não está no servidor**
   - ✅ Adicione o bot ao servidor usando OAuth2 URL Generator

### Como verificar

1. **Verifique os logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verifique se o bot está recebendo mensagens:**
   - Quando você escreve no canal, você deve ver no terminal:
   ```
   📨 Message received in channel 1442569802878029915 from username: ls -la
   ```

3. **Teste a configuração:**
   ```bash
   php artisan discord:test-bot
   ```

### Checklist

- [ ] MESSAGE_CONTENT_INTENT está ativado no Discord Developer Portal
- [ ] Bot foi adicionado ao servidor Discord
- [ ] Bot tem permissão "Read Message History" nos canais
- [ ] IDs dos canais estão corretos no `.env`
- [ ] Bot está rodando (`php artisan discord:bot`)
- [ ] Você está escrevendo nos canais corretos

### Comandos de Debug

```bash
# Ver logs em tempo real
tail -f storage/logs/laravel.log

# Testar configuração
php artisan discord:test-bot

# Testar conexão
php artisan discord:test-connection
```

---

**Se ainda não funcionar:** Verifique os logs e me mostre o erro!

