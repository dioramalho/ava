# Portal de Aulas — TDS

Projeto front-end para organização de materiais em PDF das turmas TDS 2024 e TDS 2025 da Escola Técnica Estadual.

## Estrutura

```text
portal-aulas-ete/
├── index.html
├── dashboard.html
├── css/style.css
├── js/auth.js
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

## Observações

- Não há backend real.
- O login usa validação simples em JavaScript e `localStorage`.
- A estrutura foi separada para facilitar integração futura com API/back-end.
