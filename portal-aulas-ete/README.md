# Portal de Aulas — TDS

Projeto front-end para organização de materiais em PDF das turmas TDS 2024 e TDS 2025 da Escola Técnica Estadual.

## Estrutura

```text
portal-aulas-ete/
├── index.html
├── dashboard.html
├── dashboard-professor.html
├── css/style.css
├── js/auth.js
├── backend/
│   ├── app/
│   ├── config/
│   └── public/
├── assets/
│   ├── logo.svg
│   └── pdf/
└── components/
    ├── navbar.html
    └── footer.html
```

## Como usar

1. Abra `index.html` em um servidor estático.
2. Faça login com:
   - **Login:** `professor`
   - **Senha:** `1234`
3. Você será redirecionado para `dashboard.html`.
4. Clique nos botões de PDF para abrir os materiais.
5. Para acessar o painel do professor, abra `dashboard-professor.html` (após login simulado).
6. Se desejar preparar o banco local, importe `../portal_aulas_ete.sql` no phpMyAdmin ou no MariaDB/MySQL.
7. Para subir o backend PHP 5.6 com MVC/PDO, veja `backend/README.md`.

## Observações

- Agora existe um backend PHP simples em `backend/`, mantendo o front-end principal separado para facilitar evolução gradual.
- O login usa validação simples em JavaScript e `localStorage` para proteger as páginas de aluno e professor.
- A estrutura foi separada para facilitar integração futura com API/back-end.
- O arquivo `portal_aulas_ete.sql`, na raiz do repositório, cria o banco com dados mínimos de professor, turmas, alunos, matérias, recados e documentos.
- O backend em `backend/` usa PHP 5.6 + PDO, arquitetura MVC simples e autenticação persistida com `sha1`, conforme solicitado.

## Observações

- Não há backend real.
- O login usa validação simples em JavaScript e `localStorage` para proteger as páginas de aluno e professor.
- A estrutura foi separada para facilitar integração futura com API/back-end.
- O arquivo `portal_aulas_ete.sql`, na raiz do repositório, cria o banco com dados mínimos de professor, turmas, alunos, matérias, recados e documentos.
- O login usa validação simples em JavaScript e `localStorage`.
- A estrutura foi separada para facilitar integração futura com API/back-end.
