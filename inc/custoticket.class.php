<?php
/**
 * CustoTicket - hook.php
 * Contém as funções de instalação, renderização e persistência de dados
 */

// Define o arquivo de log onde debug será salvo
define('DEBUG_FILE', '/tmp/custoticket_debug.log');

// Função auxiliar para registrar mensagens de debug com timestamp
function debug_log($msg) {
    // Formata o timestamp atual
    $timestamp = date('Y-m-d H:i:s');
    // Escreve a mensagem no arquivo de log (FILE_APPEND = append, não sobrescreve)
    file_put_contents(DEBUG_FILE, "[$timestamp] $msg\n", FILE_APPEND);
}

// ============= FUNÇÕES DE INSTALAÇÃO =============

// Função de instalação - cria a tabela no banco de dados
function plugin_custoticket_install_db() {
    // Acessa objeto global do banco de dados do GLPI
    global $DB;
    debug_log("CustoTicket: Iniciando instalação da tabela");
    
    // Verifica se a tabela já existe
    if (!$DB->tableExists('glpi_plugin_custoticket_prices')) {
        // Define o SQL para criar a tabela
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
            // Log de sucesso
            debug_log("CustoTicket: Tabela criada com sucesso");
        } else {
            // Log de erro
            debug_log("CustoTicket: Erro ao criar tabela - " . $DB->error());
            return false;
        }
    } else {
        // Se tabela já existe, apenas registra no log
        debug_log("CustoTicket: Tabela já existe");
    }
    
    return true;
}

// Função de desinstalação - remove a tabela do banco de dados
function plugin_custoticket_uninstall_db() {
    global $DB;
    
    // Verifica se a tabela existe
    if ($DB->tableExists('glpi_plugin_custoticket_prices')) {
        // SQL para deletar a tabela
        $query = "DROP TABLE `glpi_plugin_custoticket_prices`";
        $DB->query($query);
        debug_log("CustoTicket: Tabela removida");
    }
    
    return true;
}

// ============= FUNÇÕES DE RENDERIZAÇÃO E PERSISTÊNCIA =============

