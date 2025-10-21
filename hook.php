<?php
/**
 * CustoTicket - hook.php (Completo)
 */

if (!function_exists('plugin_custoticket_install')) {
    function plugin_custoticket_install() {
        global $DB;
        $DB->queryOrDie(
            "CREATE TABLE IF NOT EXISTS `glpi_plugin_custoticket_prices` (
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
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            $DB->error()
        );
        return true;
    }
}

if (!function_exists('plugin_custoticket_uninstall')) {
    function plugin_custoticket_uninstall() {
        global $DB;
        $DB->queryOrDie("DROP TABLE IF EXISTS `glpi_plugin_custoticket_prices`;", $DB->error());
        return true;
    }
}

if (!function_exists('plugin_custoticket_add_field')) {
    function plugin_custoticket_add_field($params) {
        $item = $params['item'] ?? null;
        if (!($item instanceof Ticket)) {
            return;
        }

        global $DB;

        $ticket_id = (int)$item->getID();
        
        // Valores padrão
        $data = [
            'preco_atendimento' => '0.00',
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
                break;
            }
        }

        // Preço do Atendimento
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="preco_custoticket">';
        echo '    Preço do Atendimento:';
        echo '  </label>';
        echo '  <div class="col-xxl-8 field-container">';
        echo '    <div class="input-group">';
        echo '      <span class="input-group-text">R$</span>';
        echo '      <input type="text" class="form-control" name="preco_custoticket" id="preco_custoticket" value="' . $data['preco_atendimento'] . '" placeholder="0,00">';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';

        // Descrição da despesa
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="descricao_despesa">';
        echo '    Descrição da despesa:';
        echo '  </label>';
        echo '  <div class="col-xxl-8 field-container">';
        echo '    <input type="text" class="form-control" name="descricao_despesa" id="descricao_despesa" value="' . $data['descricao_despesa'] . '" placeholder="Descreva a despesa">';
        echo '  </div>';
        echo '</div>';

        // Moeda
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="moeda">';
        echo '    Moeda:';
        echo '  </label>';
        echo '  <div class="col-xxl-8 field-container">';
        echo '    <select name="moeda" id="moeda" class="form-select">';
        echo '      <option value="BRL" ' . ($data['moeda'] === 'BRL' ? 'selected' : '') . '>BRL (R$)</option>';
        echo '      <option value="USD" ' . ($data['moeda'] === 'USD' ? 'selected' : '') . '>USD ($)</option>';
        echo '      <option value="EUR" ' . ($data['moeda'] === 'EUR' ? 'selected' : '') . '>EUR (€)</option>';
        echo '    </select>';
        echo '  </div>';
        echo '</div>';



        // Tipo
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="tipo_despesa">';
        echo '    Tipo:';
        echo '  </label>';
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

        // Data
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="data_despesa">';
        echo '    Data:';
        echo '  </label>';
        echo '  <div class="col-xxl-8 field-container">';
        echo '    <input type="date" class="form-control" name="data_despesa" id="data_despesa" value="' . $data['data_despesa'] . '">';
        echo '  </div>';
        echo '</div>';

        // Centro de custos
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="centro_custo">';
        echo '    Centro de custos:';
        echo '  </label>';
        echo '  <div class="col-xxl-8 field-container">';
        echo '    <select name="centro_custo" id="centro_custo" class="form-select">';
        echo '      <option value="" ' . ($data['centro_custo'] === '' ? 'selected' : '') . '>-----</option>';
        echo '      <option value="servicos" ' . ($data['centro_custo'] === 'servicos' ? 'selected' : '') . '>Serviços continuados</option>';
        echo '      <option value="obras" ' . ($data['centro_custo'] === 'obras' ? 'selected' : '') . '>Obras</option>';
        echo '      <option value="projetos" ' . ($data['centro_custo'] === 'projetos' ? 'selected' : '') . '>Projetos estratégicos</option>';
        echo '    </select>';
        echo '  </div>';
        echo '</div>';

        // RC
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="rc">';
        echo '    RC:';
        echo '  </label>';
        echo '  <div class="col-xxl-8 field-container">';
        echo '    <input type="text" class="form-control" name="rc" id="rc" value="' . $data['rc'] . '" placeholder="RC">';
        echo '  </div>';
        echo '</div>';

        // OC
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="oc">';
        echo '    OC:';
        echo '  </label>';
        echo '  <div class="col-xxl-8 field-container">';
        echo '    <input type="text" class="form-control" name="oc" id="oc" value="' . $data['oc'] . '" placeholder="OC">';
        echo '  </div>';
        echo '</div>';

        // Projeto
        echo '<div class="form-field row col-12 mb-2">';
        echo '  <label class="col-form-label col-xxl-4 text-xxl-end" for="projeto">';
        echo '    Projeto:';
        echo '  </label>';
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

        // Script de formatação
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo '  const campos = ["preco_custoticket", "valor_despesa"];';
        echo '  campos.forEach(id => {';
        echo '    const input = document.getElementById(id);';
        echo '    if (input) {';
        echo '      input.addEventListener("blur", function() {';
        echo '        const v = parseFloat(this.value.replace(/\./g, "").replace(",", ".")) || 0;';
        echo '        this.value = v.toFixed(2).replace(".", ",");';
        echo '      });';
        echo '    }';
        echo '  });';
        echo '});';
        echo '</script>';
    }
}

