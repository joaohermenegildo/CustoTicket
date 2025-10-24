<?php/**
 * Plugin CustoTicket - Classe principal
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginCustoticketCustoTicket extends CommonDBTM {

    static $rightname = 'plugin_custoticket';

    // Nome exibido na aba
    static function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Ticket') {
            return __('Custo', 'custoticket');
        }
        return '';
    }

    // ConteÃºdo da aba
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Ticket') {
            self::showCustoForm($item);
        }
    }

    // Renderiza o formulÃ¡rio via Twig
    static function showCustoForm(Ticket $ticket) {
        global $Twig;
        $template = GLPI_ROOT."/plugins/custoticket/templates/custo-ticket.html.twig";
        if (file_exists($template)) {
            echo $Twig->render(
                'plugins/custoticket/templates/custo-ticket.html.twig',
                ['item' => $ticket]
            );
        } else {
            echo "<div class='alert alert-warning'>Template de custo nÃ£o encontrado.</div>";
        }
    }
}
