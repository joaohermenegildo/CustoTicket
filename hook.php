<?php
/**
 * CustoTicket - hook.php (VERSÃO DEBUG COM LOG EM /tmp)
 * 
 * Esta versão escreve logs em /tmp/custoticket_debug.log 
 * para funcionar mesmo sem o diretório de logs do GLPI
 */

// Definir arquivo de log temporário
define('DEBUG_FILE', '/tmp/custoticket_debug.log');

function debug_log($msg) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(DEBUG_FILE, "[$timestamp] $msg\n", FILE_APPEND);
}

// Funções de instalação
function plugin_custoticket_install_db() {
    global $DB;
    debug_log("CustoTicket: Iniciando instalação da tabela");
    
    if (!$DB->tableExists('glpi_plugin_custoticket_prices')) {
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
        
        if ($DB->query($query)) {
            debug_log("CustoTicket: Tabela criada com sucesso");
        } else {
            debug_log("CustoTicket: Erro ao criar tabela - " . $DB->error());
            return false;
        }
    } else {
        debug_log("CustoTicket: Tabela já existe");
    }
    
    return true;
}

function plugin_custoticket_uninstall_db() {
    global $DB;
    
    if ($DB->tableExists('glpi_plugin_custoticket_prices')) {
        $query = "DROP TABLE `glpi_plugin_custoticket_prices`";
        $DB->query($query);
        debug_log("CustoTicket: Tabela removida");
    }
    
    return true;
}

function plugin_custoticket_add_field($params) {
    debug_log("ADD_FIELD: Função chamada");
    
    $item = $params['item'] ?? null;
    if (!($item instanceof Ticket)) {
        debug_log("ADD_FIELD: Item não é um Ticket");
        return;
    }

    global $DB;
    $ticket_id = (int)$item->getID();
    
    debug_log("ADD_FIELD: Ticket ID = " . ($ticket_id > 0 ? $ticket_id : "NOVO"));
    
    // Valores padrão
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

    // Busca dados no banco se ticket já existe
    if ($ticket_id > 0) {
        $it = $DB->request([
            'FROM'  => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        foreach ($it as $row) {
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

    // Renderizar campos HTML
    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="preco_custoticket">Preço do Atendimento:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <div class="input-group">';
    echo '      <span class="input-group-text">R$</span>';
    echo '      <input type="text" class="form-control" name="preco_custoticket" id="preco_custoticket" value="' . $data['preco_atendimento'] . '" placeholder="0,00">';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';

    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="descricao_despesa">Descrição da despesa:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="descricao_despesa" id="descricao_despesa" value="' . $data['descricao_despesa'] . '" placeholder="Descreva a despesa">';
    echo '  </div>';
    echo '</div>';

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

    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="data_despesa">Data:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="date" class="form-control" name="data_despesa" id="data_despesa" value="' . $data['data_despesa'] . '">';
    echo '  </div>';
    echo '</div>';

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

    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="rc">RC:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="rc" id="rc" value="' . $data['rc'] . '" placeholder="RC">';
    echo '  </div>';
    echo '</div>';

    echo '<div class="form-field row col-12 mb-2">';
    echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="oc">OC:</label>';
    echo '  <div class="col-xxl-8 field-container">';
    echo '    <input type="text" class="form-control" name="oc" id="oc" value="' . $data['oc'] . '" placeholder="OC">';
    echo '  </div>';
    echo '</div>';

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

function plugin_custoticket_item_add($item) {
    if (!($item instanceof Ticket)) {
        return;
    }

    global $DB;
    $ticket_id = (int)$item->getID();
    
    if ($ticket_id <= 0) {
        return;
    }

    debug_log("=== ITEM_ADD: Ticket #$ticket_id ===");

    $preco = isset($_POST['preco_custoticket']) 
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']) 
        : 0;
        
    $descricao = $_POST['descricao_despesa'] ?? '';
    $moeda = $_POST['moeda'] ?? 'BRL';
    $tipo = $_POST['tipo_despesa'] ?? '';
    $data = $_POST['data_despesa'] ?? date('Y-m-d');
    $centro = $_POST['centro_custo'] ?? '';
    $rc = $_POST['rc'] ?? '';
    $oc = $_POST['oc'] ?? '';
    $projeto = $_POST['projeto'] ?? '';

    debug_log("ITEM_ADD: Preço=$preco, Descrição=$descricao");

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
        debug_log("ITEM_ADD ERROR: " . $e->getMessage());
    }
}

function plugin_custoticket_item_update($item) {
    debug_log("=== ITEM_UPDATE: INÍCIO ===");
    
    if (!($item instanceof Ticket)) {
        debug_log("ITEM_UPDATE: Não é Ticket");
        return;
    }

    global $DB;
    $ticket_id = (int)$item->getID();
    
    debug_log("ITEM_UPDATE: Ticket #$ticket_id");
    
    if ($ticket_id <= 0) {
        debug_log("ITEM_UPDATE: ID inválido");
        return;
    }

    // Verificar se pelo menos um campo custom está no POST (menos rígido para não pular atualizações válidas)
    if (!isset($_POST['preco_custoticket']) && !isset($_POST['descricao_despesa'])) {
        debug_log("ITEM_UPDATE: Campos custom não presentes no POST - pulando");
        return;
    }

    // Logar o POST completo para debug (ajuda a ver se 'descricao_despesa' e outros estão chegando)
    debug_log("ITEM_UPDATE: POST completo: " . print_r($_POST, true));

    $preco = isset($_POST['preco_custoticket']) 
        ? (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']) 
        : 0;
        
    // Verificação extra para erro de parsing no preço
    if ($preco == 0 && isset($_POST['preco_custoticket']) && $_POST['preco_custoticket'] !== '' && $_POST['preco_custoticket'] !== '0,00') {
        debug_log("ITEM_UPDATE: Erro de parsing no preço - valor no POST: " . $_POST['preco_custoticket']);
    }
        
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
        $result = $DB->request([
            'FROM' => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        if ($result->count() > 0) {
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

function plugin_custoticket_pre_item_update($item) {
    if (!($item instanceof Ticket)) {
        return;
    }

    global $DB;
    $ticket_id = (int)$item->getID();
    debug_log("=== PRE_ITEM_UPDATE: Ticket #$ticket_id ===");

    // Verificar se pelo menos um campo custom está no POST (igual ao item_update)
    if (!isset($_POST['preco_custoticket']) && !isset($_POST['descricao_despesa'])) {
        debug_log("PRE_ITEM_UPDATE: Campos custom não presentes - pulando");
        return;
    }

    // Logar o POST completo para debug
    debug_log("PRE_ITEM_UPDATE: POST completo: " . print_r($_POST, true));

    // Aqui você pode validar dados antes do update principal.
    // Por exemplo, parsear o preço cedo:
    if (isset($_POST['preco_custoticket'])) {
        $preco = (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custoticket']);
        debug_log("PRE_ITEM_UPDATE: Preço detectado = $preco");
        // Verificação extra para erro de parsing
        if ($preco == 0 && $_POST['preco_custoticket'] !== '' && $_POST['preco_custoticket'] !== '0,00') {
            debug_log("PRE_ITEM_UPDATE: Erro de parsing no preço - valor no POST: " . $_POST['preco_custoticket']);
        }
    }

    // Você pode adicionar mais validações para outros campos como 'descricao_despesa' se precisar
    if (isset($_POST['descricao_despesa'])) {
        debug_log("PRE_ITEM_UPDATE: Descrição detectada = " . $_POST['descricao_despesa']);
    }

    debug_log("=== PRE_ITEM_UPDATE: FIM ===");
}