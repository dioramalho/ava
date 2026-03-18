# Backend PHP - Portal de Aulas TDS

Backend simples em **PHP 5.6**, **PDO**, **POO** e **MVC enxuto**, pensado para integração gradual com o front-end existente.

## Estrutura

```text
backend/
├── app/
│   ├── controllers/
│   ├── core/
│   └── models/
├── config/
├── public/
└── storage/uploads/
```

## Requisitos

- PHP 5.6+
- PDO MySQL habilitado
- MySQL ou MariaDB
- Apache com `mod_rewrite` habilitado (opcional; o projeto também roda com `php -S`)

## Configuração

1. Defina as variáveis `PORTAL_DB_HOST`, `PORTAL_DB_PORT`, `PORTAL_DB_NAME`, `PORTAL_DB_USER` e `PORTAL_DB_PASSWORD`.
2. Importe `../../portal_aulas_ete.sql` no banco MySQL/MariaDB.
3. Aponte o servidor web para `backend/public/` ou rode `php -S 127.0.0.1:8000 -t ../`.

## Rotas principais

- `GET /health`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`
- `GET /turmas` / `POST /turmas`
- `GET /alunos` / `POST /alunos`
- `GET /materias` / `POST /materias`
- `GET /recados` / `POST /recados`
- `GET /documentos` / `POST /documentos`

## Login padrão

- Login: `professor`
- Senha: `1234`
- Criptografia persistida no banco: `sha1`
