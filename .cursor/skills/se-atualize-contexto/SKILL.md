---
name: se-atualize-contexto
description: Resume o estado real do projeto cruzando o git com o STATUS_PROJETO.md. Use no início de um chat ou quando o usuário disser "Me atualize", "Se atualize no contexto", "Onde estamos" ou "Próximos passos".
---

# Se atualize no contexto

1. `git fetch`, `git status -sb`, `git log -5 --oneline`.
2. Ler `docs/STATUS_PROJETO.md` e, se existir, `docs/handoff/para-externo.md` (itens abertos).
3. Cruzar:
   - Tree suja ou commits à frente ⇒ "há mudanças locais: falta Pode subir".
   - Atrás do remoto ⇒ "alguém publicou depois (Lovable/outra máquina): faça pull antes de mexer".
   - Limpo e sincronizado ⇒ próximo passo é o do STATUS.
   - STATUS diz uma coisa e o git outra ⇒ confie no git e aponte a divergência.
4. Responder em até 10 linhas: estado do git, o que está em produção, pendências P1, próximo passo sugerido.

Proibido afirmar "falta subir" ou "está tudo sincronizado" sem ter rodado o passo 1 agora.
