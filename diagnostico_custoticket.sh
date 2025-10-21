#!/bin/bash
# Script de Diagnóstico Automático - Plugin CustoTicket
# Execute: bash diagnostico_custoticket.sh

echo "🔧 DIAGNÓSTICO AUTOMÁTICO - Plugin CustoTicket"
echo "=================================================="
echo "Data: $(date)"
echo

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para logs
log_info() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# ETAPA 1: Verificação do ambiente
echo "📋 ETAPA 1: VERIFICAÇÃO DO AMBIENTE"
echo "-----------------------------------"

# Verificar se é root ou tem sudo
if [[ $EUID -eq 0 ]]; then
    log_info "Executando como root"
    SUDO=""
else
    log_warning "Executando como usuário normal - alguns comandos podem precisar de sudo"
    SUDO="sudo"
fi

# Detectar caminho do GLPI
GLPI_PATHS=("/var/www/html/glpi" "/var/www/glpi" "/opt/glpi" "/usr/share/glpi")
GLPI_PATH=""

for path in "${GLPI_PATHS[@]}"; do
    if [ -d "$path" ]; then
        GLPI_PATH="$path"
        log_info "GLPI encontrado em: $path"
        break
    fi
done

if [ -z "$GLPI_PATH" ]; then
    log_error "GLPI não encontrado nos caminhos padrão"
    echo "Por favor, informe o caminho do GLPI:"
    read -p "Caminho: " GLPI_PATH
fi

PLUGIN_PATH="$GLPI_PATH/plugins/custoticket"

# Verificar se plugin existe
if [ -d "$PLUGIN_PATH" ]; then
    log_info "Pasta do plugin encontrada: $PLUGIN_PATH"
else
    log_error "Pasta do plugin NÃO encontrada: $PLUGIN_PATH"
    echo "Verifique se os arquivos foram extraídos corretamente"
fi

# Verificar arquivos do plugin
echo
echo "📁 VERIFICAÇÃO DE ARQUIVOS:"
for file in "setup.php" "hook.php" "css/custoticket.css"; do
    if [ -f "$PLUGIN_PATH/$file" ]; then
        log_info "$file existe"
    else
        log_error "$file NÃO existe"
    fi
done

# Verificar permissões
echo
echo "🔐 VERIFICAÇÃO DE PERMISSÕES:"
if [ -d "$PLUGIN_PATH" ]; then
    OWNER=$(stat -c '%U:%G' "$PLUGIN_PATH")
    PERMS=$(stat -c '%a' "$PLUGIN_PATH")
    echo "Proprietário: $OWNER"
    echo "Permissões: $PERMS"

    if [ "$OWNER" = "www-data:www-data" ] || [ "$OWNER" = "apache:apache" ]; then
        log_info "Proprietário correto"
    else
        log_warning "Proprietário pode estar incorreto. Esperado: www-data:www-data ou apache:apache"
    fi
fi

# ETAPA 2: Verificação do banco de dados
echo
echo "🗄️  ETAPA 2: VERIFICAÇÃO DO BANCO DE DADOS"
echo "----------------------------------------"

# Tentar encontrar arquivo de configuração
CONFIG_FILES=("$GLPI_PATH/config/config_db.php" "$GLPI_PATH/inc/config.php")
DB_CONFIG=""

for config in "${CONFIG_FILES[@]}"; do
    if [ -f "$config" ]; then
        DB_CONFIG="$config"
        log_info "Arquivo de configuração encontrado: $config"
        break
    fi
done

if [ -n "$DB_CONFIG" ]; then
    # Extrair informações do banco
    DB_HOST=$(grep "db_host" "$DB_CONFIG" | sed "s/.*['\"]\([^'\"]*\)['\"].*/\1/")
    DB_NAME=$(grep "db_name" "$DB_CONFIG" | sed "s/.*['\"]\([^'\"]*\)['\"].*/\1/")
    DB_USER=$(grep "db_user" "$DB_CONFIG" | sed "s/.*['\"]\([^'\"]*\)['\"].*/\1/")

    echo "Host: $DB_HOST"
    echo "Database: $DB_NAME"
    echo "User: $DB_USER"

    # Verificar se tabela existe
    echo "Verificando se a tabela do plugin existe..."
    MYSQL_CMD="mysql -h$DB_HOST -u$DB_USER -p$DB_NAME -e"
    echo "Execute manualmente:"
    echo "$MYSQL_CMD "SHOW TABLES LIKE 'glpi_plugin_custoticket_prices';""
