<?php
// Proteção: evita acesso direto ao arquivo
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

// Classe que gerencia a aba de custo no ticket
class PluginCustoticketCustoTicket extends CommonDBTM {

    // Define o grupo de permissão para este plugin
    static $rightname = 'plugin_custoticket';

    // Retorna o nome da aba que aparecerá no ticket
    static function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        // Se for um Ticket, mostra a aba "Custo"
        if ($item->getType() == 'Ticket') {
            return __('Custo', 'custoticket');
        }
        // Caso contrário, retorna vazio (não exibe aba)
        return '';
    }

    // Exibe o conteúdo da aba de custo quando o usuário clica nela
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        // Verifica novamente se é um Ticket
        if ($item->getType() == 'Ticket') {
            // Chama a função que renderiza o formulário de custo
            self::showCustoForm($item);
        }
    }

    // Renderiza o formulário de custo usando template Twig
    static function showCustoForm(Ticket $ticket) {
        // Acessa a instância global do Twig para renderizar templates
        global $Twig;
        // Define o caminho do arquivo template
        $template = GLPI_ROOT."/plugins/custoticket/templates/custo-ticket.html.twig";
        // Verifica se o arquivo template existe
        if (file_exists($template)) {
            // Renderiza o template, passando o ticket como variável 'item'
            echo $Twig->render(
                'plugins/custoticket/templates/custo-ticket.html.twig',
                ['item' => $ticket]
            );
        } else {
            // Se template não existe, mostra mensagem de erro
            echo "<div class='alert alert-warning'>Template de custo não encontrado.</div>";
        }
    }
}
