
# Webhook Monitor

## Descrição

O **Webhook Monitor** é uma aplicação desenvolvida para facilitar o monitoramento e gestão de webhooks em tempo real. O sistema permite que os usuários gerem URLs de webhook, recebam e visualizem notificações de novas requisições, e retransmitam as informações para outras URLs configuradas.

## Funcionalidades

- **Notificações em Tempo Real**: Receba notificações instantâneas no navegador para cada novo webhook recebido.
- **Retransmissão Automática de Webhooks**: Configure URLs de destino para retransmitir automaticamente as requisições recebidas.
- **Visualização Detalhada de Webhooks**: Acesse informações completas, como cabeçalhos, parâmetros de consulta e corpo da requisição.
- **Forçar Retransmissão**: Em desenvolvimento, essa função permitirá retransmitir manualmente webhooks específicos.
- **Interface Intuitiva**: Gerencie URLs de webhook e visualize detalhes de cada requisição em uma interface amigável.

## Requisitos do Sistema

- **PHP 7.4+** (recomendado PHP 8.0+)
- **Laravel 8+**
- **MySQL** ou outro banco de dados compatível
- **Pusher** (para notificações em tempo real)
- **Node.js** (para execução de scripts de frontend)

## Instalação

### 1. Clonar o Repositório

```bash
git clone https://github.com/leonardodevbr/webhooks.git
cd webhooks
```

### 2. Configuração do Ambiente

Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com as seguintes configurações:

- Configurações de banco de dados:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=seu_banco_de_dados
  DB_USERNAME=seu_usuario
  DB_PASSWORD=sua_senha
  ```

- Configurações do Pusher (para notificações em tempo real):
  ```env
  PUSHER_APP_ID=seu_app_id
  PUSHER_APP_KEY=seu_app_key
  PUSHER_APP_SECRET=seu_app_secret
  PUSHER_APP_CLUSTER=seu_cluster
  ```

### 3. Instalar Dependências

Instale as dependências do PHP e do Node.js:

```bash
composer install
npm install && npm run dev
```

### 4. Geração de Chave da Aplicação

```bash
php artisan key:generate
```

### 5. Configuração do Banco de Dados

Execute as migrações para criar as tabelas necessárias:

```bash
php artisan migrate
```

### 6. Iniciar o Servidor

```bash
php artisan serve
```

A aplicação estará disponível em `http://localhost:8000`.

## Uso

- Acesse a interface web para gerar novas URLs de webhook, visualizar requisições recebidas e configurar retransmissões.
- Use as URLs geradas em serviços externos para enviar webhooks e observar a recepção e tratamento em tempo real pelo sistema.