if (!function_exists('plugin_custoticket_item_add')) {
    function plugin_custoticket_item_add($params) {
        return plugin_custoticket_save_data($params);
    }
}

if (!function_exists('plugin_custoticket_item_update')) {
    function plugin_custoticket_item_update($params) {
        return plugin_custoticket_save_data($params);
    }
}

if (!function_exists('plugin_custoticket_save_data')) {
    function plugin_custoticket_save_data($params) {
        global $DB;
        
        $item = $params['item'] ?? null;
        if (!($item instanceof Ticket)) {
            return;
        }

        $ticket_id = (int)$item->getID();
        if ($ticket_id <= 0) {
            return;
        }

        // Pega os valores dos campos do formulário
        $preco_atendimento = isset($_POST['preco_custoticket']) ? (float)str_replace(',', '.', $_POST['preco_custoticket']) : 0;
        $descricao_despesa = $_POST['descricao_despesa'] ?? '';
        $moeda = $_POST['moeda'] ?? 'BRL';
        $tipo_despesa = $_POST['tipo_despesa'] ?? '';
        $data_despesa = $_POST['data_despesa'] ?? date('Y-m-d');
        $centro_custo = $_POST['centro_custo'] ?? '';
        $rc = $_POST['rc'] ?? '';
        $oc = $_POST['oc'] ?? '';
        $projeto = $_POST['projeto'] ?? '';

        // Verifica se já existe registro para este ticket
        $existing = $DB->request([
            'FROM'  => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        if (count($existing) > 0) {
            // UPDATE
            $DB->update('glpi_plugin_custoticket_prices', [
                'preco_atendimento' => $preco_atendimento,
                'descricao_despesa' => $descricao_despesa,
                'moeda' => $moeda,
                
                'tipo_despesa' => $tipo_despesa,
                'data_despesa' => $data_despesa,
                'centro_custo' => $centro_custo,
                'rc' => $rc,
                'oc' => $oc,
                'projeto' => $projeto,
                'date_mod' => date('Y-m-d H:i:s')
            ], [
                'tickets_id' => $ticket_id
            ]);
        } else {
            // INSERT
            $DB->insert('glpi_plugin_custoticket_prices', [
                'tickets_id' => $ticket_id,
                'preco_atendimento' => $preco_atendimento,
                'descricao_despesa' => $descricao_despesa,
                'moeda' => $moeda,
                
                'tipo_despesa' => $tipo_despesa,
                'data_despesa' => $data_despesa,
                'centro_custo' => $centro_custo,
                'rc' => $rc,
                'oc' => $oc,
                'projeto' => $projeto,
                'date_creation' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
