# Plugin CustoTicket para GLPI

Este plugin adiciona funcionalidade de associação de valores financeiros aos tickets do GLPI, permitindo que gerentes de contrato tenham indicadores financeiros para ajustar suas operações adequadamente.

## Características

- **Campo Preço**: Adiciona um campo monetário "Preço" aos tickets
- **Formatação em Real**: Valores formatados em R$ com separadores de milhares e centavos
- **Validação**: Campo aceita apenas valores monetários válidos
- **Integração com Solução**: Quando o ticket é solucionado, o valor é adicionado automaticamente no final do texto da solução
- **Interface Responsiva**: Campo integrado naturalmente ao formulário do GLPI

## Requisitos

- GLPI >= 10.0.0
- PHP >= 8.1
- MySQL/MariaDB

## Instalação

1. Extraia os arquivos do plugin na pasta `plugins/custoticket/` da sua instalação GLPI
2. Acesse **Setup > Plugins** no GLPI
3. Localize o plugin "CustoTicket" e clique em **Instalar**
4. Após a instalação, clique em **Ativar**

## Como Usar

1. Abra qualquer ticket existente ou crie um novo
2. Você verá o campo "Preço do Atendimento" no formulário
3. Digite o valor financeiro associado ao ticket (ex: 150,00)
4. Salve o ticket
5. Quando o ticket for solucionado, o valor aparecerá automaticamente no final da solução

## Funcionalidades

### Campo de Preço
- Campo monetário com formatação automática
- Aceita valores decimais com vírgula ou ponto
- Validação de entrada para aceitar apenas números
- Máscara de formatação em tempo real

### Integração com Solução
- Valor é adicionado automaticamente quando ticket é marcado como solucionado
- Formatação: "**Valor do Atendimento:** R$ 150,00"
- Não duplica valores em atualizações subsequentes

### Armazenamento
- Valores armazenados em tabela dedicada
- Associação única por ticket
- Histórico de modificações com timestamps

## Estrutura do Banco de Dados

O plugin cria a tabela `glpi_plugin_custoticket_prices`:

```sql
CREATE TABLE `glpi_plugin_custoticket_prices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tickets_id` int unsigned NOT NULL DEFAULT '0',
  `preco` decimal(20,4) NOT NULL DEFAULT '0.0000',
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_id` (`tickets_id`)
);
```

## Desenvolvimento

### Arquivos Principais

- `setup.php`: Configuração e inicialização do plugin
- `hook.php`: Hooks de instalação e funcionalidades principais
- `css/custoticket.css`: Estilos personalizados

### Hooks Utilizados

- `pre_item_form`: Carrega dados antes da exibição
- `post_item_form`: Adiciona campo ao formulário
- `item_add`: Salva preço na criação
- `item_update`: Salva preço na atualização e integra com solução

## Contribuição

Este plugin foi desenvolvido seguindo as melhores práticas do GLPI:
- Estrutura padrão de plugins
- Uso de hooks oficiais
- Sanitização de dados
- Interface consistente com o GLPI

## Suporte

Para suporte e melhorias, entre em contato através das issues do repositório ou e-mail do desenvolvedor.

## Licença

MIT License - você pode usar, modificar e distribuir livremente.

---

**Desenvolvido por:** João Assis  
**Versão:** 1.0.0  
**Compatível com:** GLPI 10.0.0+
"""
