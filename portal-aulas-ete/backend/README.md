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

- PHP 5.6
- PDO MySQL habilitado
- MySQL ou MariaDB
- Apache com `mod_rewrite` habilitado (recomendado)

## Configuração

1. Ajuste as credenciais em `config/config.php`.
2. Importe `../../portal_aulas_ete.sql` no banco.
3. Aponte o servidor web para `backend/public/`.

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
