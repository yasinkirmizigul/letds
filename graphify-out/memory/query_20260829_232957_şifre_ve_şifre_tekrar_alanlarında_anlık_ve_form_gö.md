---
type: "query"
date: "2026-08-29T23:29:57.433881+00:00"
question: "Şifre ve şifre tekrar alanlarında anlık ve form gönderiminde eşleşme kontrolü var mı?"
contributor: "graphify"
outcome: "useful"
source_nodes: ["PasswordResetController"]
---

# Q: Şifre ve şifre tekrar alanlarında anlık ve form gönderiminde eşleşme kontrolü var mı?

## Answer

Expanded from original query via graph vocab: [password, confirm, validation, register, reset, credential]. Sunucu tarafında bulunan tüm parola-tekrar akışları Laravel confirmed kuralıyla eşleşmeyi reddediyor. Anlık istemci kontrolü yalnızca admin profil düzenleme ve admin şifre sıfırlama ekranlarında var; üye kayıt, üye şifre sıfırlama, üye hesap düzenleme ve admin üye düzenleme ekranlarında eşleşme için JS dinleyicisi bulunmadı. Uyumsuz parola için özel otomatik test de bulunmadı.

## Outcome

- Signal: useful

## Source Nodes

- PasswordResetController