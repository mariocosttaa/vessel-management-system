# Como Obter o Token do Bot do Discord

Guia passo a passo para encontrar o token do seu bot.

## 📍 Passo a Passo

### 1. Acesse o Discord Developer Portal

Abra no navegador:
**https://discord.com/developers/applications**

Faça login com sua conta Discord.

### 2. Selecione Sua Aplicação

Na lista de aplicações, clique na aplicação que você criou (ou crie uma nova se ainda não tiver).

**Se você ainda não criou:**
- Clique em **"New Application"** (canto superior direito)
- Digite um nome (ex: "Vessel Management Bot")
- Clique em **"Create"**

### 3. Vá para a Seção "Bot"

No menu lateral esquerdo, clique em **"Bot"**.

### 4. Encontre o Token

Na página do Bot, você verá uma seção chamada **"TOKEN"**.

**Se você já tem um token:**
- Clique no botão **"Copy"** ou **"Reset Token"**
- ⚠️ **IMPORTANTE:** Se você clicar em "Reset Token", o token antigo será invalidado e você receberá um novo token.

**Se você ainda não tem um bot:**
- Clique em **"Add Bot"** ou **"Create Bot"**
- Confirme a criação
- O token aparecerá automaticamente

### 5. Copie o Token

O token parece com algo assim:
```
MTIzNDU2Nzg5MDEyMzQ1Njc4OQ.abcdefghijklmnopqrstuvwxyz.1234567890abcdefghijklmnopqrstuvwxyz
```

**⚠️ ATENÇÃO:**
- O token é **SENSÍVEL** - não compartilhe com ninguém
- Se você perder o token, pode resetá-lo (mas o antigo para de funcionar)
- Guarde o token em local seguro

### 6. Cole no .env

Cole o token no seu arquivo `.env`:

```env
DISCORD_BOT_TOKEN=MTIzNDU2Nzg5MDEyMzQ1Njc4OQ.abcdefghijklmnopqrstuvwxyz.1234567890abcdefghijklmnopqrstuvwxyz
```

## 🖼️ Onde Está Visualmente

```
Discord Developer Portal
├── Applications
│   └── [Sua Aplicação]
│       ├── General Information
│       ├── Bot ← AQUI!
│       │   └── TOKEN ← AQUI ESTÁ O TOKEN!
│       ├── OAuth2
│       └── ...
```

## 🔒 Segurança

### ⚠️ NUNCA:
- ❌ Compartilhe o token publicamente
- ❌ Commite o token no Git
- ❌ Envie o token por email/mensagem não criptografada
- ❌ Deixe o token visível em screenshots

### ✅ SEMPRE:
- ✅ Use variáveis de ambiente (`.env`)
- ✅ Mantenha o `.env` no `.gitignore`
- ✅ Guarde o token em local seguro
- ✅ Se o token vazar, resete imediatamente

## 🐛 Problemas Comuns

### "Token não aparece"

**Solução:**
1. Certifique-se de que criou o bot (clique em "Add Bot")
2. Verifique se está na seção correta ("Bot" no menu lateral)
3. Se ainda não aparecer, tente resetar o token

### "Token não funciona"

**Soluções:**
1. Verifique se copiou o token completo (não cortado)
2. Verifique se não há espaços antes/depois do token
3. Verifique se o token não foi resetado (use o token mais recente)
4. Verifique se o bot foi adicionado ao servidor Discord

### "Esqueci o token"

**Solução:**
1. Vá em "Bot" → "Reset Token"
2. Um novo token será gerado
3. ⚠️ O token antigo para de funcionar imediatamente
4. Atualize o `.env` com o novo token

## 📝 Checklist

- [ ] Acessei https://discord.com/developers/applications
- [ ] Selecionei minha aplicação (ou criei uma nova)
- [ ] Fui para a seção "Bot"
- [ ] Copiei o token
- [ ] Colei no `.env` como `DISCORD_BOT_TOKEN=...`
- [ ] Guardei o token em local seguro

## 🎯 Próximos Passos

Depois de obter o token:

1. **Cole no `.env`:**
   ```env
   DISCORD_BOT_TOKEN=seu_token_aqui
   ```

2. **Limpe o cache:**
   ```bash
   php artisan config:clear
   ```

3. **Teste o bot:**
   ```bash
   php artisan discord:bot
   ```

---

**Precisa de mais ajuda?** Verifique a documentação completa em `docs/discord-bot-setup.md`

