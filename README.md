# ava

Ambiente virtual de aprendizado.

## Projeto incluído

Este repositório contém um portal front-end para aulas do curso Técnico em Desenvolvimento de Sistemas:

- Pasta: `portal-aulas-ete/`
- Tecnologias: HTML, CSS, Bootstrap 5 e JavaScript (login simulado)
- Módulos: dashboard do aluno e dashboard do professor (cadastros/publicações simuladas)
- Banco de dados: script SQL de importação para MySQL/MariaDB em `portal_aulas_ete.sql` (raiz do repositório)

## Como executar localmente

1. Entre na pasta do projeto:
   ```bash
   cd portal-aulas-ete
   ```
2. Inicie um servidor estático simples:
   ```bash
   python -m http.server 8000
   ```
3. Abra no navegador:
   - `http://localhost:8000/index.html`

## Credenciais de acesso (simulado)

- Usuário: `professor`
- Senha: `1234`