// Hook executado quando um formulário de ticket é exibido
// Renderiza os campos de custo no formulário
function plugin_custoticket_add_field($params) {
    debug_log("ADD_FIELD: Função chamada");
    
    // Extrai o item (Ticket) dos parâmetros
    $item = $params['item'] ?? null;
    // Verifica se o item é realmente um Ticket
    if (!($item instanceof Ticket)) {
        debug_log("ADD_FIELD: Item não é um Ticket");
        return;
    }

    global $DB;
    // Obtém o ID do ticket (> 0 se existe, <= 0 se é novo)
    $ticket_id = (int)$item->getID();
    
    debug_log("ADD_FIELD: Ticket ID = " . ($ticket_id > 0 ? $ticket_id : "NOVO"));
    
    // Define valores padrão para todos os campos
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

    // Se ticket já existe, busca dados anteriores no banco
    if ($ticket_id > 0) {
        // Faz requisição ao banco para buscar dados do ticket
        $it = $DB->request([
            'FROM'  => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        // Itera sobre os resultados (deve haver apenas um por causa da UNIQUE KEY)
        foreach ($it as $row) {
            // Formata os valores para exibição
            $data = [
                // Converte preço numérico para formato brasileiro (1.234,56)
                'preco_atendimento' => number_format((float)$row['preco_atendimento'], 2, ',', '.'),
                // Sanitiza contra XSS
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
            break;  // Para porque há apenas um registro por ticket
        }
    }

    // ========== RENDERIZAÇÃO DOS CAMPOS HTML ==========
    
    // Campo: Preço do Atendimento
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="preco_custoticket">Preço do Atendimento:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <div class="input-group">';
    echo '      <span class="input-group-text">R$</span>';
    echo '      <input type="text" class="form-control" name="preco_custoticket" id="preco_custoticket" value="' . $data['preco_atendimento'] . '" placeholder="0,00">';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';

    // Campo: Descrição da Despesa
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="descricao_despesa">Descrição da despesa:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="descricao_despesa" id="descricao_despesa" value="' . $data['descricao_despesa'] . '" placeholder="Descreva a despesa">';
    echo '  </div>';
    echo '</div>';

    // Campo: Moeda
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="moeda">Moeda:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <select name="moeda" id="moeda" class="form-select">';
    // Define opções de moeda, marcando como selected a que estava salva
    echo '      <option value="BRL" ' . ($data['moeda'] === 'BRL' ? 'selected' : '') . '>BRL (R$)</option>';
    echo '      <option value="USD" ' . ($data['moeda'] === 'USD' ? 'selected' : '') . '>USD ($)</option>';
    echo '      <option value="EUR" ' . ($data['moeda'] === 'EUR' ? 'selected' : '') . '>EUR (€)</option>';
    echo '    </select>';
    echo '  </div>';
    echo '</div>';

    // Campo: Tipo de Despesa (categoria)
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

    // Campo: Data da Despesa
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="data_despesa">Data:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="date" class="form-control" name="data_despesa" id="data_despesa" value="' . $data['data_despesa'] . '">';
    echo '  </div>';
    echo '</div>';

    // Campo: Centro de Custos
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

    // Campo: RC (Responsabilidade de Custo)
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="rc">RC:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="rc" id="rc" value="' . $data['rc'] . '" placeholder="RC">';
    echo '  </div>';
    echo '</div>';

    // Campo: OC (Ordem de Compra)
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="oc">OC:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="oc" id="oc" value="' . $data['oc'] . '" placeholder="OC">';
    echo '  </div>';
    echo '</div>';

    // Campo: Projeto
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

    // JavaScript para formatar o preço automaticamente quando o usuário sai do campo
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '  const input = document.getElementById("preco_custoticket");';
    echo '  if (input) {';
    echo '    // Quando o usuário sai do campo de preço (evento blur)';
    echo '    input.addEventListener("blur", function() {';
    echo '      // Converte string com formato brasileiro para número (remove pontos, substitui vírgula por ponto)';
    echo '      const v = parseFloat(this.value.replace(/\\./g, "").replace(",", ".")) || 0;';
    echo '      // Formata de volta para 2 casas decimais e converte ponto para vírgula';
    echo '      this.value = v.toFixed(2).replace(".", ",");';
    echo '    });';
    echo '  }';
    echo '});';
    echo '</script>';
    
    debug_log("ADD_FIELD: Campos renderizados com sucesso");
}

// Hook executado APÓS um novo Ticket ser criado
// Insere os dados de custo na tabela
function plugin_custoticket_item_add($item) {
    // Verifica se é um Ticket
    if (!($item instanceof Ticket)) {
        return;
    }

    global $DB;
    // Obtém o ID do ticket recém-criado
    $ticket_id = (int)$item->getID();
    
    // Se ID é inválido, não faz nada
    if ($ticket_id <= 0) {
        return;
    }

    debug_log("=== ITEM_ADD: Ticket #$ticket_id ===");

    // Captura e converte o preço de formato brasileiro para número
    // Remove pontos (separadores de milhares) e substitui vírgula por ponto (formato de BD)
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

    // Tenta inserir os dados na tabela
    try {
        // Usa função parametrizada do GLPI (segura contra SQL injection)
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
        // Log do erro se a inserção falhar
        debug_log("ITEM_ADD ERROR: " . $e->getMessage());
    }
}

// Hook executado APÓS um Ticket ser atualizado
// Atualiza os dados de custo ou insere se for a primeira vez
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
    
    // Se ID é inválido, não faz nada
    if ($ticket_id <= 0) {
        debug_log("ITEM_UPDATE: ID inválido");
        return;
    }

    // Verifica se pelo menos um campo de custo está no POST
    // Evita processar updates que não envolvem custo
    if (!isset($_POST['preco_custoticket']) && !isset($_POST['descricao_despesa'])) {
        debug_log("ITEM_UPDATE: Campos custom não presentes no POST - pulando");
        return;
    }

    // Log do POST completo para debug (ajuda a identificar problemas)
    debug_log("ITEM_UPDATE: POST completo: " . print_r($_POST, true));

    // Processa o preço (mesma conversão do item_add)
    $preco = isset($_POST['preco_custoticket']) 
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']) 
        : 0;
        
    // Verificação extra: se resultado = 0 mas POST não vazio, pode ter erro de parsing
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
        // Verifica se já existe um registro para este ticket
        $result = $DB->request([
            'FROM' => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        // Se existe registro
        if ($result->count() > 0) {
            // Faz UPDATE dos dados existentes
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
            // Se não existe, faz INSERT novo
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

// Hook executado ANTES de atualizar o Ticket
// Útil para validações prévias e logging
function plugin_custoticket_pre_item_update($item) {
    // Verifica se é um Ticket
    if (!($item instanceof Ticket)) {
        return;
    }

    global $DB;
    $ticket_id = (int)$item->getID();
    debug_log("=== PRE_ITEM_UPDATE: Ticket #$ticket_id ===");

    // Verifica se há campos custom no POST (mesma lógica do item_update)
    if (!isset($_POST['preco_custoticket']) && !isset($_POST['descricao_despesa'])) {
        debug_log("PRE_ITEM_UPDATE: Campos custom não presentes - pulando");
        return;
    }

    // Log do POST para auditoria
    debug_log("PRE_ITEM_UPDATE: POST completo: " . print_r($_POST, true));

    // Validação de preço - tenta fazer parse e registra se houver erro
    if (isset($_POST['preco_custoticket'])) {
        // Converte preço para número
        $preco = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']);
        debug_log("PRE_ITEM_UPDATE: Preço detectado = $preco");
        
        // Validação extra: se resultado = 0 mas POST não vazio, erro de parsing
        if ($preco == 0 && $_POST['preco_custoticket'] !== '' && $_POST['preco_custoticket'] !== '0,00') {
            debug_log("PRE_ITEM_UPDATE: Erro de parsing no preço - valor no POST: " . $_POST['preco_custoticket']);
        }
    }

    // Validação de descrição - apenas registra se foi detectada
    if (isset($_POST['descricao_despesa'])) {
        debug_log("PRE_ITEM_UPDATE: Descrição detectada = " . $_POST['descricao_despesa']);
    }

    debug_log("=== PRE_ITEM_UPDATE: FIM ===");
}
