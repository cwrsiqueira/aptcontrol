# Incidente de segurança

Vazou segredo, alguém acessou o que não devia, dado pessoal exposto, sistema invadido. Em ordem:

## 1. Conter (primeira hora)

- **Segredo vazou:** rotacionar a chave no provedor agora. Apagar do código não resolve; o histórico e os clones guardam.
- **Acesso indevido:** derrubar sessões (trocar `APP_KEY`/`AUTH_SECRET` ou incrementar `token_version`), bloquear a conta ou a rota, tirar a página do ar se preciso.
- Preservar evidências: logs, horário, IPs. Não apagar nada para "limpar".

## 2. Avaliar (até 24 h)

- O que foi exposto, de quantas pessoas, desde quando.
- Há dado pessoal? Sensível (saúde, documento, financeiro, menor)?
- Pode causar **risco ou dano relevante** aos titulares (fraude, discriminação, dano financeiro ou à reputação)?

## 3. Comunicar (LGPD)

- Risco ou dano relevante ⇒ comunicar a **ANPD em até 3 dias úteis** do conhecimento (agente de pequeno porte: prazo em dobro), pelo canal oficial da ANPD. Se faltar informação, comunicação preliminar com o que se sabe.
- Comunicar os **titulares afetados** sem demora injustificada, em linguagem simples: o que aconteceu, quais dados, o que estamos fazendo, o que eles devem fazer.
- Sem risco relevante ⇒ não comunica, mas **registra** a avaliação e o motivo.
- Confirme prazos e formulário no site da ANPD na hora: a regra pode mudar.

## 4. Corrigir

- Correção com teste que prova o bloqueio.
- Procurar o mesmo problema nos outros projetos (o padrão costuma se repetir).

## 5. Registrar

No `docs/SEGURANCA.md` do projeto (seção Incidentes) ou em `docs/INCIDENTES.md`:

```text
Data:
Tipo: incidente | quase-incidente
Gravidade: crítica | alta | média | baixa
Área: segredos | acesso | dados | infraestrutura | dependência | mobile | IA
Resumo:
Causa raiz:
Correção aplicada:
Prevenção (regra, teste, alerta):
Comunicado à ANPD/titulares: sim (data) | não (motivo)
Status: aberto | mitigado | encerrado
```

Sem valor de segredo, sem dado pessoal no registro.
