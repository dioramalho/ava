# Deploy automático com GitHub Actions + FTP

Este projeto agora possui um workflow em `/.github/workflows/deploy-develop.yml` para publicar o portal automaticamente quando houver **push na branch `develop`**.

## O que esse workflow faz

- escuta `push` na branch `develop`
- faz checkout do repositório
- envia os arquivos de `portal-aulas-ete/` para a hospedagem via FTP
- usa como URL de ambiente: `http://ava.dev.diogoramalho.com.br/`

## Dados já preenchidos no workflow

Os seguintes dados já foram adicionados com base nas informações que você enviou:

- **Domínio:** `http://ava.dev.diogoramalho.com.br/`
- **Servidor FTP:** `ftp.diogoramalho.com.br`
- **Usuário FTP:** `ete@diogoramalho.com.br`
- **Porta:** `21`
- **Protocolo inicial:** `ftp`
- **Branch de disparo:** `develop`

## O que você ainda precisa preencher

### 1. Criar o secret da senha no GitHub

No GitHub do repositório:

1. abra **Settings**
2. entre em **Secrets and variables**
3. clique em **Actions**
4. clique em **New repository secret**
5. crie o secret com:
   - **Name:** `FTP_PASSWORD`
   - **Value:** sua senha FTP

> Observação: no pedido você informou `senha: xxx`, então o workflow foi preparado para receber esse valor por secret, sem versionar a senha no repositório.

## Ponto mais importante para revisar

### `server-dir`

No workflow, a configuração está assim:

```yml
server-dir: ./
```

Isso funciona **somente se a raiz do login FTP já apontar para a pasta correta do site**.

Dependendo da hospedagem, você pode precisar trocar para algo como:

```yml
server-dir: public_html/
```

ou:

```yml
server-dir: public_html/ava.dev.diogoramalho.com.br/
```

Se o deploy rodar mas o site não atualizar, este costuma ser o primeiro campo a revisar.

## Como usar no dia a dia

1. ajuste o `server-dir` se necessário
2. configure o secret `FTP_PASSWORD`
3. faça commit das mudanças
4. envie para a branch `develop`
5. acompanhe em **GitHub > Actions**

## Como saber se deu certo

Depois do push na `develop`:

1. abra a aba **Actions** no GitHub
2. clique no workflow **Deploy via FTP (develop)**
3. verifique se o job **Publicar no ambiente de desenvolvimento** terminou com sucesso
4. abra o domínio `http://ava.dev.diogoramalho.com.br/`

## Observações úteis

- O workflow publica apenas a pasta `portal-aulas-ete/`.
- A pasta `backend/storage/uploads/` foi excluída do deploy para evitar sobrescrever uploads já existentes no servidor.
- Se sua hospedagem suportar **FTPS**, você pode trocar no YAML:

```yml
protocol: ftps
```

## Referência usada

- Documentação oficial do FTP Deploy Action: <https://github.com/SamKirkland/FTP-Deploy-Action>
