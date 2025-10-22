<?php
/**
 * CustoTicket - hook.php
 * Arquivo que contém os hooks do plugin para integração com GLPI
 * 
 * VERSÃO COM DEBUGGING: Escreve logs em /tmp/custoticket_debug.log
 * Isso permite funcionar mesmo sem o diretório de logs do GLPI configurado
 * 
 * @package PluginCustoticket
 * @license MIT
 */

/**
 * Define o arquivo de log temporário para debug
 * Todos os logs do plugin serão escritos aqui
 */
define('DEBUG_FILE', '/tmp/custoticket_debug.log');

/**
 * Função auxiliar para registrar mensagens de debug
 * 
 * Adiciona timestamps e append as mensagens ao arquivo de log
 * para facilitar troubleshooting sem acessar logs do GLPI
 * 
 * @param string $msg Mensagem a ser registrada no log
 * 
 * @return void
 */
function debug_log($msg) {
    // Formata o timestamp no padrão Y-m-d H:i:s
    $timestamp = date('Y-m-d H:i:s');
    // Escreve a mensagem ao arquivo, com flag FILE_APPEND para não sobrescrever
    file_put_contents(DEBUG_FILE, "[$timestamp] $msg\n", FILE_APPEND);
}

/**
 * Função de instalação do plugin
 * Chamada automaticamente quando o plugin é instalado no GLPI
 * Cria a tabela de preços se não existir
 * 
 * @global $DB Objeto de conexão com banco de dados GLPI
 * 
 * @return boolean true se sucesso, false caso contrário
 */
