# CustoTicket - Plugin GLPI

## 📋 Descrição

Plugin para GLPI que adiciona funcionalidades de gestão de custos e despesas aos chamados (Tickets). Permite registrar informações detalhadas sobre preço de atendimento, tipo de despesa, moeda, data, centro de custo, RC, OC e projeto associado ao ticket.

---

## 🎯 Funcionalidades

- **Preço do Atendimento**: Registra o valor de atendimento do ticket em reais
- **Descrição da Despesa**: Campo para descrever a natureza da despesa
- **Moeda**: Seleção entre BRL (R$), USD ($) ou EUR (€)
- **Tipo de Despesa**: Categorização em:
  - Materiais de consumo
  - Aluguel de carro
  - Refeição
  - Estacionamento
- **Data**: Data da despesa (preenchida automaticamente com data atual)
- **Centro de Custos**: Associação a:
  - Serviços continuados
  - Obras
  - Projetos estratégicos
- **RC**: Código de Requisição de Compra
- **OC**: Código de Ordem de Compra
- **Projeto**: Associação a projetos:
  - EDP
  - EMBRAER
  - SEDE GREEN4T SP
  - DCTA

---

## 📦 Instalação

### Pré-requisitos
- GLPI 10.0.0 ou superior (até 11.0.99)
- MySQL/MariaDB com suporte a UTF-8
- PHP 7.4+

### Passos

1. **Clone ou copie os arquivos do plugin** para:
   ```
   /plugins/custoticket/
   ```

2. **Estrutura de diretórios esperada:**
   ```
   /plugins/custoticket/
   ├── hook.php
   ├── setup.php
   ├── inc/
   │   └── custoticket.class.php
   └── README.md
   ```

3. **Acesse a administração do GLPI** e ative o plugin em:
   ```
   Configuração → Plugins → CustoTicket → Ativar
   ```

4. **O banco de dados será criado automaticamente** ao ativar o plugin.

---

## 🗄️ Banco de Dados

A tabela `glpi_plugin_custoticket_prices` é criada automaticamente durante a instalação com os seguintes campos:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT UNSIGNED | ID único (chave primária) |
| `tickets_id` | INT UNSIGNED | ID do ticket associado (chave única) |
| `preco_atendimento` | DECIMAL(20,4) | Preço do atendimento em reais |
| `descricao_despesa` | VARCHAR(255) | Descrição da despesa |
| `moeda` | VARCHAR(10) | Código da moeda (BRL, USD, EUR) |
| `tipo_despesa` | VARCHAR(50) | Tipo de despesa categorizado |
| `data_despesa` | DATE | Data da despesa |
| `centro_custo` | VARCHAR(50) | Centro de custos |
| `rc` | VARCHAR(50) | Número da Requisição de Compra |
| `oc` | VARCHAR(50) | Número da Ordem de Compra |
| `projeto` | VARCHAR(100) | Projeto associado |
| `date_creation` | TIMESTAMP | Data/hora de criação do registro |
| `date_mod` | TIMESTAMP | Data/hora da última modificação |

---

## 🔧 Como Usar

### Adicionando Custos a um Ticket

1. **Abra um ticket** ou crie um novo
2. **Role para baixo** no formulário do ticket
3. **Preencha os campos de custo:**
   - Preço do Atendimento (em reais)
   - Descrição da Despesa
   - Moeda (se diferente de BRL)
   - Tipo de Despesa
   - Data (automática)
   - Centro de Custos
   - RC (opcional)
   - OC (opcional)
   - Projeto

4. **Clique em salvar** - os dados serão armazenados no banco de dados

### Visualizando Custos

Os custos são exibidos automaticamente no formulário do ticket e os valores são carregados do banco de dados quando o ticket é aberto novamente.

### Formatação de Valores

Os campos de preço são automaticamente formatados para o padrão brasileiro (vírgula como separador decimal) via JavaScript.

---

## 📝 Estrutura de Código

### `setup.php`
Arquivo de configuração do plugin com:
- Constantes de versão
- Definição de requisitos de compatibilidade GLPI
- Registro de hooks
- Funções de instalação/desinstalação

### `hook.php`
Contém todas as funções principais:

#### `plugin_custoticket_install()`
Cria a tabela no banco de dados

#### `plugin_custoticket_uninstall()`
Remove a tabela ao desinstalar o plugin

#### `plugin_custoticket_add_field($params)`
Exibe os campos de custo no formulário do ticket
- Carrega dados salvos se o ticket já existe
- Renderiza campos em HTML
- Aplica formatação JavaScript

#### `plugin_custoticket_item_add($params)` e `plugin_custoticket_item_update($params)`
Salva os dados no banco de dados ao criar ou atualizar um ticket

#### `plugin_custoticket_save_data($params)`
Lógica compartilhada para salvar dados:
- Valida dados do formulário
- Converte valores de moeda
- Insere ou atualiza registros no banco

### `inc/custoticket.class.php`
Classe principal para gerenciar abas customizadas (futura expansão)

---

## 🐛 Troubleshooting

### Campos não aparecem no ticket
- Verifique se o plugin está ativado em Configuração → Plugins
- Limpe o cache do GLPI: Configuração → Geral → Limpar cache
- Verifique se o arquivo `hook.php` está no diretório correto

### Dados não são salvos
- Verifique permissões do banco de dados
- Confira se a tabela foi criada: `SHOW TABLES LIKE 'glpi_plugin_custoticket%'`
- Verifique logs do GLPI: `/files/_log/glpi.log`

### Erro "Tela branca"
- Ative debug no `hook.php` adicionando no início:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Verifique erros de sintaxe PHP:
  ```bash
  php -l /path/to/hook.php
  ```

---

## 🔐 Segurança

- Todos os valores de entrada são escapados com `htmlspecialchars()`
- Usa prepared statements para prevenir SQL injection
- Compatível com sistema de permissões do GLPI

---

## 📄 Licença

MIT

---

## 👨‍💻 Autor

João Assis

---

## 📅 Histórico de Versões

### v1.0.0
- Primeira versão estável
- Implementação completa de campos de custo
- Armazenamento em banco de dados
- Formulário integrado ao Ticket

---

## 📞 Suporte

Para dúvidas ou problemas, verifique:
1. Logs do GLPI em `/files/_log/glpi.log`
2. Console do navegador (F12 → Console)
3. Sintaxe do PHP com `php -l`
