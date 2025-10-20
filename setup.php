<?php
/**
 * CustoTicket - setup.php
 */

use Glpi\Plugin\Hooks;

define('PLUGIN_CUSTOTICKET_VERSION', '1.0.0');
define('PLUGIN_CUSTOTICKET_MIN_GLPI', '10.0.0');
define('PLUGIN_CUSTOTICKET_MAX_GLPI', '11.0.99');

function plugin_init_custoticket() {
    global $PLUGIN_HOOKS;

    // CSRF ok
    $PLUGIN_HOOKS['csrf_compliant']['custoticket'] = true;

    // Exibição do campo no formulário do Ticket
    $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['custoticket'] = [
        'Ticket' => 'plugin_custoticket_add_field'
    ];

    // Persistência
    $PLUGIN_HOOKS[Hooks::ITEM_ADD]['custoticket'] = [
        'Ticket' => 'plugin_custoticket_item_add'
    ];
    $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['custoticket'] = [
        'Ticket' => 'plugin_custoticket_item_update'
    ];
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
