CUSTOTICKET - PLUGIN GLPI
=========================

O que é?
--------
Plugin simples para rastrear custos em tickets do GLPI.


INSTALAÇÃO
==========

1. Copiar pasta custoticket/ para /var/www/glpi/plugins/

2. Acessar GLPI > Setup > Plugins > Instalar "CustoTicket"

3. Ativar plugin

4. Criar arquivo /plugins/custoticket/templates/custo-ticket.html.twig
   (pode estar vazio)


COMO USAR
=========

1. Abrir ticket

2. Clicar aba "Custo"

3. Preencher campos:
   - Preço do Atendimento (ex: 1.500,00)
   - Descrição da despesa
   - Moeda (BRL, USD, EUR)
   - Tipo de Despesa (materiais, aluguel, refeição, estacionamento)
   - Data
   - Centro de Custos (serviços, obras, projetos)
   - RC (Responsabilidade de Custo)
   - OC (Ordem de Compra)
   - Projeto (EDP, EMBRAER, SEDE, DCTA)

4. Clicar "Adicionar" ou "Atualizar"


BANCO DE DADOS
==============

Tabela criada automaticamente na instalação:

CREATE TABLE glpi_plugin_custoticket_prices (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tickets_id INT UNSIGNED UNIQUE NOT NULL,
  preco_atendimento DECIMAL(20,4),
  descricao_despesa VARCHAR(255),
  moeda VARCHAR(10),
  tipo_despesa VARCHAR(50),
  data_despesa DATE,
  centro_custo VARCHAR(50),
  rc VARCHAR(50),
  oc VARCHAR(50),
  projeto VARCHAR(100),
  date_creation TIMESTAMP,
  date_mod TIMESTAMP
);


ARQUIVOS DO PLUGIN
==================

setup.php
  - Configuração e inicialização do plugin
  - Registra os hooks

hook.php
  - Funções de instalação/desinstalação
  - Renderiza campos do formulário
  - Salva dados no banco

inc/custoticket.class.php
  - Classe principal
  - Define aba "Custo" no ticket

templates/custo-ticket.html.twig
  - Template de exibição
  - Deve ser criado manualmente


VERSÃO
======
2.3.1

COMPATIBILIDADE
===============
GLPI: 10.0.0 ou superior
PHP: 7.2 ou superior
