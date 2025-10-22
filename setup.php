<?php
/**
 * CustoTicket - setup.php (CORRIGIDO)
 * 
 * PRINCIPAIS CORREÇÕES:
 * 1. Sintaxe correta dos hooks (item_add ao invés de item_add:after)
 * 2. Funções install/uninstall que realmente criam/removem tabelas
 * 3. Remoção de código desnecessário
 */

define('PLUGIN_CUSTOTICKET_VERSION', '2.3.1');
define('PLUGIN_CUSTOTICKET_MIN_GLPI', '10.0.0');
define('PLUGIN_CUSTOTICKET_MAX_GLPI', '11.0.99');

function plugin_init_custoticket() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['custoticket'] = true;

    // Hook para adicionar campos no formulário
    $PLUGIN_HOOKS['post_item_form']['custoticket'] = 'plugin_custoticket_add_field';

    // CORREÇÃO: Hooks para salvar dados - sintaxe correta para GLPI 10+
    // Antes estava: $PLUGIN_HOOKS['item_add:after']['Ticket']['custoticket']
    // Correto é: array com itemtype => callback
    $PLUGIN_HOOKS['item_add']['custoticket'] = [
        'Ticket' => 'plugin_custoticket_item_add'
    ];
    
    $PLUGIN_HOOKS['item_update']['custoticket'] = [
        'Ticket' => 'plugin_custoticket_item_update'
    ];

    $PLUGIN_HOOKS['pre_item_update']['custoticket'] = [
    'Ticket' => 'plugin_custoticket_pre_item_update'
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

// CORREÇÃO: Chamar a função de instalação do hook.php
// Antes: apenas "return true" sem fazer nada
function plugin_custoticket_install() {
    include_once(__DIR__ . '/hook.php');
    return plugin_custoticket_install_db();
}

// CORREÇÃO: Chamar a função de desinstalação do hook.php
function plugin_custoticket_uninstall() {
    include_once(__DIR__ . '/hook.php');
    return plugin_custoticket_uninstall_db();
}
