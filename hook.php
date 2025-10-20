<?php
/**
 * CustoTicket - hook.php
 */

if (!function_exists('plugin_custoticket_install')) {
    function plugin_custoticket_install() {
        global $DB;
        $DB->queryOrDie(
            "CREATE TABLE IF NOT EXISTS `glpi_plugin_custoticket_prices` (
               `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
               `tickets_id` INT UNSIGNED NOT NULL DEFAULT '0',
               `preco` DECIMAL(20,4) NOT NULL DEFAULT '0.0000',
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

/**
 * POST_ITEM_FORM: imprime o campo no formulário do Ticket
 * Recebe $params com ['item'=>Ticket, 'options'=>...]
 */
if (!function_exists('plugin_custoticket_add_field')) {
    \Toolbox::logDebug('CUSTOTICKET_HIT', date('c'));
    function plugin_custoticket_add_field($params) {
        $item = $params['item'] ?? null;
        if (!($item instanceof Ticket)) {
            return;
        }

        global $DB;

        $ticket_id = (int)$item->getID();
        $preco = 0.00;

        if ($ticket_id > 0) {
            $it = $DB->request([
                'FROM'  => 'glpi_plugin_custoticket_prices',
                'WHERE' => ['tickets_id' => $ticket_id]
            ]);

            foreach ($it as $row) {
                $preco = (float)$row['preco'];
                break;
            }
        }

        $preco_fmt = number_format($preco, 2, ',', '.');

        // Linha compatível com o renderer de formulário de Ticket
        echo '<tr class="tab_bg_1">';
        echo '  <td><label for="preco_custoticket">Preço do Atendimento:</label></td>';
        echo '  <td>';
        echo '    <div class="input-group" style="max-width:220px;">';
        echo '      <span class="input-group-text">R$</span>';
        echo '      <input type="text" class="form-control" name="preco_custoticket" id="preco_custoticket" value="' . htmlspecialchars($preco_fmt, ENT_QUOTES) . '" placeholder="0,00">';
        echo '    </div>';
        echo '  </td>';
        echo '  <td colspan="2"></td>';
        echo '</tr>';

        // Máscara simples de formatação
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var input = document.getElementById("preco_custoticket");
            if (!input) return;
            input.addEventListener("blur", function() {
                var v = parseFloat(String(this.value).replace(".", "").replace(",", ".")) || 0;
                this.value = v.toFixed(2).replace(".", ",");
            });
        });
        </script>';
    }
}

/**
 * ITEM_ADD: salva preço quando o ticket é criado
 */
if (!function_exists('plugin_custoticket_item_add')) {
    function plugin_custoticket_item_add($item) {
        if (!($item instanceof Ticket)) return;
        if (!isset($_POST['preco_custoticket'])) return;
        plugin_custoticket_save_price((int)$item->getID(), $_POST['preco_custoticket']);
    }
}

/**
 * ITEM_UPDATE: salva preço quando o ticket é atualizado
 */
if (!function_exists('plugin_custoticket_item_update')) {
    function plugin_custoticket_item_update($item) {
        if (!($item instanceof Ticket)) return;
        if (!isset($_POST['preco_custoticket'])) return;

        plugin_custoticket_save_price((int)$item->getID(), $_POST['preco_custoticket']);

        if (isset($item->input['status']) && $item->input['status'] == CommonITILObject::SOLVED) {
            plugin_custoticket_add_to_solution($item);
        }
    }
}

/**
 * Persistência (API $DB moderna)
 */
if (!function_exists('plugin_custoticket_save_price')) {
    function plugin_custoticket_save_price($ticket_id, $price_raw) {
        global $DB;

        // Normaliza "1.234,56" -> 1234.56
        $clean = str_replace(['.', ','], ['', '.'], trim((string)$price_raw));
        $val = (float)$clean;

        // Já existe?
        $it = $DB->request([
            'FROM'  => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        $exists = false;
        foreach ($it as $row) { $exists = true; break; }

        if ($exists) {
            $DB->update(
                'glpi_plugin_custoticket_prices',
                ['preco' => $val, 'date_mod' => date('Y-m-d H:i:s')],
                ['tickets_id' => $ticket_id]
            );
        } else {
            $DB->insert(
                'glpi_plugin_custoticket_prices',
                [
                    'tickets_id'    => $ticket_id,
                    'preco'         => $val,
                    'date_creation' => date('Y-m-d H:i:s'),
                    'date_mod'      => date('Y-m-d H:i:s')
                ]
            );
        }
    }
}

/**
 * Anexa preço na última solução se o ticket for resolvido
 */
if (!function_exists('plugin_custoticket_add_to_solution')) {
    function plugin_custoticket_add_to_solution($ticket) {
        global $DB;

        $ticket_id = (int)$ticket->getID();

        $it = $DB->request([
            'FROM'  => 'glpi_plugin_custoticket_prices',
            'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        $preco = 0.0;
        foreach ($it as $row) { $preco = (float)$row['preco']; break; }
        if ($preco <= 0) return;

        $fmt = 'R$ ' . number_format($preco, 2, ',', '.');

        $sol = $DB->request([
            'FROM'  => 'glpi_itilsolutions',
            'WHERE' => ['items_id' => $ticket_id, 'itemtype' => 'Ticket'],
            'ORDER' => 'date_creation DESC',
            'LIMIT' => 1
        ]);

        foreach ($sol as $s) {
            if (strpos($s['content'], '**Valor do Atendimento:**') !== false) return;
            $new = $s['content'] . "\n\n**Valor do Atendimento:** " . $fmt;
            $DB->update('glpi_itilsolutions', ['content' => $new], ['id' => (int)$s['id']]);
        }
    }
}
