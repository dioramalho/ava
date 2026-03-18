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
- PDO SQLite habilitado (padrão local) ou PDO MySQL habilitado
- MySQL ou MariaDB somente se você optar pelo modo MySQL
- Apache com `mod_rewrite` habilitado (opcional; o projeto também roda com `php -S`)

## Configuração

1. Para uso local rápido, apenas inicie o servidor na raiz de `portal-aulas-ete/`; o arquivo `storage/database.sqlite` será criado automaticamente.
2. Para usar MySQL/MariaDB, defina as variáveis `PORTAL_DB_DRIVER=mysql`, `PORTAL_DB_HOST`, `PORTAL_DB_PORT`, `PORTAL_DB_NAME`, `PORTAL_DB_USER` e `PORTAL_DB_PASSWORD`.
3. Importe `../../portal_aulas_ete.sql` no banco MySQL/MariaDB, se estiver usando esse modo.
4. Aponte o servidor web para `backend/public/` ou rode `php -S 127.0.0.1:8000 -t ../`.

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
