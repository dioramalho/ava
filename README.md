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
2. Inicie o servidor PHP na raiz do portal:
   ```bash
   php -S 127.0.0.1:8000 -t portal-aulas-ete
   ```
3. Abra no navegador:
   - `http://127.0.0.1:8000/index.html`

## Credenciais de acesso

- Usuário: `professor`
- Senha: `1234`

## Banco de dados

- Por padrão, o backend sobe com um banco SQLite local em `portal-aulas-ete/backend/storage/database.sqlite`, inicializado automaticamente com dados de exemplo.
- Se desejar usar MySQL/MariaDB, configure as variáveis `PORTAL_DB_*` antes de iniciar o servidor e importe `portal_aulas_ete.sql`.