function plugin_custoticket_install_db() {
    global $DB;
    debug_log("CustoTicket: Iniciando instalação da tabela");
    
    // Verifica se a tabela já existe
    if (!$DB->tableExists('glpi_plugin_custoticket_prices')) {
        // SQL para criar a tabela com todos os campos necessários
        $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_custoticket_prices` (
           `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
           `tickets_id` INT UNSIGNED NOT NULL DEFAULT '0',
           `preco_atendimento` DECIMAL(20,4) NOT NULL DEFAULT '0.0000',
           `descricao_despesa` VARCHAR(255) DEFAULT NULL,
           `moeda` VARCHAR(10) DEFAULT 'BRL',
           `tipo_despesa` VARCHAR(50) DEFAULT NULL,
           `data_despesa` DATE DEFAULT NULL,
           `centro_custo` VARCHAR(50) DEFAULT NULL,
           `rc` VARCHAR(50) DEFAULT NULL,
           `oc` VARCHAR(50) DEFAULT NULL,
           `projeto` VARCHAR(100) DEFAULT NULL,
           `date_creation` TIMESTAMP NULL,
           `date_mod` TIMESTAMP NULL,
           PRIMARY KEY (`id`),
           UNIQUE KEY `tickets_id` (`tickets_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Executa a query de criação
        if ($DB->query($query)) {
            debug_log("CustoTicket: Tabela criada com sucesso");
        } else {
            // Log do erro se não conseguir criar
            debug_log("CustoTicket: Erro ao criar tabela - " . $DB->error());
            return false;
        }
    } else {
        // Tabela já existe, apenas registra no log
        debug_log("CustoTicket: Tabela já existe");
    }
    
    return true;
}

/**
 * Função de desinstalação do plugin
 * Chamada quando o plugin é desinstalado
 * Remove a tabela de preços
 * 
 * @global $DB Objeto de conexão com banco de dados GLPI
 * 
 * @return boolean true (sempre bem-sucedida)
 */
function plugin_custoticket_uninstall_db() {
    global $DB;
    
    // Verifica se a tabela existe
    if ($DB->tableExists('glpi_plugin_custoticket_prices')) {
        // Query para deletar a tabela
        $query = "DROP TABLE `glpi_plugin_custoticket_prices`";
        $DB->query($query);
        debug_log("CustoTicket: Tabela removida");
    }
    
    return true;
}

/**
 * Hook executado quando um formulário de item é exibido
 * Adiciona os campos personalizados de custo ao formulário do Ticket
 * 
 * Este hook é crucial: renderiza todos os campos HTML que o usuário verá
 * para inserir dados de custo
 * 
 * @param array $params Array com 'item' (o objeto Ticket)
 * 
 * @return void (Imprime HTML diretamente)
 */
function plugin_custoticket_add_field($params) {
    debug_log("ADD_FIELD: Função chamada");
    
    // Extrai o item (Ticket) dos parâmetros passados pelo hook
    $item = $params['item'] ?? null;
    
    // Verifica se o item é realmente um Ticket
    if (!($item instanceof Ticket)) {
        debug_log("ADD_FIELD: Item não é um Ticket");
        return;
    }

    global $DB;
    // Obtém o ID do ticket (será > 0 se ticket já existe, ou <= 0 se novo)
    $ticket_id = (int)$item->getID();
    
    debug_log("ADD_FIELD: Ticket ID = " . ($ticket_id > 0 ? $ticket_id : "NOVO"));
    
    /**
     * Valores padrão dos campos
     * Usados quando é um novo ticket ou dados não encontrados no banco
     */
    $data = [
        'preco_atendimento' => '0,00',
        'descricao_despesa' => '',
        'moeda' => 'BRL',
        'tipo_despesa' => '',
        'data_despesa' => date('Y-m-d'),
        'centro_custo' => '',
        'rc' => '',
        'oc' => '',
        'projeto' => ''
    ];

    /**
     * Se ticket já existe, busca os dados no banco
     * Query na tabela de preços para carregar valores anteriores
     */
    if ($ticket_id > 0) {
        // Request padrão do GLPI para buscar dados
        $it = $DB->request([
            'FROM'  => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        // Itera sobre os resultados (deve haver no máximo um devido à UNIQUE KEY)
        foreach ($it as $row) {
            // Converte preço do banco para formato brasileiro (0,00)
            $data = [
                'preco_atendimento' => number_format((float)$row['preco_atendimento'], 2, ',', '.'),
                'descricao_despesa' => htmlspecialchars($row['descricao_despesa'] ?? '', ENT_QUOTES),
                'moeda' => $row['moeda'] ?? 'BRL',
                'tipo_despesa' => $row['tipo_despesa'] ?? '',
                'data_despesa' => $row['data_despesa'] ?? date('Y-m-d'),
                'centro_custo' => $row['centro_custo'] ?? '',
                'rc' => htmlspecialchars($row['rc'] ?? '', ENT_QUOTES),
                'oc' => htmlspecialchars($row['oc'] ?? '', ENT_QUOTES),
                'projeto' => $row['projeto'] ?? ''
            ];
            debug_log("ADD_FIELD: Dados carregados do banco - Preço: " . $data['preco_atendimento']);
            break;
        }
    }

    // ==================== RENDERIZAÇÃO DOS CAMPOS HTML ====================
    
    /**
     * Campo: Preço do Atendimento
     * Entrada de texto com máscara de moeda (R$)
     * Formatação esperada: 1.234,56
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="preco_custoticket">Preço do Atendimento:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <div class="input-group">';
    echo '      <span class="input-group-text">R$</span>';
    echo '      <input type="text" class="form-control" name="preco_custoticket" id="preco_custoticket" value="' . $data['preco_atendimento'] . '" placeholder="0,00">';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: Descrição da Despesa
     * Campo de texto livre para detalhar a despesa
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="descricao_despesa">Descrição da despesa:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="descricao_despesa" id="descricao_despesa" value="' . $data['descricao_despesa'] . '" placeholder="Descreva a despesa">';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: Moeda
     * Select com opções: BRL, USD, EUR
     * Padrão: BRL (Real Brasileiro)
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="moeda">Moeda:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <select name="moeda" id="moeda" class="form-select">';
    echo '      <option value="BRL" ' . ($data['moeda'] === 'BRL' ? 'selected' : '') . '>BRL (R$)</option>';
    echo '      <option value="USD" ' . ($data['moeda'] === 'USD' ? 'selected' : '') . '>USD ($)</option>';
    echo '      <option value="EUR" ' . ($data['moeda'] === 'EUR' ? 'selected' : '') . '>EUR (€)</option>';
    echo '    </select>';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: Tipo de Despesa
     * Categorização da despesa para melhor controle
     * Opções: Materiais, Aluguel, Refeição, Estacionamento, etc
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="tipo_despesa">Tipo:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <select name="tipo_despesa" id="tipo_despesa" class="form-select">';
    echo '      <option value="" ' . ($data['tipo_despesa'] === '' ? 'selected' : '') . '>-----</option>';
    echo '      <option value="materiais" ' . ($data['tipo_despesa'] === 'materiais' ? 'selected' : '') . '>Materiais de consumo</option>';
    echo '      <option value="aluguel" ' . ($data['tipo_despesa'] === 'aluguel' ? 'selected' : '') . '>Aluguel de carro</option>';
    echo '      <option value="refeicao" ' . ($data['tipo_despesa'] === 'refeicao' ? 'selected' : '') . '>Refeição</option>';
    echo '      <option value="estacionamento" ' . ($data['tipo_despesa'] === 'estacionamento' ? 'selected' : '') . '>Estacionamento</option>';
    echo '    </select>';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: Data da Despesa
     * Date picker para selecionar quando a despesa ocorreu
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="data_despesa">Data:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="date" class="form-control" name="data_despesa" id="data_despesa" value="' . $data['data_despesa'] . '">';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: Centro de Custos
     * Classificação da despesa por centro de custos da empresa
     * Opções: Serviços, Obras, Projetos Estratégicos
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="centro_custo">Centro de custos:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <select name="centro_custo" id="centro_custo" class="form-select">';
    echo '      <option value="" ' . ($data['centro_custo'] === '' ? 'selected' : '') . '>-----</option>';
    echo '      <option value="servicos" ' . ($data['centro_custo'] === 'servicos' ? 'selected' : '') . '>Serviços continuados</option>';
    echo '      <option value="obras" ' . ($data['centro_custo'] === 'obras' ? 'selected' : '') . '>Obras</option>';
    echo '      <option value="projetos" ' . ($data['centro_custo'] === 'projetos' ? 'selected' : '') . '>Projetos estratégicos</option>';
    echo '    </select>';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: RC (Responsabilidade de Custo)
     * Código ou ID do centro responsável
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="rc">RC:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="rc" id="rc" value="' . $data['rc'] . '" placeholder="RC">';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: OC (Ordem de Compra)
     * Número da ordem de compra relacionada
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="oc">OC:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="oc" id="oc" value="' . $data['oc'] . '" placeholder="OC">';
    echo '  </div>';
    echo '</div>';

    /**
     * Campo: Projeto
     * Associação da despesa a um projeto específico
     * Projetos: EDP, EMBRAER, SEDE GREEN4T, DCTA
     */
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="projeto">Projeto:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <select name="projeto" id="projeto" class="form-select">';
    echo '      <option value="" ' . ($data['projeto'] === '' ? 'selected' : '') . '>-----</option>';
    echo '      <option value="edp" ' . ($data['projeto'] === 'edp' ? 'selected' : '') . '>EDP</option>';
    echo '      <option value="embraer" ' . ($data['projeto'] === 'embraer' ? 'selected' : '') . '>EMBRAER</option>';
    echo '      <option value="sede" ' . ($data['projeto'] === 'sede' ? 'selected' : '') . '>SEDE GREEN4T SP</option>';
    echo '      <option value="dcta" ' . ($data['projeto'] === 'dcta' ? 'selected' : '') . '>DCTA</option>';
    echo '    </select>';
    echo '  </div>';
    echo '</div>';

    /**
     * JavaScript para formatação automática do campo de preço
     * 
     * Quando o usuário sai do campo (blur), formata o valor como moeda:
     * - Remove espaços e pontos
     * - Substitui vírgula por ponto para conversão
     * - Formata de volta com 2 casas decimais e vírgula
     * 
     * Exemplo: "1.234,56" -> 1234.56 -> "1234.56"
     */
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '  const input = document.getElementById("preco_custoticket");';
    echo '  if (input) {';
    echo '    input.addEventListener("blur", function() {';
    echo '      const v = parseFloat(this.value.replace(/\\./g, "").replace(",", ".")) || 0;';
    echo '      this.value = v.toFixed(2).replace(".", ",");';
    echo '    });';
    echo '  }';
    echo '});';
    echo '</script>';
    
    debug_log("ADD_FIELD: Campos renderizados com sucesso");
}

/**
 * Hook executado quando um novo item (Ticket) é criado
 * Insere os dados de custo na tabela glpi_plugin_custoticket_prices
 * 
 * Este hook é chamado APÓS o ticket ser criado no banco
 * Captura os dados do formulário e salva
 * 
 * @param Ticket $item O ticket recém-criado
 * 
 * @return void
 */
function plugin_custoticket_item_add($item) {
    // Verifica se é um Ticket
    if (!($item instanceof Ticket)) {
        return;
    }

    global $DB;
    // Obtém o ID do ticket recém-criado
    $ticket_id = (int)$item->getID();
    
    // Se o ID for inválido (novo ticket ainda sem salvar), pula
    if ($ticket_id <= 0) {
        return;
    }

    debug_log("=== ITEM_ADD: Ticket #$ticket_id ===");

    /**
     * Captura e processa o valor do preço
     * Converte de formato brasileiro (1.234,56) para número (1234.56)
     * 
     * Processo:
     * 1. Remove pontos (separadores de milhares)
     * 2. Substitui vírgula por ponto (separador decimal brasileiro -> formato BD)
     * 3. Converte para float
     * 4. Padrão: 0 se não informado
     */
    $preco = isset($_POST['preco_custoticket']) 
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']) 
        : 0;
        
    // Captura os demais campos do formulário
    $descricao = $_POST['descricao_despesa'] ?? '';
    $moeda = $_POST['moeda'] ?? 'BRL';
    $tipo = $_POST['tipo_despesa'] ?? '';
    $data = $_POST['data_despesa'] ?? date('Y-m-d');
    $centro = $_POST['centro_custo'] ?? '';
    $rc = $_POST['rc'] ?? '';
    $oc = $_POST['oc'] ?? '';
    $projeto = $_POST['projeto'] ?? '';

    debug_log("ITEM_ADD: Preço=$preco, Descrição=$descricao");

    /**
     * Tenta inserir os dados na tabela de preços
     * A chave UNIQUE em tickets_id evita duplicatas
     */
    try {
        $DB->insert('glpi_plugin_custoticket_prices', [
            'tickets_id' => $ticket_id,
            'preco_atendimento' => $preco,
            'descricao_despesa' => $descricao,
            'moeda' => $moeda,
            'tipo_despesa' => $tipo,
            'data_despesa' => $data,
            'centro_custo' => $centro,
            'rc' => $rc,
            'oc' => $oc,
            'projeto' => $projeto,
            'date_creation' => date('Y-m-d H:i:s')
        ]);
        
        debug_log("ITEM_ADD: ✓ Sucesso");
    } catch (Exception $e) {
        // Log de erro caso a inserção falhe
        debug_log("ITEM_ADD ERROR: " . $e->getMessage());
    }
}

/**
 * Hook executado quando um ticket é ATUALIZADO
 * Atualiza os dados de custo ou insere se for a primeira vez
 * 
 * Este é o hook mais importante para persistir mudanças
 * Trata tanto updates quanto inserts (upsert)
 * 
 * @param Ticket $item O ticket sendo atualizado
 * 
 * @return void
 */
function plugin_custoticket_item_update($item) {
    debug_log("=== ITEM_UPDATE: INÍCIO ===");
    
    // Verifica se é um Ticket
    if (!($item instanceof Ticket)) {
        debug_log("ITEM_UPDATE: Não é Ticket");
        return;
    }

    global $DB;
    // Obtém o ID do ticket
    $ticket_id = (int)$item->getID();
    
    debug_log("ITEM_UPDATE: Ticket #$ticket_id");
    
    // ID inválido
    if ($ticket_id <= 0) {
        debug_log("ITEM_UPDATE: ID inválido");
        return;
    }

    /**
     * Verifica se pelo menos um campo de custo está no POST
     * Evita processar updates que não envolvem custo
     */
    if (!isset($_POST['preco_custoticket']) && !isset($_POST['descricao_despesa'])) {
        debug_log("ITEM_UPDATE: Campos custom não presentes no POST - pulando");
        return;
    }

    // Log do POST completo para debugging (ajuda a identificar problemas)
    debug_log("ITEM_UPDATE: POST completo: " . print_r($_POST, true));

    /**
     * Processa o preço (mesmo lógica do item_add)
     * Converte de formato brasileiro para número
     */
    $preco = isset($_POST['preco_custoticket']) 
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']) 
        : 0;
        
    /**
     * Validação adicional: se preço = 0 mas veio algo no POST,
     * pode ser um erro de parsing
     */
    if ($preco == 0 && isset($_POST['preco_custoticket']) && $_POST['preco_custoticket'] !== '' && $_POST['preco_custoticket'] !== '0,00') {
        debug_log("ITEM_UPDATE: Erro de parsing no preço - valor no POST: " . $_POST['preco_custoticket']);
    }
        
    // Captura demais campos
    $descricao = $_POST['descricao_despesa'] ?? '';
    $moeda = $_POST['moeda'] ?? 'BRL';
    $tipo = $_POST['tipo_despesa'] ?? '';
    $data = $_POST['data_despesa'] ?? date('Y-m-d');
    $centro = $_POST['centro_custo'] ?? '';
    $rc = $_POST['rc'] ?? '';
    $oc = $_POST['oc'] ?? '';
    $projeto = $_POST['projeto'] ?? '';
    
    debug_log("ITEM_UPDATE: Valores - Preço=$preco, Desc=$descricao");

    try {
        /**
         * Verifica se já existe um registro para este ticket
         * Count > 0 = existe (fazer UPDATE)
         * Count = 0 = não existe (fazer INSERT)
         */
        $result = $DB->request([
            'FROM' => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        if ($result->count() > 0) {
            // Registro existe: ATUALIZA
            debug_log("ITEM_UPDATE: Fazendo UPDATE");
            
            $success = $DB->update('glpi_plugin_custoticket_prices', [
                'preco_atendimento' => $preco,
                'descricao_despesa' => $descricao,
                'moeda' => $moeda,
                'tipo_despesa' => $tipo,
                'data_despesa' => $data,
                'centro_custo' => $centro,
                'rc' => $rc,
                'oc' => $oc,
                'projeto' => $projeto,
                'date_mod' => date('Y-m-d H:i:s')
            ], [
                'tickets_id' => $ticket_id
            ]);
            
            debug_log("ITEM_UPDATE: " . ($success ? "✓ UPDATE OK" : "✗ UPDATE FALHOU - " . $DB->error()));
        } else {
            // Registro não existe: INSERT
            debug_log("ITEM_UPDATE: Fazendo INSERT");
            
            $success = $DB->insert('glpi_plugin_custoticket_prices', [
                'tickets_id' => $ticket_id,
                'preco_atendimento' => $preco,
                'descricao_despesa' => $descricao,
                'moeda' => $moeda,
                'tipo_despesa' => $tipo,
                'data_despesa' => $data,
                'centro_custo' => $centro,
                'rc' => $rc,
                'oc' => $oc,
                'projeto' => $projeto,
                'date_creation' => date('Y-m-d H:i:s')
            ]);
            
            debug_log("ITEM_UPDATE: " . ($success ? "✓ INSERT OK" : "✗ INSERT FALHOU - " . $DB->error()));
        }
    } catch (Exception $e) {
        debug_log("ITEM_UPDATE ERROR: " . $e->getMessage());
    }
    
    debug_log("=== ITEM_UPDATE: FIM ===");
}

/**
 * Hook PRE-UPDATE
 * Executado ANTES do update principal do ticket
 * Usado para validações prévias e logging
 * 
 * @param Ticket $item O ticket antes de ser atualizado
 * 
 * @return void
 */
function plugin_custoticket_pre_item_update($item) {
    if (!($item instanceof Ticket)) {
        return;
    }

    global $DB;
    $ticket_id = (int)$item->getID();
    debug_log("=== PRE_ITEM_UPDATE: Ticket #$ticket_id ===");

    /**
     * Verifica se há campos custom no POST
     * Mesmo validação do item_update
     */
    if (!isset($_POST['preco_custoticket']) && !isset($_POST['descricao_despesa'])) {
        debug_log("PRE_ITEM_UPDATE: Campos custom não presentes - pulando");
        return;
    }

    // Log do POST para auditoria
    debug_log("PRE_ITEM_UPDATE: POST completo: " . print_r($_POST, true));

    /**
     * Validação de preço
     * Tenta fazer parse e registra se houver erro
     */
    if (isset($_POST['preco_custoticket'])) {
        $preco = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']);
        debug_log("PRE_ITEM_UPDATE: Preço detectado = $preco");
        
        // Validação: se resultado = 0 mas POST não vazio, erro de parsing
        if ($preco == 0 && $_POST['preco_custoticket'] !== '' && $_POST['preco_custoticket'] !== '0,00') {
            debug_log("PRE_ITEM_UPDATE: Erro de parsing no preço - valor no POST: " . $_POST['preco_custoticket']);
        }
    }

    // Validação adicional para descrição
    if (isset($_POST['descricao_despesa'])) {
        debug_log("PRE_ITEM_UPDATE: Descrição detectada = " . $_POST['descricao_despesa']);
    }

    debug_log("=== PRE_ITEM_UPDATE: FIM ===");
}
