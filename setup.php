<?php
/**
 * CustoTicket - setup.php
 */

use glpi\plugin\hook;



define('PLUGIN_CUSTOTICKET_VERSION', '1.2.2');
define('PLUGIN_CUSTOTICKET_MIN_GLPI', '10.0.0');
define('PLUGIN_CUSTOTICKET_MAX_GLPI', '11.0.99');

function plugin_init_custoticket() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['custoticket'] = true;

    // Outros hooks...
    $PLUGIN_HOOKS['post_item_form']['custoticket']   = 'plugin_custoticket_add_field';
    $PLUGIN_HOOKS['item_add']['custoticket']        = ['Ticket' => 'plugin_custoticket_item_add'];
    $PLUGIN_HOOKS['item_update']['custoticket']     = ['Ticket' => 'plugin_custoticket_item_update'];

    // Novo hook para aba de custos
  //  $PLUGIN_HOOKS['add_tab']['custoticket']         = ['Ticket' => 'PluginCustoticketCustoTicket'];
    //$PLUGIN_HOOKS['add_tab_content']['custoticket'] = ['Ticket' => 'PluginCustoticketCustoTicket'];
}


function plugin_version_custoticket() {
    return [
        'name'    => 'CustoTicket',
        'version' => PLUGIN_CUSTOTICKET_VERSION,
        'author'  => 'João Assis',
        'license' => 'MIT',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_CUSTOTICKET_MIN_GLPI,
                'max' => PLUGIN_CUSTOTICKET_MAX_GLPI
            ]
        ],
    ];
}

function plugin_custoticket_check_prerequisites() {
    return true;
}

function plugin_custoticket_check_config($verbose = false) {
    return true;
}