else
    log_error "Arquivo de configuração do banco não encontrado"
fi

# ETAPA 3: Verificação de logs
echo
echo "📋 ETAPA 3: VERIFICAÇÃO DE LOGS"
echo "-----------------------------"

LOG_PATHS=(
    "$GLPI_PATH/files/_log/php-errors.log"
    "/var/log/apache2/error.log"
    "/var/log/nginx/error.log"
    "/var/log/php-fpm.log"
    "/var/log/php7.4/fpm.log"
    "/var/log/php8.0/fpm.log"
    "/var/log/php8.1/fpm.log"
    "/var/log/php8.2/fpm.log"
)

echo "Procurando logs de erro..."
for logpath in "${LOG_PATHS[@]}"; do
    if [ -f "$logpath" ]; then
        log_info "Log encontrado: $logpath"
        echo "Últimas 5 linhas:"
        tail -5 "$logpath" 2>/dev/null | head -5
        echo
    fi
done

# ETAPA 4: Verificação de serviços
echo
echo "⚙️  ETAPA 4: VERIFICAÇÃO DE SERVIÇOS"
echo "----------------------------------"

# Apache
if systemctl is-active --quiet apache2; then
    log_info "Apache está rodando"
elif systemctl is-active --quiet httpd; then
    log_info "HTTPD está rodando"
else
    log_warning "Apache/HTTPD não está rodando ou não foi detectado"
fi

# Nginx
if systemctl is-active --quiet nginx; then
    log_info "Nginx está rodando"
fi

# MySQL/MariaDB
if systemctl is-active --quiet mysql; then
    log_info "MySQL está rodando"
elif systemctl is-active --quiet mariadb; then
    log_info "MariaDB está rodando"
else
    log_warning "MySQL/MariaDB não está rodando ou não foi detectado"
fi

# PHP
PHP_VERSION=$(php -v 2>/dev/null | head -n 1)
if [ $? -eq 0 ]; then
    log_info "PHP detectado: $PHP_VERSION"
else
    log_error "PHP não encontrado ou com problemas"
fi

# ETAPA 5: Teste de sintaxe PHP
echo
echo "🔍 ETAPA 5: TESTE DE SINTAXE PHP"
echo "------------------------------"

if [ -f "$PLUGIN_PATH/setup.php" ]; then
    php -l "$PLUGIN_PATH/setup.php" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        log_info "setup.php - sintaxe OK"
    else
        log_error "setup.php - erro de sintaxe"
        php -l "$PLUGIN_PATH/setup.php"
    fi
fi

if [ -f "$PLUGIN_PATH/hook.php" ]; then
    php -l "$PLUGIN_PATH/hook.php" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        log_info "hook.php - sintaxe OK"
    else
        log_error "hook.php - erro de sintaxe"
        php -l "$PLUGIN_PATH/hook.php"
    fi
fi

# ETAPA 6: Resumo e próximos passos
echo
echo "📊 RESUMO DO DIAGNÓSTICO"
echo "======================="
echo "Plugin Path: $PLUGIN_PATH"
echo "Config DB: $DB_CONFIG"
echo "GLPI Path: $GLPI_PATH"
echo
echo "🎯 PRÓXIMOS PASSOS:"
echo "1. Verifique se há erros de sintaxe nos arquivos PHP"
echo "2. Confirme se a tabela foi criada no banco de dados"
echo "3. Verifique logs de erro detalhados"
echo "4. Teste no formulário de ticket do GLPI"
echo
echo "💡 DICA: Salve este output e compartilhe para análise detalhada"

echo
echo "=== FIM DO DIAGNÓSTICO ==="
