<?php
/**
 * CustoTicket - setup.php
 * Arquivo de configuração e inicialização do plugin
 */

// Define a versão do plugin
define('PLUGIN_CUSTOTICKET_VERSION', '2.3.1');
// Define a versão mínima do GLPI que o plugin suporta
define('PLUGIN_CUSTOTICKET_MIN_GLPI', '10.0.0');
// Define a versão máxima do GLPI que o plugin suporta
define('PLUGIN_CUSTOTICKET_MAX_GLPI', '11.0.99');

// Função de inicialização do plugin - chamada quando o GLPI carrega o plugin
function plugin_init_custoticket() {
    // Acessa array global que armazena todos os hooks do sistema
    global $PLUGIN_HOOKS;

    // Define que o plugin é compatível com proteção CSRF do GLPI
    $PLUGIN_HOOKS['csrf_compliant']['custoticket'] = true;

    // Hook para adicionar campos personalizados no formulário do ticket
    // Chamado quando o formulário do ticket é renderizado
    $PLUGIN_HOOKS['post_item_form']['custoticket'] = 'plugin_custoticket_add_field';

    // Hook para quando um novo Ticket é criado
    // Sintaxe correta para GLPI 10+: array com ['ItemType' => 'função_callback']
    $PLUGIN_HOOKS['item_add']['custoticket'] = [
        'Ticket' => 'plugin_custoticket_item_add'
    ];
    
    // Hook para quando um Ticket é atualizado
    $PLUGIN_HOOKS['item_update']['custoticket'] = [
        'Ticket' => 'plugin_custoticket_item_update'
    ];

    // Hook para ANTES de atualizar um Ticket (útil para validações)
    $PLUGIN_HOOKS['pre_item_update']['custoticket'] = [
        'Ticket' => 'plugin_custoticket_pre_item_update'
    ];
}

// Função que retorna informações sobre o plugin
function plugin_version_custoticket() {
    return [
        'name'    => 'CustoTicket',  // Nome do plugin
        'version' => PLUGIN_CUSTOTICKET_VERSION,  // Versão atual
        'author'  => 'João Assis',  // Autor
        'license' => 'MIT',  // Tipo de licença
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_CUSTOTICKET_MIN_GLPI,  // Versão mínima do GLPI
                'max' => PLUGIN_CUSTOTICKET_MAX_GLPI   // Versão máxima do GLPI
            ]
        ],
    ];
}

// Função que verifica se os pré-requisitos estão instalados
function plugin_custoticket_check_prerequisites() {
    return true;
}

// Função que verifica a configuração do plugin
function plugin_custoticket_check_config($verbose = false) {
    return true;
}

// Função chamada quando o plugin é instalado
function plugin_custoticket_install() {
    // Inclui o arquivo hook.php que contém a função de criar tabelas
    include_once(__DIR__ . '/hook.php');
    // Chama a função que cria a tabela do banco de dados
    return plugin_custoticket_install_db();
}

// Função chamada quando o plugin é desinstalado
function plugin_custoticket_uninstall() {
    // Inclui o arquivo hook.php que contém a função de remover tabelas
    include_once(__DIR__ . '/hook.php');
    // Chama a função que remove a tabela do banco de dados
    return plugin_custoticket_uninstall_db();
}
